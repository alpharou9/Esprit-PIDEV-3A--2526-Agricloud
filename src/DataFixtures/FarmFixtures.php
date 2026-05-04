<?php

namespace App\DataFixtures;

use App\Entity\Farm;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class FarmFixtures extends Fixture implements FixtureGroupInterface
{
    private const FARM_TYPES = [
        'Olive Grove',
        'Vegetable Farm',
        'Fruit Orchard',
        'Dairy Farm',
        'Mixed Farm',
        'Herb Garden',
        'Grain Farm',
    ];

    private const TUNISIAN_LOCATIONS = [
        ['name' => 'Sidi Bouzid', 'lat' => '35.0382', 'lng' => '9.4849'],
        ['name' => 'Kairouan', 'lat' => '35.6781', 'lng' => '10.0963'],
        ['name' => 'Nabeul', 'lat' => '36.4561', 'lng' => '10.7376'],
        ['name' => 'Bizerte', 'lat' => '37.2744', 'lng' => '9.8739'],
        ['name' => 'Beja', 'lat' => '36.7256', 'lng' => '9.1817'],
        ['name' => 'Kasserine', 'lat' => '35.1676', 'lng' => '8.8365'],
        ['name' => 'Mahdia', 'lat' => '35.5047', 'lng' => '11.0622'],
        ['name' => 'Sfax', 'lat' => '34.7406', 'lng' => '10.7603'],
        ['name' => 'Zaghouan', 'lat' => '36.4029', 'lng' => '10.1429'],
        ['name' => 'Jendouba', 'lat' => '36.5011', 'lng' => '8.7802'],
        ['name' => 'Gabes', 'lat' => '33.8815', 'lng' => '10.0982'],
        ['name' => 'Kef', 'lat' => '36.1826', 'lng' => '8.7148'],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $faker->seed(2026);

        $users = $manager->getRepository(User::class)->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')->addSelect('r')
            ->where('u.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();

        $owners = array_values(array_filter($users, static function (User $user): bool {
            $roles = $user->getRoles();

            return in_array('ROLE_FARMER', $roles, true) || in_array('ROLE_ADMIN', $roles, true);
        }));

        if ($owners === []) {
            throw new \RuntimeException('FarmFixtures needs at least one active farmer or admin user in the database.');
        }

        $admins = array_values(array_filter($users, static function (User $user): bool {
            return in_array('ROLE_ADMIN', $user->getRoles(), true);
        }));

        $now = new \DateTimeImmutable();

        for ($i = 0; $i < 10; ++$i) {
            $location = self::TUNISIAN_LOCATIONS[$i % count(self::TUNISIAN_LOCATIONS)];
            $status = $faker->boolean(75) ? 'approved' : 'pending';

            $farm = new Farm();
            $farm->setUser($owners[array_rand($owners)]);
            $farm->setName(sprintf('%s %s', ucfirst($faker->word()), self::FARM_TYPES[array_rand(self::FARM_TYPES)]));
            $farm->setLocation($location['name']);
            $farm->setLatitude($this->offsetCoordinate($location['lat'], $faker));
            $farm->setLongitude($this->offsetCoordinate($location['lng'], $faker));
            $farm->setArea(number_format($faker->randomFloat(2, 2.5, 80), 2, '.', ''));
            $farm->setFarmType(self::FARM_TYPES[array_rand(self::FARM_TYPES)]);
            $farm->setDescription($this->generateDescription($faker, $location['name']));
            $farm->setStatus($status);
            $farm->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-10 months', '-2 weeks')));
            $farm->setUpdatedAt($now);

            if ($status === 'approved') {
                $farm->setApprovedAt($now);
                $farm->setApprovedBy($admins !== [] ? $admins[array_rand($admins)] : null);
            }

            $manager->persist($farm);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['farms'];
    }

    private function generateDescription(\Faker\Generator $faker, string $location): string
    {
        $focuses = [
            'seasonal vegetables',
            'fresh fruit harvests',
            'olive production',
            'mixed family farming',
            'herbs and aromatic plants',
            'sustainable local produce',
        ];

        $strengths = [
            'serves nearby markets with reliable weekly supply',
            'focuses on practical farm-to-market quality',
            'keeps a simple and well-organized production cycle',
            'supports fresh local distribution for households and shops',
            'balances careful harvesting with consistent output',
        ];

        return sprintf(
            'Located in %s, this farm focuses on %s and %s.',
            $location,
            $focuses[array_rand($focuses)],
            $strengths[array_rand($strengths)]
        );
    }

    private function offsetCoordinate(string $baseCoordinate, \Faker\Generator $faker): string
    {
        return number_format(((float) $baseCoordinate) + $faker->randomFloat(4, -0.12, 0.12), 8, '.', '');
    }
}
