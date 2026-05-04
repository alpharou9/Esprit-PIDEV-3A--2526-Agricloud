<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findForProduct(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->where('r.product = :product')
            ->setParameter('product', $product)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findStatsForProduct(Product $product): array
    {
        $stats = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating, COUNT(r.id) AS reviewCount')
            ->where('r.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleResult();

        return [
            'averageRating' => $stats['averageRating'] !== null ? round((float) $stats['averageRating'], 1) : null,
            'reviewCount' => (int) ($stats['reviewCount'] ?? 0),
        ];
    }

    public function hasUserReviewedProduct(User $user, Product $product): bool
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.product = :product')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findStatsForProducts(iterable $products): array
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

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.product) AS productId, AVG(r.rating) AS averageRating, COUNT(r.id) AS reviewCount')
            ->where('r.product IN (:productIds)')
            ->setParameter('productIds', $productIds)
            ->groupBy('r.product')
            ->getQuery()
            ->getArrayResult();

        $stats = [];

        foreach ($rows as $row) {
            $productId = (int) $row['productId'];
            $stats[$productId] = [
                'averageRating' => $row['averageRating'] !== null ? round((float) $row['averageRating'], 1) : null,
                'reviewCount' => (int) ($row['reviewCount'] ?? 0),
            ];
        }

        foreach ($productIds as $productId) {
            $stats[$productId] ??= [
                'averageRating' => null,
                'reviewCount' => 0,
            ];
        }

        return $stats;
    }
}
