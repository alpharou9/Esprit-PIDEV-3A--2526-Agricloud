<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class StripeCheckoutService
{
    private const SHIPPING_COUNTRIES = ['TN', 'FR', 'DE', 'IT', 'ES', 'GB', 'US'];
    private const ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    private readonly string $secretKey;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CurrencyConverterService $currencyConverterService,
        ?string $secretKey,
    ) {
        $this->secretKey = $secretKey ?? '';
    }

    /**
     * Creates a hosted Stripe Checkout Session for the provided orders.
     */
    public function createCheckoutSession(array $orders, Request $request): array
    {
        if ($this->secretKey === '') {
            return [
                'success' => false,
                'message' => 'Stripe is not configured yet. Add your Stripe secret key to the environment first.',
            ];
        }

        $stripeCurrency = $this->getStripeCurrency();
        $rates = $this->currencyConverterService->getRates();

        if (!isset($rates[$stripeCurrency]) || !is_numeric($rates[$stripeCurrency])) {
            return [
                'success' => false,
                'message' => sprintf('Unable to convert TND to %s for Stripe checkout right now.', $stripeCurrency),
            ];
        }

        $payload = [
            'mode' => 'payment',
            'success_url' => $this->buildAbsoluteUrl($request, '/market/cart/checkout/stripe/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => $this->buildAbsoluteUrl($request, '/market/cart/checkout/stripe/cancel?session_id={CHECKOUT_SESSION_ID}'),
            'payment_method_types[]' => 'card',
            'phone_number_collection[enabled]' => 'true',
            'customer_creation' => 'always',
            'metadata[order_ids]' => implode(',', array_map(static fn (Order $order) => (string) $order->getId(), $orders)),
            'metadata[presentment_currency]' => strtolower($stripeCurrency),
        ];

        foreach (self::SHIPPING_COUNTRIES as $index => $countryCode) {
            $payload[sprintf('shipping_address_collection[allowed_countries][%d]', $index)] = $countryCode;
        }

        foreach (array_values($orders) as $index => $order) {
            $convertedUnitAmount = round(((float) $order->getUnitPrice()) * (float) $rates[$stripeCurrency], 2);

            $payload["line_items[$index][quantity]"] = $order->getQuantity();
            $payload["line_items[$index][price_data][currency]"] = strtolower($stripeCurrency);
            $payload["line_items[$index][price_data][unit_amount]"] = $this->toStripeAmount($convertedUnitAmount, $stripeCurrency);
            $payload["line_items[$index][price_data][product_data][name]"] = $order->getProduct()->getName();
            $payload["line_items[$index][price_data][product_data][description]"] = sprintf(
                'Order #%d for %s (%s %.2f from TND)',
                $order->getId(),
                $order->getCustomer()?->getName() ?? 'AgriCloud customer',
                $stripeCurrency,
                $convertedUnitAmount
            );
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.stripe.com/v1/checkout/sessions', [
                'auth_basic' => [$this->secretKey, ''],
                'body' => $payload,
                'timeout' => 30,
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $data = json_decode($response->getContent(false), true);

                return [
                    'success' => false,
                    'message' => $data['error']['message'] ?? 'Stripe could not create a checkout session right now.',
                ];
            }

            $data = $response->toArray(false);

            if (!isset($data['id'], $data['url'])) {
                return [
                    'success' => false,
                    'message' => 'Stripe returned an unexpected checkout response.',
                ];
            }

            return [
                'success' => true,
                'sessionId' => (string) $data['id'],
                'checkoutUrl' => (string) $data['url'],
            ];
        } catch (ExceptionInterface) {
            return [
                'success' => false,
                'message' => 'Stripe is temporarily unavailable. Please try again in a moment.',
            ];
        }
    }

    public function retrieveSession(string $sessionId): ?array
    {
        if ($sessionId === '' || $this->secretKey === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', sprintf('https://api.stripe.com/v1/checkout/sessions/%s', $sessionId), [
                'auth_basic' => [$this->secretKey, ''],
                'timeout' => 20,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray(false);

            return is_array($data) ? $data : null;
        } catch (ExceptionInterface) {
            return null;
        }
    }

    public function getStripeCurrency(): string
    {
        return 'EUR';
    }

    public function extractShippingDetails(?array $session): ?array
    {
        if (!is_array($session)) {
            return null;
        }

        $shippingDetails = $session['shipping_details'] ?? null;
        $customerDetails = $session['customer_details'] ?? null;
        $address = is_array($shippingDetails['address'] ?? null) ? $shippingDetails['address'] : [];

        if ($shippingDetails === null && $customerDetails === null) {
            return null;
        }

        return [
            'name' => $shippingDetails['name'] ?? $customerDetails['name'] ?? null,
            'phone' => $shippingDetails['phone'] ?? $customerDetails['phone'] ?? null,
            'email' => $customerDetails['email'] ?? null,
            'line1' => $address['line1'] ?? null,
            'line2' => $address['line2'] ?? null,
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'postal_code' => $address['postal_code'] ?? null,
            'country' => $address['country'] ?? null,
            'formatted' => $this->formatAddress($address),
        ];
    }

    private function toStripeAmount(string|float|int $amount, string $currency): int
    {
        $currency = strtolower($currency);
        $multiplier = in_array($currency, self::ZERO_DECIMAL_CURRENCIES, true) ? 1 : 100;

        return (int) round(((float) $amount) * $multiplier);
    }

    private function buildAbsoluteUrl(Request $request, string $path): string
    {
        return rtrim($request->getSchemeAndHttpHost(), '/') . $path;
    }

    private function formatAddress(array $address): ?string
    {
        $parts = array_filter([
            $address['line1'] ?? null,
            $address['line2'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ], static fn (?string $value) => $value !== null && trim($value) !== '');

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }
}
