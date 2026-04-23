<?php

namespace App\Repository;

use App\Entity\Favorite;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    public function findOneForUserAndProduct(User $user, Product $product): ?Favorite
    {
        return $this->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);
    }

    public function isFavoritedByUser(User $user, Product $product): bool
    {
        return $this->findOneForUserAndProduct($user, $product) !== null;
    }

    public function findFavoriteProductIdsForUser(User $user, iterable $products): array
    {
        $productIds = [];

        foreach ($products as $product) {
            if ($product instanceof Product && $product->getId() !== null) {
                $productIds[] = $product->getId();
            }
        }

        $productIds = array_values(array_unique($productIds));

        if ($productIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.product) AS productId')
            ->where('f.user = :user')
            ->andWhere('f.product IN (:productIds)')
            ->setParameter('user', $user)
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['productId'], $rows);
    }

    public function findFavoritesForUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.product', 'p')->addSelect('p')
            ->leftJoin('p.user', 'seller')->addSelect('seller')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
