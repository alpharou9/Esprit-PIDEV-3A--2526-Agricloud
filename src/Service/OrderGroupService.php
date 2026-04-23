<?php

namespace App\Service;

use App\Entity\Order;

class OrderGroupService
{
    /**
     * Groups order rows that were created by the same cart checkout.
     *
     * The current database stores each product line as an Order row. This helper
     * keeps that schema, but lets the UI present those rows as one checkout order.
     *
     * @param Order[] $orders
     *
     * @return array<string, array{key: string, representative: Order, orders: Order[], itemCount: int, quantityTotal: int, totalPrice: float, productSummary: string}>
     */
    public function group(array $orders): array
    {
        $groups = [];

        foreach ($orders as $order) {
            $key = $this->getKey($order);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'representative' => $order,
                    'orders' => [],
                    'itemCount' => 0,
                    'quantityTotal' => 0,
                    'totalPrice' => 0.0,
                    'productSummary' => '',
                ];
            }

            $groups[$key]['orders'][] = $order;
            $groups[$key]['itemCount']++;
            $groups[$key]['quantityTotal'] += $order->getQuantity();
            $groups[$key]['totalPrice'] += (float) $order->getTotalPrice();
        }

        foreach ($groups as $key => $group) {
            $names = array_map(
                static fn (Order $order): string => $order->getProduct()?->getName() ?? 'Product',
                $group['orders']
            );

            $groups[$key]['productSummary'] = $this->summarizeProducts($names);
        }

        return $groups;
    }

    public function getKey(Order $order): string
    {
        $stripeSessionId = trim((string) $order->getStripeSessionId());

        if ($stripeSessionId !== '') {
            return 'stripe:' . $stripeSessionId;
        }

        return sprintf(
            'checkout:%s:%s:%s',
            $order->getCustomer()?->getId() ?? 'guest',
            $order->getPaymentMethod(),
            $order->getCreatedAt()?->format('YmdHis') ?? $order->getId()
        );
    }

    /**
     * @param Order[] $orders
     */
    public function getCombinedStatusLabel(array $orders): string
    {
        $labels = array_unique(array_map(static fn (Order $order): string => $order->getStatusLabel(), $orders));

        return count($labels) === 1 ? reset($labels) : 'Mixed';
    }

    /**
     * @param Order[] $orders
     */
    public function getCombinedStatusColor(array $orders): string
    {
        $colors = array_unique(array_map(static fn (Order $order): string => $order->getStatusColor(), $orders));

        return count($colors) === 1 ? reset($colors) : 'secondary';
    }

    private function summarizeProducts(array $names): string
    {
        $names = array_values(array_unique(array_filter($names)));

        if (count($names) <= 2) {
            return implode(', ', $names);
        }

        return sprintf('%s, %s + %d more', $names[0], $names[1], count($names) - 2);
    }
}
