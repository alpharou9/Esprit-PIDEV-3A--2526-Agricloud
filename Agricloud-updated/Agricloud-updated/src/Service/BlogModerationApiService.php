<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BlogModerationApiService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function analyze(string $text, string $type = 'comment'): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['label' => 'empty', 'action' => 'reject', 'score' => 1.0, 'reason' => 'Text cannot be empty.', 'provider' => 'local-fallback'];
        }

        $huggingFace = $this->callHuggingFace($text);
        if ($huggingFace !== null) {
            return $huggingFace;
        }

        return $this->localAnalysis($text);
    }

    private function callHuggingFace(string $text): ?array
    {
        $token = $this->env('HF_TOKEN');
        if ($token === '') {
            return null;
        }

        $model = $this->env('HF_MODERATION_MODEL') ?: 'unitary/toxic-bert';

        try {
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/' . rawurlencode($model), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'inputs' => $text,
                    'options' => [
                        'wait_for_model' => true,
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $labels = [];
            if (isset($data[0]) && is_array($data[0])) {
                $labels = $data[0];
            } elseif (is_array($data)) {
                $labels = $data;
            }

            if (!$labels) {
                return null;
            }

            $top = null;
            foreach ($labels as $item) {
                if (!is_array($item) || !isset($item['label'], $item['score'])) {
                    continue;
                }
                if ($top === null || (float) $item['score'] > (float) $top['score']) {
                    $top = $item;
                }
            }

            if ($top === null) {
                return null;
            }

            $label = mb_strtolower((string) $top['label']);
            $score = (float) $top['score'];
            $unsafe = preg_match('/toxic|insult|obscene|threat|hate|identity|severe|offensive/i', $label) === 1;

            if ($unsafe && $score >= 0.75) {
                return ['label' => $label, 'action' => 'reject', 'score' => $score, 'reason' => 'Hugging Face moderation flagged this as unsafe.', 'provider' => 'huggingface'];
            }

            if ($unsafe && $score >= 0.4) {
                return ['label' => $label, 'action' => 'pending', 'score' => $score, 'reason' => 'Hugging Face moderation suggests manual review.', 'provider' => 'huggingface'];
            }

            return ['label' => 'safe', 'action' => 'approve', 'score' => $score, 'reason' => 'Hugging Face moderation marked this content safe.', 'provider' => 'huggingface'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function localAnalysis(string $text): array
    {
        $blockedPatterns = [
            '/\bfuck(?:ing)?\b/i',
            '/\bshit(?:ty)?\b/i',
            '/\bbitch(?:es)?\b/i',
            '/\basshole\b/i',
            '/\bbastard\b/i',
            '/\bdick\b/i',
            '/\bcunt\b/i',
            '/\bmotherfucker\b/i',
            '/\bpiece\s+of\s+shit\b/i',
            '/\bkill\s+yourself\b/i',
            '/\bi\s+hate\s+you\b/i',
            '/\byou\s+suck\b/i',
            '/\bsuck\s+my\s+\w+\b/i',
            '/\bsuck\s+(?:a\s+)?dick\b/i',
            '/\beat\s+shit\b/i',
            '/\bgo\s+to\s+hell\b/i',
            '/\bidiot\b/i',
            '/\bstupid\b/i',
            '/\bscam\b/i',
            '/\bfraud\b/i',
            '/\bporn\b/i',
            '/\bballs?\b/i',
            '/\bslut\b/i',
            '/\bwhore\b/i'
        ];
        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return ['label' => 'blocked', 'action' => 'reject', 'score' => 0.98, 'reason' => 'Contains abusive, profane, or unsafe language.', 'provider' => 'local-fallback'];
            }
        }

        return ['label' => 'safe', 'action' => 'approve', 'score' => 0.02, 'reason' => 'Looks safe and readable.', 'provider' => 'local-fallback'];
    }

    private function env(string $name): string
    {
        return trim((string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: ''));
    }
}
