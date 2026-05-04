<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TemporaryShippingStorage
{
    private const TTL_SECONDS = 604800;

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Stores shipping data temporarily for each created order.
     */
    public function storeForOrders(array $orders, array $shippingDetails): void
    {
        $payload = $this->normalize($shippingDetails);

        foreach ($orders as $order) {
            if (!$order instanceof Order || $order->getId() === null) {
                continue;
            }

            $key = $this->getCacheKey($order);
            $this->cache->get($key, function (ItemInterface $item) use ($payload): array {
                $item->expiresAfter(self::TTL_SECONDS);

                return $payload;
            });
        }
    }

    public function getForOrder(Order $order): ?array
    {
        if ($order->getId() === null) {
            return null;
        }

        $value = $this->cache->get($this->getCacheKey($order), function (ItemInterface $item): ?array {
            $item->expiresAfter(1);

            return null;
        });

        return is_array($value) ? $value : null;
    }

    public function deleteForOrder(Order $order): void
    {
        if ($order->getId() === null) {
            return;
        }

        $this->cache->delete($this->getCacheKey($order));
    }

    private function getCacheKey(Order $order): string
    {
        return 'temporary_shipping_order_' . $order->getId();
    }

    private function normalize(array $shippingDetails): array
    {
        return [
            'name' => trim((string) ($shippingDetails['name'] ?? '')),
            'email' => trim((string) ($shippingDetails['email'] ?? '')),
            'phone' => $this->nullableTrim($shippingDetails['phone'] ?? null),
            'line1' => trim((string) ($shippingDetails['line1'] ?? '')),
            'line2' => $this->nullableTrim($shippingDetails['line2'] ?? null),
            'city' => trim((string) ($shippingDetails['city'] ?? '')),
            'postal_code' => $this->nullableTrim($shippingDetails['postal_code'] ?? null),
            'country' => $this->nullableTrim($shippingDetails['country'] ?? null) ?? 'TN',
            'formatted' => $this->formatAddress($shippingDetails),
            'notes' => $this->nullableTrim($shippingDetails['notes'] ?? null),
        ];
    }

    private function formatAddress(array $shippingDetails): string
    {
        $parts = array_filter([
            trim((string) ($shippingDetails['line1'] ?? '')),
            $this->nullableTrim($shippingDetails['line2'] ?? null),
            trim((string) ($shippingDetails['city'] ?? '')),
            $this->nullableTrim($shippingDetails['postal_code'] ?? null),
            $this->nullableTrim($shippingDetails['country'] ?? null) ?? 'TN',
        ], static fn (?string $value) => $value !== null && $value !== '');

        return implode(', ', $parts);
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
