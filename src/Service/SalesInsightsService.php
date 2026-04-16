<?php

namespace App\Service;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SalesInsightsService
{
    private const HF_MODEL = 'Qwen/Qwen2.5-7B-Instruct';

    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly ProductRepository $productRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $huggingFaceApiToken,
    ) {
    }

    public function generateInsights(): array
    {
        $snapshot = $this->buildSnapshot();
        $token = trim($this->huggingFaceApiToken);

        if ($token !== '') {
            $insights = $this->generateWithHuggingFace($token, $snapshot);
            if ($insights !== []) {
                return $insights;
            }
        }

        return $this->fallbackInsights($snapshot);
    }

    private function buildSnapshot(): array
    {
        return [
            'bestSellingProducts' => $this->orderRepository->bestSellingProducts(),
            'lowStockProducts' => $this->productRepository->lowStockProducts(),
            'monthlyTrends' => array_reverse($this->orderRepository->monthlySalesTrend()),
            'statusBreakdown' => $this->orderRepository->orderStatusBreakdown(),
        ];
    }

    private function generateWithHuggingFace(string $token, array $snapshot): array
    {
        $prompt = implode("\n", [
            'You analyze ecommerce sales data for a farm marketplace.',
            'Return valid JSON only.',
            'Use exactly this shape: {"insights":["...", "...", "..."]}.',
            'Write 3 to 5 short business insights.',
            'Cover best selling products, low stock alerts, and trends.',
            'Each insight must be plain text, concise, and actionable.',
            'Metrics:',
            json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

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
                            'content' => 'You write sharp sales insights for dashboards.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => 220,
                    'temperature' => 0.5,
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                ],
            ]);
        } catch (TransportExceptionInterface) {
            return [];
        }

        $statusCode = $response->getStatusCode();
        $contentType = strtolower($response->getHeaders(false)['content-type'][0] ?? '');
        $rawContent = $response->getContent(false);

        if ($statusCode < 200 || $statusCode >= 300 || !str_contains($contentType, 'application/json')) {
            return [];
        }

        $data = json_decode($rawContent, true);
        if (!is_array($data) || isset($data['error'])) {
            return [];
        }

        $content = (string) ($data['choices'][0]['message']['content'] ?? '');
        if ($content === '') {
            return [];
        }

        $decoded = json_decode(trim($content), true);
        if (!is_array($decoded)) {
            return [];
        }

        $insights = $decoded['insights'] ?? null;
        if (!is_array($insights)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($insight) {
            return trim((string) $insight);
        }, $insights)));
    }

    private function fallbackInsights(array $snapshot): array
    {
        $insights = [];

        if (($snapshot['bestSellingProducts'][0] ?? null) !== null) {
            $top = $snapshot['bestSellingProducts'][0];
            $insights[] = sprintf(
                '%s is currently the best seller with %s units sold and %s TND in revenue.',
                $top['productName'],
                $top['soldQty'],
                number_format((float) $top['revenue'], 2, '.', '')
            );
        }

        if ($snapshot['lowStockProducts'] !== []) {
            $lowStockNames = array_map(static fn(array $product) => sprintf('%s (%s left)', $product['productName'], $product['quantity']), array_slice($snapshot['lowStockProducts'], 0, 3));
            $insights[] = 'Low stock alert: ' . implode(', ', $lowStockNames) . '.';
        }

        if (count($snapshot['monthlyTrends']) >= 2) {
            $latest = $snapshot['monthlyTrends'][count($snapshot['monthlyTrends']) - 1];
            $previous = $snapshot['monthlyTrends'][count($snapshot['monthlyTrends']) - 2];
            $latestRevenue = (float) ($latest['revenue'] ?? 0);
            $previousRevenue = (float) ($previous['revenue'] ?? 0);

            if ($latestRevenue > $previousRevenue) {
                $insights[] = sprintf('Revenue trend is improving: %s generated %.2f TND versus %.2f TND in %s.', $latest['sales_month'], $latestRevenue, $previousRevenue, $previous['sales_month']);
            } elseif ($latestRevenue < $previousRevenue) {
                $insights[] = sprintf('Revenue dipped in %s to %.2f TND from %.2f TND in %s, so it may be time to boost visibility or promotions.', $latest['sales_month'], $latestRevenue, $previousRevenue, $previous['sales_month']);
            }
        }

        if ($snapshot['statusBreakdown'] !== []) {
            foreach ($snapshot['statusBreakdown'] as $row) {
                if (($row['status'] ?? '') === 'pending' && (int) ($row['total'] ?? 0) > 0) {
                    $insights[] = sprintf('There are %d pending orders waiting for action, which could affect delivery speed and customer satisfaction.', (int) $row['total']);
                    break;
                }
            }
        }

        if ($insights === []) {
            $insights[] = 'Sales data is still limited, so more completed orders are needed before strong trends emerge.';
        }

        return array_slice($insights, 0, 5);
    }
}
