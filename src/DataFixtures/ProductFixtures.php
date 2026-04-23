<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ProductFixtures extends Fixture
{
    private const IMAGE_MAP = [
        'Tomatoes' => 'tomate-cerise-allongee.webp',
        'Cherry Tomatoes' => 'tomate-cerise-allongee.webp',
        'Potatoes' => 'Aardappel-groenten-Veggipedia__FitMaxWzYwMCw2MDBd.webp',
        'Onions' => 'oignon-rebeii.webp',
        'Garlic' => 'ail-rouge-sec-importe.webp',
        'Carrots' => 'carotte-sans-fanes.webp',
        'Zucchini' => 'courgette.webp',
        'Eggplant' => 'courge-musquee.webp',
        'Bell Peppers' => 'Red-peppers-afa27f8.jpg',
        'Cucumbers' => 'concombre.webp',
        'Spinach' => 'salade-romaine.webp',
        'Parsley' => 'persil.webp',
        'Mint' => 'celeri-vert.webp',
        'Basil' => 'persil.webp',
        'Apples' => 'images.jpg',
        'Pears' => 'images (2).jpg',
        'Oranges' => 'citron-eureka.webp',
        'Lemons' => 'citron-eureka.webp',
        'Strawberries' => 'red raspberries.png',
        'Raspberries' => 'red raspberries.png',
        'Peaches' => 'images.jpg',
        'Watermelon' => 'download.jpg',
        'Melon' => 'download.jpg',
        'Olive Oil' => 'images (2).jpg',
        'Fresh Milk' => 'download.jpg',
        'Natural Yogurt' => 'homemade-yogurt.jpg',
        'Farm Eggs' => 'download.jpg',
        'Goat Cheese' => 'emmental-de-savoie.jpg',
        'Chicken Breast' => 'chicken-69e02e925c780.jpg',
        'Free-Range Chicken' => 'chicken-69e02e925c780.jpg',
        'Ground Beef' => 'viande-hachee-jeune-bovin.webp',
        'Durum Wheat' => 'images.jpg',
        'Barley' => 'images (2).jpg',
        'Chickpeas' => 'petit-pois.webp',
        'Almonds' => 'images.jpg',
        'Walnuts' => 'images (2).jpg',
    ];

    private const PRODUCT_CATALOG = [
        ['name' => 'Tomatoes', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Cherry Tomatoes', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Potatoes', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Onions', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Garlic', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Carrots', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Zucchini', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Eggplant', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Bell Peppers', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Cucumbers', 'category' => 'Vegetables', 'unit' => 'kg'],
        ['name' => 'Spinach', 'category' => 'Vegetables', 'unit' => 'bundle'],
        ['name' => 'Parsley', 'category' => 'Herbs', 'unit' => 'bundle'],
        ['name' => 'Mint', 'category' => 'Herbs', 'unit' => 'bundle'],
        ['name' => 'Basil', 'category' => 'Herbs', 'unit' => 'bundle'],
        ['name' => 'Apples', 'category' => 'Fruits', 'unit' => 'kg'],
        ['name' => 'Pears', 'category' => 'Fruits', 'unit' => 'kg'],
        ['name' => 'Oranges', 'category' => 'Fruits', 'unit' => 'kg'],
        ['name' => 'Lemons', 'category' => 'Fruits', 'unit' => 'kg'],
        ['name' => 'Strawberries', 'category' => 'Fruits', 'unit' => 'box'],
        ['name' => 'Raspberries', 'category' => 'Fruits', 'unit' => 'box'],
        ['name' => 'Peaches', 'category' => 'Fruits', 'unit' => 'kg'],
        ['name' => 'Watermelon', 'category' => 'Fruits', 'unit' => 'piece'],
        ['name' => 'Melon', 'category' => 'Fruits', 'unit' => 'piece'],
        ['name' => 'Olive Oil', 'category' => 'Pantry', 'unit' => 'bottle'],
        ['name' => 'Fresh Milk', 'category' => 'Dairy', 'unit' => 'litre'],
        ['name' => 'Natural Yogurt', 'category' => 'Dairy', 'unit' => 'piece'],
        ['name' => 'Farm Eggs', 'category' => 'Dairy', 'unit' => 'dozen'],
        ['name' => 'Goat Cheese', 'category' => 'Dairy', 'unit' => 'piece'],
        ['name' => 'Chicken Breast', 'category' => 'Meat', 'unit' => 'kg'],
        ['name' => 'Free-Range Chicken', 'category' => 'Meat', 'unit' => 'piece'],
        ['name' => 'Ground Beef', 'category' => 'Meat', 'unit' => 'kg'],
        ['name' => 'Durum Wheat', 'category' => 'Grains', 'unit' => 'kg'],
        ['name' => 'Barley', 'category' => 'Grains', 'unit' => 'kg'],
        ['name' => 'Chickpeas', 'category' => 'Grains', 'unit' => 'kg'],
        ['name' => 'Almonds', 'category' => 'Nuts', 'unit' => 'kg'],
        ['name' => 'Walnuts', 'category' => 'Nuts', 'unit' => 'kg'],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->seed(2026);

        $seller = $manager->getRepository(User::class)->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')->addSelect('r')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        $eligibleSellers = array_values(array_filter($seller, static function (User $user): bool {
            $roles = $user->getRoles();

            return in_array('ROLE_FARMER', $roles, true)
                || in_array('ROLE_ADMIN', $roles, true)
                || in_array('ROLE_USER', $roles, true);
        }));

        if ($eligibleSellers === []) {
            throw new \RuntimeException('ProductFixtures needs at least one active user in the database before loading products.');
        }

        $count = $faker->numberBetween(30, 50);
        $now = new \DateTimeImmutable();

        for ($i = 0; $i < $count; ++$i) {
            $catalogItem = self::PRODUCT_CATALOG[array_rand(self::PRODUCT_CATALOG)];
            $product = new Product();

            $product->setUser($eligibleSellers[array_rand($eligibleSellers)]);
            $product->setName($catalogItem['name'] . ($faker->boolean(30) ? ' ' . ucfirst($faker->word()) : ''));
            $product->setCategory($catalogItem['category']);
            $product->setUnit($catalogItem['unit']);
            $product->setPrice(number_format($faker->randomFloat(2, 2, 85), 2, '.', ''));
            $product->setQuantity($faker->numberBetween(8, 220));
            $product->setDescription($this->generateDescription($faker, $catalogItem['name']));
            $product->setStatus('approved');
            $product->setViews($faker->numberBetween(0, 350));
            $product->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));
            $product->setUpdatedAt($now);
            $product->setApprovedAt($now);
            $product->setImage(self::IMAGE_MAP[$catalogItem['name']] ?? null);

            $manager->persist($product);
        }

        $manager->flush();
    }

    private function generateDescription(\Faker\Generator $faker, string $productName): string
    {
        $qualities = [
            'freshly harvested',
            'carefully selected',
            'naturally grown',
            'farm-picked',
            'seasonal and flavorful',
        ];

        $uses = [
            'perfect for daily cooking',
            'great for healthy family meals',
            'ideal for salads and simple dishes',
            'a practical choice for market shoppers',
            'ready for your kitchen this week',
        ];

        return sprintf(
            '%s is %s and %s.',
            $productName,
            $qualities[array_rand($qualities)],
            $uses[array_rand($uses)]
        );
    }
}
