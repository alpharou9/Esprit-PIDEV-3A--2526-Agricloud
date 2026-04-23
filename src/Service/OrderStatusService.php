<?php

namespace App\Service;

use App\Entity\Order;

class OrderStatusService
{
    public function __construct(
        private readonly TemporaryShippingStorage $temporaryShippingStorage,
    ) {
    }

    public function getAllowedStatuses(): array
    {
        return Order::getAllowedStatuses();
    }

    public function canTransition(Order $order, string $nextStatus): bool
    {
        $currentStatus = $order->getStatus();

        if (!in_array($nextStatus, $this->getAllowedStatuses(), true)) {
            return false;
        }

        if ($currentStatus === $nextStatus) {
            return true;
        }

        $transitions = [
            Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
            Order::STATUS_CONFIRMED => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
            Order::STATUS_PREPARING => [Order::STATUS_SHIPPED, Order::STATUS_CANCELLED],
            Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED],
            Order::STATUS_DELIVERED => [],
            Order::STATUS_CANCELLED => [Order::STATUS_PENDING],
        ];

        return in_array($nextStatus, $transitions[$currentStatus] ?? [], true);
    }

    public function getSelectableStatuses(Order $order): array
    {
        $currentStatus = $order->getStatus();
        $statuses = [$currentStatus];

        foreach ($this->getAllowedStatuses() as $status) {
            if ($status !== $currentStatus && $this->canTransition($order, $status)) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }

    public function applyStatusChange(Order $order, string $nextStatus, ?string $reason = null): array
    {
        $currentStatus = $order->getStatus();
        $product = $order->getProduct();

        if (!$this->canTransition($order, $nextStatus)) {
            return [
                'success' => false,
                'message' => sprintf('You cannot move an order from %s to %s.', $order->getStatusLabel(), Order::getStatusLabels()[$nextStatus] ?? ucfirst($nextStatus)),
                'confirmedJustNow' => false,
            ];
        }

        if ($nextStatus === Order::STATUS_CANCELLED && $currentStatus !== Order::STATUS_CANCELLED) {
            $product->setQuantity($product->getQuantity() + $order->getQuantity());
            $order->setCancelledAt(new \DateTime());
            $order->setCancelledReason($reason ?: 'Order cancelled');
        } elseif ($currentStatus === Order::STATUS_CANCELLED && $nextStatus === Order::STATUS_PENDING) {
            if ($product->getStatus() !== 'approved' || $product->getQuantity() < $order->getQuantity()) {
                return [
                    'success' => false,
                    'message' => 'This order cannot be reopened because the product is no longer available in stock.',
                    'confirmedJustNow' => false,
                ];
            }

            $product->setQuantity($product->getQuantity() - $order->getQuantity());
            $order->setCancelledAt(null);
            $order->setCancelledReason(null);
        }

        $order->setStatus($nextStatus);
        $order->setUpdatedAt(new \DateTime());

        if (in_array($nextStatus, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED], true)) {
            $this->temporaryShippingStorage->deleteForOrder($order);
        }

        return [
            'success' => true,
            'message' => 'Order status updated.',
            'confirmedJustNow' => $currentStatus !== Order::STATUS_CONFIRMED && $nextStatus === Order::STATUS_CONFIRMED,
        ];
    }
}
