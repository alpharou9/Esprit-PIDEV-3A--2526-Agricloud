<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyConverterService
{
    private const BASE_CURRENCY = 'TND';
    private const TARGET_CURRENCIES = ['EUR', 'USD'];

    private ?array $ratesCache = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiUrl,
    ) {
    }

    public function getSupportedCurrencies(): array
    {
        return self::TARGET_CURRENCIES;
    }

    public function getRates(): array
    {
        if ($this->ratesCache !== null) {
            return $this->ratesCache;
        }

        $endpoint = rtrim($this->apiUrl, '/') . '/rates';

        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'query' => [
                    'base' => self::BASE_CURRENCY,
                    'quotes' => implode(',', self::TARGET_CURRENCIES),
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->ratesCache = [];
            }

            $data = $response->toArray(false);

            if (isset($data[0]) && is_array($data[0])) {
                $rates = [];

                foreach ($data as $row) {
                    $quote = strtoupper((string) ($row['quote'] ?? ''));
                    $rate = $row['rate'] ?? null;

                    if (in_array($quote, self::TARGET_CURRENCIES, true) && is_numeric($rate)) {
                        $rates[$quote] = (float) $rate;
                    }
                }

                return $this->ratesCache = $rates;
            }

            $rates = is_array($data['rates'] ?? null) ? $data['rates'] : [];

            return $this->ratesCache = array_intersect_key($rates, array_flip(self::TARGET_CURRENCIES));
        } catch (ExceptionInterface) {
            return $this->ratesCache = [];
        }
    }

    public function convertAmount(string|float|int $amount): array
    {
        $amount = (float) $amount;
        $rates = $this->getRates();
        $converted = [];

        foreach (self::TARGET_CURRENCIES as $currency) {
            if (!isset($rates[$currency]) || !is_numeric($rates[$currency])) {
                continue;
            }

            $converted[$currency] = round($amount * (float) $rates[$currency], 2);
        }

        return $converted;
    }
}
