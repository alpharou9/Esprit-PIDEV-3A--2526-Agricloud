<?php

declare(strict_types=1);

namespace App\Tests\Market\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\OrderStatusService;
use App\Service\TemporaryShippingStorage;
use PHPUnit\Framework\TestCase;

final class OrderStatusServiceTest extends TestCase
{
    public function testAllowedTransitionsIncludePendingToConfirmedAndCancelled(): void
    {
        $service = $this->service();
        $order = (new Order())->setStatus(Order::STATUS_PENDING);

        self::assertTrue($service->canTransition($order, Order::STATUS_CONFIRMED));
        self::assertTrue($service->canTransition($order, Order::STATUS_CANCELLED));
        self::assertFalse($service->canTransition($order, Order::STATUS_DELIVERED));
    }

    public function testCancellingRestoresStockAndStoresReason(): void
    {
        $temporaryShippingStorage = $this->createMock(TemporaryShippingStorage::class);
        $temporaryShippingStorage
            ->expects(self::once())
            ->method('deleteForOrder');

        $service = new OrderStatusService($temporaryShippingStorage);
        $product = (new Product())->setQuantity(7);
        $order = (new Order())
            ->setProduct($product)
            ->setQuantity(4)
            ->setStatus(Order::STATUS_CONFIRMED);

        $result = $service->applyStatusChange($order, Order::STATUS_CANCELLED, 'Buyer request');

        self::assertTrue($result['success']);
        self::assertSame(11, $product->getQuantity());
        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        self::assertSame('Buyer request', $order->getCancelledReason());
        self::assertInstanceOf(\DateTimeInterface::class, $order->getCancelledAt());
    }

    public function testReopeningCancelledOrderFailsWhenStockIsUnavailable(): void
    {
        $service = $this->service();
        $product = (new Product())->setQuantity(1)->setStatus('approved');
        $order = (new Order())
            ->setProduct($product)
            ->setQuantity(2)
            ->setStatus(Order::STATUS_CANCELLED);

        $result = $service->applyStatusChange($order, Order::STATUS_PENDING);

        self::assertFalse($result['success']);
        self::assertSame(Order::STATUS_CANCELLED, $order->getStatus());
        self::assertSame(1, $product->getQuantity());
    }

    private function service(): OrderStatusService
    {
        return new OrderStatusService($this->createMock(TemporaryShippingStorage::class));
    }
}
