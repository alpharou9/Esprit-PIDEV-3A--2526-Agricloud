<?php

declare(strict_types=1);

namespace App\Tests\Market\Entity;

use App\Entity\CartItem;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class CartItemTest extends TestCase
{
    public function testSubtotalUsesProductPriceAndQuantity(): void
    {
        $product = (new Product())->setPrice('12.50');
        $cartItem = (new CartItem())->setProduct($product)->setQuantity(3);

        self::assertSame(37.5, $cartItem->getSubtotal());
    }
}
