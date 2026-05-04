<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ReviewFixtures extends Fixture implements FixtureGroupInterface
{
    private const COMMENTS_BY_RATING = [
        1 => [
            'The product was not as fresh as I expected.',
            'I was disappointed with the quality this time.',
            'Not my favorite purchase, the result felt below average.',
        ],
        2 => [
            'Decent product, but the quality could be better.',
            'It was usable, though not as good as I hoped.',
            'Average experience overall, with room for improvement.',
        ],
        3 => [
            'Good product for regular cooking and daily use.',
            'Quite practical and the quality was acceptable.',
            'A fair purchase for the price.',
        ],
        4 => [
            'Fresh product and very useful in the kitchen.',
            'Good quality, I would gladly buy it again.',
            'Very satisfying purchase for everyday meals.',
        ],
        5 => [
            'Excellent quality and very fresh product.',
            'One of the best products I bought from the marketplace.',
            'Really great taste and presentation, highly recommended.',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->seed(2026);

        $existingPairs = [];
        $reviewsPerProduct = [];

        foreach ($manager->getRepository(Review::class)->findAll() as $existingReview) {
            $product = $existingReview->getProduct();
            $user = $existingReview->getUser();

            if ($product instanceof Product && $user instanceof User) {
                $existingPairs[$this->buildPairKey($product, $user)] = true;
                $productKey = (int) $product->getId();
                $reviewsPerProduct[$productKey] = ($reviewsPerProduct[$productKey] ?? 0) + 1;
            }
        }

        $orders = $manager->getRepository(Order::class)->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')->addSelect('p')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', Order::STATUS_CANCELLED)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        shuffle($orders);

        foreach ($orders as $order) {
            $product = $order->getProduct();
            $user = $order->getCustomer();

            if (!$product instanceof Product || !$user instanceof User) {
                continue;
            }

            $pairKey = $this->buildPairKey($product, $user);
            $productKey = (int) $product->getId();
            $reviewsPerProduct[$productKey] = $reviewsPerProduct[$productKey] ?? 0;

            if (isset($existingPairs[$pairKey]) || $reviewsPerProduct[$productKey] >= 4 || $faker->boolean(40)) {
                continue;
            }

            $rating = $this->randomDemoRating($faker);

            $review = (new Review())
                ->setProduct($product)
                ->setUser($user)
                ->setRating($rating)
                ->setComment($this->buildComment($product, $rating))
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-4 months', 'now')));

            $manager->persist($review);

            $existingPairs[$pairKey] = true;
            ++$reviewsPerProduct[$productKey];
        }

        $products = $manager->getRepository(Product::class)->findBy(['status' => 'approved'], ['createdAt' => 'DESC'], 18);
        $users = $manager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->setMaxResults(18)
            ->getQuery()
            ->getResult();

        foreach ($products as $index => $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $productKey = (int) $product->getId();

            if (($reviewsPerProduct[$productKey] ?? 0) > 0) {
                continue;
            }

            $user = $users[$index % max(count($users), 1)] ?? null;

            if (!$user instanceof User) {
                continue;
            }

            $pairKey = $this->buildPairKey($product, $user);

            if (isset($existingPairs[$pairKey])) {
                continue;
            }

            $rating = $this->randomDemoRating($faker);

            $review = (new Review())
                ->setProduct($product)
                ->setUser($user)
                ->setRating($rating)
                ->setComment($this->buildComment($product, $rating))
                ->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 months', 'now')));

            $manager->persist($review);
            $existingPairs[$pairKey] = true;
            $reviewsPerProduct[$productKey] = 1;
        }

        $manager->flush();
    }

    private function buildPairKey(Product $product, User $user): string
    {
        return $product->getId() . '-' . $user->getId();
    }

    private function randomDemoRating(\Faker\Generator $faker): int
    {
        $roll = $faker->numberBetween(1, 100);

        return match (true) {
            $roll <= 8 => 1,
            $roll <= 18 => 2,
            $roll <= 40 => 3,
            $roll <= 72 => 4,
            default => 5,
        };
    }

    private function buildComment(Product $product, int $rating): string
    {
        $baseComment = self::COMMENTS_BY_RATING[$rating][array_rand(self::COMMENTS_BY_RATING[$rating])];

        return sprintf('%s %s', $product->getName() . ':', $baseComment);
    }

    public static function getGroups(): array
    {
        return ['reviews'];
    }
}
