<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommentModerationService
{
    private const HF_MODEL = 'Qwen/Qwen2.5-7B-Instruct';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $huggingFaceApiToken,
    ) {
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function moderate(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return ['allowed' => false, 'reason' => 'Empty comments are not allowed.'];
        }

        $token = trim((string) $this->huggingFaceApiToken);
        if ($token !== '') {
            $decision = $this->moderateWithHuggingFace($token, $content);
            if ($decision !== null) {
                return $decision;
            }
        }

        return $this->moderateWithFallbackRules($content);
    }

    /**
     * @return array{allowed: bool, reason: ?string}|null
     */
    private function moderateWithHuggingFace(string $token, string $content): ?array
    {
        try {
            $response = $this->httpClient->request('POST', 'https://router.huggingface.co/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::HF_MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You moderate blog comments. Return JSON only with exactly these keys: allowed (boolean) and reason (string). Block comments containing insults, hate, harassment, explicit sexual content, threats, repeated spam, or unsafe abuse. Allow normal discussion, disagreement, and constructive criticism.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                    'max_tokens' => 120,
                    'temperature' => 0,
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                ],
                'timeout' => 10,
                'max_duration' => 12,
            ]);
        } catch (TransportExceptionInterface) {
            return null;
        }

        $statusCode = $response->getStatusCode();
        $contentType = strtolower($response->getHeaders(false)['content-type'][0] ?? '');
        $rawContent = $response->getContent(false);

        if ($statusCode < 200 || $statusCode >= 300 || !str_contains($contentType, 'application/json')) {
            return null;
        }

        $data = json_decode($rawContent, true);
        if (!is_array($data) || isset($data['error'])) {
            return null;
        }

        $message = (string) ($data['choices'][0]['message']['content'] ?? '');
        if ($message === '') {
            return null;
        }

        $decoded = json_decode($this->extractJsonObject($message), true);
        if (!is_array($decoded) || !array_key_exists('allowed', $decoded)) {
            return null;
        }

        return [
            'allowed' => (bool) $decoded['allowed'],
            'reason' => isset($decoded['reason']) ? trim((string) $decoded['reason']) : null,
        ];
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    private function moderateWithFallbackRules(string $content): array
    {
        $normalized = mb_strtolower($content);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $blockedTerms = [
            'fuck', 'fucking', 'shit', 'bitch', 'asshole', 'bastard', 'idiot', 'moron',
            'slut', 'whore', 'nigger', 'niga', 'retard', 'dickhead', 'stupid', 'dick',
            'cock', 'penis', 'pussy', 'vagina', 'boobs', 'tits', 'cum', 'horny', 'suck',
        ];

        foreach ($blockedTerms as $term) {
            if (preg_match('/\b' . preg_quote($term, '/') . '\b/u', $normalized) === 1) {
                return [
                    'allowed' => false,
                    'reason' => 'Please keep comments respectful and appropriate.',
                ];
            }
        }

        $blockedPhrases = [
            'suck my dick',
            'suck dick',
            'kill yourself',
            'go to hell',
            'piece of shit',
            'son of a bitch',
        ];

        foreach ($blockedPhrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return [
                    'allowed' => false,
                    'reason' => 'Please keep comments respectful and appropriate.',
                ];
            }
        }

        if (preg_match('/\b(?:sex|sexual|nude|naked|porn|blowjob|handjob)\b/i', $content) === 1) {
            return [
                'allowed' => false,
                'reason' => 'Sexually explicit comments are not allowed.',
            ];
        }

        if (preg_match('/https?:\/\//i', $content) === 1 || preg_match('/\b(?:www\.|telegram|whatsapp|dm me|earn money|crypto)\b/i', $content) === 1) {
            return [
                'allowed' => false,
                'reason' => 'Spam or promotional comments are not allowed.',
            ];
        }

        if (preg_match('/(.)\1{7,}/u', $content) === 1 || preg_match('/\b(\w+)(?:\s+\1){3,}\b/ui', $normalized) === 1) {
            return [
                'allowed' => false,
                'reason' => 'Repeated or spam-like comments are not allowed.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    private function extractJsonObject(string $content): string
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;

        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return $content;
        }

        return substr($content, $start, $end - $start + 1);
    }
}
