<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiService
{
    private const HF_MODEL = 'Qwen/Qwen2.5-7B-Instruct';
    private const ALLOWED_CATEGORIES = [
        'vegetables',
        'fruits',
        'grains',
        'dairy',
        'meat',
        'herbs',
        'other',
    ];
    private const ALLOWED_UNITS = [
        'kg',
        'g',
        'litre',
        'piece',
        'box',
        'dozen',
        'bunch',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $huggingFaceApiToken,
    ) {
    }

    public function generateProductDraft(
        string $name,
        ?string $category,
        ?string $unit,
        ?string $price,
        ?string $origin,
        ?string $stock,
        ?string $descriptionHint = null,
    ): array {
        $name = trim($name);
        $category = trim((string) $category);
        $unit = trim((string) $unit);
        $price = trim((string) $price);
        $origin = trim((string) $origin);
        $stock = trim((string) $stock);
        $descriptionHint = trim((string) $descriptionHint);

        $huggingFaceToken = trim($this->huggingFaceApiToken);

        if ($huggingFaceToken !== '') {
            $generated = $this->generateWithHuggingFace($huggingFaceToken, $name, $category, $unit, $price, $origin, $stock, $descriptionHint);
            if ($generated !== null) {
                return $generated;
            }
        }

        return $this->generateFallbackDraft($name, $category, $unit, $price, $origin, $stock, $descriptionHint);
    }

    private function generateWithHuggingFace(
        string $token,
        string $name,
        string $category,
        string $unit,
        string $price,
        string $origin,
        string $stock,
        string $descriptionHint,
    ): ?array {
        $prompt = implode("\n", [
            'Create a product draft for a farm marketplace form.',
            'Return valid JSON only.',
            'Use exactly these keys: name, category, unit, description.',
            'The category must be one of: vegetables, fruits, grains, dairy, meat, herbs, other.',
            'The unit must be one of: kg, g, litre, piece, box, dozen, bunch.',
            'Keep price and quantity out of the JSON.',
            'Description must be 2 or 3 attractive sentences, natural and easy to read.',
            'Do not use markdown, labels, bullet points, or extra keys.',
            'Do not invent certifications, health claims, or unsupported facts.',
            'Current form values:',
            'Name: ' . ($name !== '' ? $name : 'Not specified'),
            'Category: ' . ($category !== '' ? $category : 'Not specified'),
            'Unit: ' . ($unit !== '' ? $unit : 'Not specified'),
            'Price: ' . ($price !== '' ? $price . ' TND' : 'Not specified'),
            'Origin: ' . ($origin !== '' ? $origin : 'Not specified'),
            'Stock: ' . ($stock !== '' ? $stock : 'Not specified'),
            'Description hint: ' . ($descriptionHint !== '' ? $descriptionHint : 'Not specified'),
        ]);

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://router.huggingface.co/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => self::HF_MODEL,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You write polished agricultural product descriptions for an online marketplace.',
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'max_tokens' => 140,
                        'temperature' => 0.7,
                        'response_format' => [
                            'type' => 'json_object',
                        ],
                    ],
                ]
            );
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

        if (!is_array($data)) {
            return null;
        }

        if (($data['error'] ?? null) !== null) {
            return null;
        }

        if (isset($data['choices'][0]['message']['content'])) {
            return $this->normalizeDraftFromJson((string) $data['choices'][0]['message']['content'], $name, $category, $unit, $price, $origin, $stock, $descriptionHint);
        }

        return null;
    }

    private function normalizeDraftFromJson(
        string $content,
        string $name,
        string $category,
        string $unit,
        string $price,
        string $origin,
        string $stock,
        string $descriptionHint,
    ): ?array {
        $decoded = json_decode(trim($content), true);

        if (!is_array($decoded)) {
            return null;
        }

        return $this->normalizeDraftArray($decoded, $name, $category, $unit, $price, $origin, $stock, $descriptionHint);
    }

    private function generateFallbackDraft(
        string $name,
        string $category,
        string $unit,
        string $price,
        string $origin,
        string $stock,
        string $descriptionHint,
    ): array {
        $resolvedName = $name !== '' ? $name : $this->guessNameFromContext($category, $descriptionHint);
        $resolvedCategory = $this->normalizeCategory($category) ?? $this->guessCategory($resolvedName, $descriptionHint);
        $resolvedUnit = $this->normalizeUnit($unit) ?? $this->guessUnit($resolvedCategory, $resolvedName, $descriptionHint);
        $resolvedDescription = $this->buildFallbackDescription($resolvedName, $resolvedCategory, $price, $origin, $stock, $descriptionHint);

        return [
            'name' => $resolvedName,
            'category' => $resolvedCategory,
            'unit' => $resolvedUnit,
            'description' => $resolvedDescription,
        ];
    }

    private function normalizeDraftArray(
        array $draft,
        string $name,
        string $category,
        string $unit,
        string $price,
        string $origin,
        string $stock,
        string $descriptionHint,
    ): array {
        $fallback = $this->generateFallbackDraft($name, $category, $unit, $price, $origin, $stock, $descriptionHint);

        $resolvedName = trim((string) ($draft['name'] ?? ''));
        if ($resolvedName === '') {
            $resolvedName = $fallback['name'];
        }

        $resolvedCategory = $this->normalizeCategory((string) ($draft['category'] ?? '')) ?? $fallback['category'];
        $resolvedUnit = $this->normalizeUnit((string) ($draft['unit'] ?? '')) ?? $fallback['unit'];

        $resolvedDescription = trim((string) ($draft['description'] ?? ''));
        if ($resolvedDescription === '') {
            $resolvedDescription = $fallback['description'];
        }

        return [
            'name' => $resolvedName,
            'category' => $resolvedCategory,
            'unit' => $resolvedUnit,
            'description' => preg_replace('/\s+/', ' ', $resolvedDescription) ?: $resolvedDescription,
        ];
    }

    private function buildFallbackDescription(
        string $name,
        string $category,
        string $price,
        string $origin,
        string $stock,
        string $descriptionHint,
    ): string {
        $introCategory = $category !== '' ? strtolower($category) : 'farm-fresh';
        $sentenceOne = sprintf(
            '%s is a %s product prepared for customers who want dependable quality, freshness, and everyday value.',
            $name,
            $introCategory
        );

        $details = [];
        if ($origin !== '') {
            $details[] = sprintf('coming from %s', $origin);
        }
        if ($stock !== '') {
            $details[] = sprintf('available in stock (%s)', $stock);
        }
        if ($price !== '') {
            $details[] = sprintf('priced at %s TND', $price);
        }

        if ($details !== []) {
            $sentenceTwo = 'It is ' . implode(', ', $details) . ', making it an attractive choice for buyers looking for a practical and appealing marketplace option.';
        } else {
            $sentenceTwo = 'It is presented as a practical and appealing marketplace option for buyers who appreciate simple products with clear value.';
        }

        $sentenceThree = $descriptionHint !== ''
            ? 'It builds on your idea while keeping the tone friendly, clear, and ready for the marketplace.'
            : 'A great pick for customers who want something fresh, useful, and easy to trust.';

        return trim($sentenceOne . ' ' . $sentenceTwo . ' ' . $sentenceThree);
    }

    private function normalizeCategory(string $category): ?string
    {
        $category = strtolower(trim($category));

        return in_array($category, self::ALLOWED_CATEGORIES, true) ? $category : null;
    }

    private function normalizeUnit(string $unit): ?string
    {
        $unit = strtolower(trim($unit));

        return in_array($unit, self::ALLOWED_UNITS, true) ? $unit : null;
    }

    private function guessCategory(string $name, string $descriptionHint): string
    {
        $context = strtolower(trim($name . ' ' . $descriptionHint));

        $map = [
            'dairy' => ['milk', 'cheese', 'yogurt', 'yaourt', 'yoghurt', 'cream', 'butter', 'vanille'],
            'fruits' => ['apple', 'orange', 'banana', 'strawberry', 'grape', 'lemon', 'melon', 'mango'],
            'vegetables' => ['tomato', 'potato', 'onion', 'carrot', 'pepper', 'lettuce', 'cucumber'],
            'grains' => ['wheat', 'barley', 'corn', 'rice', 'oats', 'flour'],
            'meat' => ['beef', 'chicken', 'lamb', 'turkey', 'meat'],
            'herbs' => ['mint', 'basil', 'parsley', 'coriander', 'thyme', 'rosemary'],
        ];

        foreach ($map as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($context, $keyword)) {
                    return $category;
                }
            }
        }

        return 'other';
    }

    private function guessUnit(string $category, string $name, string $descriptionHint): string
    {
        $context = strtolower(trim($name . ' ' . $descriptionHint));

        if (str_contains($context, 'juice') || str_contains($context, 'milk') || str_contains($context, 'oil')) {
            return 'litre';
        }

        if (str_contains($context, 'egg')) {
            return 'dozen';
        }

        if (str_contains($context, 'herb') || $category === 'herbs') {
            return 'bunch';
        }

        if (str_contains($context, 'box')) {
            return 'box';
        }

        if (in_array($category, ['dairy', 'meat', 'grains', 'vegetables', 'fruits'], true)) {
            return 'kg';
        }

        return 'piece';
    }

    private function guessNameFromContext(string $category, string $descriptionHint): string
    {
        if ($descriptionHint !== '') {
            $words = preg_split('/\s+/', trim($descriptionHint)) ?: [];
            $slice = array_slice($words, 0, 3);
            $guess = trim(implode(' ', $slice));
            if ($guess !== '') {
                return ucfirst($guess);
            }
        }

        return match ($this->normalizeCategory($category)) {
            'vegetables' => 'Fresh Vegetables',
            'fruits' => 'Fresh Fruit Selection',
            'grains' => 'Quality Grain Pack',
            'dairy' => 'Farm Dairy Product',
            'meat' => 'Farm Meat Product',
            'herbs' => 'Fresh Herb Bundle',
            default => 'Farm Product',
        };
    }
}
