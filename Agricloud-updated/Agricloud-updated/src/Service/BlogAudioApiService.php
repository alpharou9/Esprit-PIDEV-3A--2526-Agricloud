<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BlogAudioApiService
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function synthesize(string $title, string $content, ?string $language = null): ?array
    {
        $apiKey = $this->env('VOICERSS_API_KEY');
        if ($apiKey === '') {
            return null;
        }

        $text = trim($title . ". " . $content);
        if ($text === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.voicerss.org/', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'audio/mpeg',
                ],
                'body' => [
                    'key' => $apiKey,
                    'hl' => $this->resolveLanguage($language),
                    'src' => mb_substr($text, 0, 4500),
                    'c' => 'MP3',
                    'f' => '44khz_16bit_stereo',
                    'r' => $this->env('VOICERSS_RATE') ?: '0',
                ],
            ]);

            $audio = $response->getContent(false);
            if (str_starts_with($audio, 'ERROR')) {
                return null;
            }

            return [
                'audio' => $audio,
                'content_type' => 'audio/mpeg',
                'provider' => 'voicerss',
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function env(string $name): string
    {
        return trim((string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: ''));
    }

    private function resolveLanguage(?string $language): string
    {
        $language = mb_strtolower(trim((string) $language));
        if ($language === '') {
            return $this->env('VOICERSS_LANGUAGE') ?: 'en-us';
        }

        $map = [
            'ar' => 'ar-sa',
            'zh' => 'zh-cn',
            'nl' => 'nl-nl',
            'fr' => 'fr-fr',
            'de' => 'de-de',
            'hi' => 'hi-in',
            'id' => 'id-id',
            'it' => 'it-it',
            'ja' => 'ja-jp',
            'ko' => 'ko-kr',
            'pl' => 'pl-pl',
            'pt' => 'pt-br',
            'ru' => 'ru-ru',
            'es' => 'es-es',
            'sv' => 'sv-se',
            'tr' => 'tr-tr',
            'uk' => 'uk-ua',
            'vi' => 'vi-vn',
            'en' => 'en-us',
        ];

        return $map[$language] ?? ($this->env('VOICERSS_LANGUAGE') ?: 'en-us');
    }
}
