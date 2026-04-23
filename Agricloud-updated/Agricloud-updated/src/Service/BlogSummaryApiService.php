<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BlogSummaryApiService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function generateSummary(string $title, string $content, int $maxLength = 180): array
    {
        $title = trim($title);
        $content = trim($content);

        if ($title === '' && $content === '') {
            return ['summary' => '', 'provider' => 'none'];
        }

        $huggingFace = $this->callHuggingFace($title, $content, $maxLength);
        if ($huggingFace !== null) {
            return $huggingFace;
        }

        return [
            'summary' => $this->buildLocalSummary($title, $content, $maxLength),
            'provider' => 'local-fallback',
        ];
    }

    private function callHuggingFace(string $title, string $content, int $maxLength): ?array
    {
        $token = $this->env('HF_TOKEN');
        if ($token === '') {
            return null;
        }

        $model = $this->env('HF_SUMMARY_MODEL') ?: 'facebook/bart-large-cnn';
        $input = trim($title . "\n\n" . $content);

        try {
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/' . rawurlencode($model), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'inputs' => $input,
                    'parameters' => [
                        'max_length' => min(140, max(40, (int) floor($maxLength / 2))),
                        'min_length' => 25,
                    ],
                    'options' => [
                        'wait_for_model' => true,
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $summary = '';
            if (isset($data[0]['summary_text'])) {
                $summary = (string) $data[0]['summary_text'];
            } elseif (isset($data['summary_text'])) {
                $summary = (string) $data['summary_text'];
            }

            $summary = trim($summary);
            if ($summary === '') {
                return null;
            }

            return ['summary' => $this->trimText($summary, $maxLength), 'provider' => 'huggingface'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildLocalSummary(string $title, string $content, int $maxLength): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($content)) ?? '');
        if ($clean === '') {
            return $this->trimText($title, $maxLength);
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $clean) ?: [];
        $summary = '';
        foreach ($sentences as $sentence) {
            $candidate = trim($sentence);
            if ($candidate === '') {
                continue;
            }
            $next = $summary === '' ? $candidate : $summary . ' ' . $candidate;
            if (mb_strlen($next) > $maxLength) {
                break;
            }
            $summary = $next;
            if (mb_strlen($summary) >= (int) floor($maxLength * 0.7)) {
                break;
            }
        }

        if ($summary === '') {
            $summary = $clean;
        }
        if ($title !== '' && !str_contains(mb_strtolower($summary), mb_strtolower($title))) {
            $summary = $title . ': ' . $summary;
        }

        return $this->trimText($summary, $maxLength);
    }

    private function trimText(string $text, int $maxLength): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(0, $maxLength - 1))) . '…';
    }

    private function env(string $name): string
    {
        return trim((string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: ''));
    }
}