<?php

declare(strict_types=1);

namespace App\Tests\Market\Entity;

use App\Entity\Order;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testProcessingStatusIsNormalizedToPreparing(): void
    {
        $order = (new Order())->setStatus(' processing ');

        self::assertSame(Order::STATUS_PREPARING, $order->getStatus());
        self::assertSame('Preparing', $order->getStatusLabel());
        self::assertSame(3, $order->getStatusStepNumber());
    }

    public function testCancelledStatusHasFullProgressAndNoStep(): void
    {
        $order = (new Order())->setStatus(Order::STATUS_CANCELLED);

        self::assertSame(100, $order->getStatusProgressPercent());
        self::assertSame(0, $order->getStatusStepNumber());
        self::assertSame('danger', $order->getStatusColor());
    }

    public function testPaymentLabelsDefaultToCashAndPending(): void
    {
        $order = new Order();

        self::assertSame('Cash on delivery', $order->getPaymentMethodLabel());
        self::assertSame('Pending', $order->getPaymentStatusLabel());
        self::assertSame('warning', $order->getPaymentStatusColor());
    }
}
