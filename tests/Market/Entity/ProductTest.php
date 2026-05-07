<?php

declare(strict_types=1);

namespace App\Tests\Market\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testImageUrlKeepsExternalUrl(): void
    {
        $product = (new Product())->setImage('https://example.com/tomato.jpg');

        self::assertSame('https://example.com/tomato.jpg', $product->getImageUrl());
        self::assertTrue($product->isImageExternal());
    }

    public function testImageUrlNormalizesLocalFileName(): void
    {
        $product = (new Product())->setImage('/tomato.jpg');

        self::assertSame('uploads/products/tomato.jpg', $product->getImageUrl());
        self::assertSame($product->getImageUrl(), $product->getImagePath());
        self::assertFalse($product->isImageExternal());
    }

    public function testBenefitsUseCategorySpecificCopy(): void
    {
        $product = (new Product())
            ->setName('Tomatoes')
            ->setQuantity(12)
            ->setUnit('kg')
            ->setCategory(' vegetables ');

        self::assertSame('Everyday vegetable benefits', $product->getBenefitsTitle());
        self::assertStringContainsString('Tomatoes', $product->getBenefitsText());
        self::assertStringContainsString('salads', $product->getBenefitsText());
    }
}
