<?php

declare(strict_types=1);

namespace App\Tests\Market\Service;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\OrderGroupService;
use PHPUnit\Framework\TestCase;

final class OrderGroupServiceTest extends TestCase
{
    public function testGroupUsesStripeSessionAndSummarizesProducts(): void
    {
        $service = new OrderGroupService();

        $orders = [
            $this->order('cs_test_123', 'Tomatoes', 2, '5.50'),
            $this->order('cs_test_123', 'Potatoes', 3, '4.00'),
            $this->order('cs_test_123', 'Milk', 1, '2.25'),
        ];

        $groups = $service->group($orders);

        self::assertArrayHasKey('stripe:cs_test_123', $groups);
        self::assertSame(3, $groups['stripe:cs_test_123']['itemCount']);
        self::assertSame(6, $groups['stripe:cs_test_123']['quantityTotal']);
        self::assertSame(11.75, $groups['stripe:cs_test_123']['totalPrice']);
        self::assertSame('Tomatoes, Potatoes + 1 more', $groups['stripe:cs_test_123']['productSummary']);
    }

    public function testCombinedStatusFallsBackToMixedWhenStatusesDiffer(): void
    {
        $service = new OrderGroupService();
        $orders = [
            (new Order())->setStatus(Order::STATUS_PENDING),
            (new Order())->setStatus(Order::STATUS_DELIVERED),
        ];

        self::assertSame('Mixed', $service->getCombinedStatusLabel($orders));
        self::assertSame('secondary', $service->getCombinedStatusColor($orders));
    }

    private function order(string $stripeSessionId, string $productName, int $quantity, string $totalPrice): Order
    {
        $product = (new Product())->setName($productName);

        return (new Order())
            ->setStripeSessionId($stripeSessionId)
            ->setProduct($product)
            ->setQuantity($quantity)
            ->setTotalPrice($totalPrice);
    }
}
