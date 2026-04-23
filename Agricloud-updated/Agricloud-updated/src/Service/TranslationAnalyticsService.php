<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class TranslationAnalyticsService
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function record(string $language, string $context = 'post'): void
    {
        $language = mb_strtolower(trim($language));
        $context = mb_strtolower(trim($context));

        if ($language === '') {
            return;
        }

        $data = $this->read();
        $data['totalTranslations'] = (int) ($data['totalTranslations'] ?? 0) + 1;
        $data['lastTranslatedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $languages = is_array($data['languages'] ?? null) ? $data['languages'] : [];
        $languages[$language] = (int) ($languages[$language] ?? 0) + 1;
        arsort($languages);
        $data['languages'] = $languages;

        $contexts = is_array($data['contexts'] ?? null) ? $data['contexts'] : [];
        $contexts[$context] = (int) ($contexts[$context] ?? 0) + 1;
        arsort($contexts);
        $data['contexts'] = $contexts;

        $history = is_array($data['history'] ?? null) ? $data['history'] : [];
        array_unshift($history, [
            'language' => $language,
            'context' => $context,
            'at' => $data['lastTranslatedAt'],
        ]);
        $data['history'] = array_slice($history, 0, 12);

        $this->write($data);
    }

    public function getSnapshot(): array
    {
        $data = $this->read();
        $languages = is_array($data['languages'] ?? null) ? $data['languages'] : [];
        $contexts = is_array($data['contexts'] ?? null) ? $data['contexts'] : [];
        $history = is_array($data['history'] ?? null) ? $data['history'] : [];

        $topLanguage = array_key_first($languages);
        $topLanguageCount = $topLanguage !== null ? (int) $languages[$topLanguage] : 0;
        $total = (int) ($data['totalTranslations'] ?? 0);

        $languageItems = [];
        foreach (array_slice($languages, 0, 5, true) as $code => $count) {
            $languageItems[] = [
                'code' => $code,
                'label' => $this->label($code),
                'count' => (int) $count,
                'share' => $total > 0 ? (int) round(((int) $count / $total) * 100) : 0,
            ];
        }

        $contextItems = [];
        foreach (array_slice($contexts, 0, 4, true) as $name => $count) {
            $contextItems[] = [
                'name' => $name,
                'label' => ucfirst($name),
                'count' => (int) $count,
            ];
        }

        return [
            'totalTranslations' => $total,
            'uniqueLanguages' => count($languages),
            'topLanguage' => $topLanguage,
            'topLanguageLabel' => $topLanguage ? $this->label($topLanguage) : 'No data yet',
            'topLanguageCount' => $topLanguageCount,
            'lastTranslatedAt' => $data['lastTranslatedAt'] ?? null,
            'languageItems' => $languageItems,
            'contextItems' => $contextItems,
            'history' => $history,
        ];
    }

    private function filePath(): string
    {
        return $this->kernel->getProjectDir() . '/var/translation_analytics.json';
    }

    private function read(): array
    {
        $path = $this->filePath();
        if (!is_file($path)) {
            return [
                'totalTranslations' => 0,
                'languages' => [],
                'contexts' => [],
                'history' => [],
                'lastTranslatedAt' => null,
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [
            'totalTranslations' => 0,
            'languages' => [],
            'contexts' => [],
            'history' => [],
            'lastTranslatedAt' => null,
        ];
    }

    private function write(array $data): void
    {
        $path = $this->filePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function label(string $code): string
    {
        return match ($code) {
            'ar' => 'Arabic',
            'zh' => 'Chinese',
            'nl' => 'Dutch',
            'fr' => 'French',
            'de' => 'German',
            'hi' => 'Hindi',
            'id' => 'Indonesian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese',
            'en' => 'English',
            default => strtoupper($code),
        };
    }
}
