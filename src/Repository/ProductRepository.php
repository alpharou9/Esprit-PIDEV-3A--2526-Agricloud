<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function marketplaceQueryBuilder(?string $q = null, ?string $category = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->where('p.status = :status')->setParameter('status', 'approved')
            ->orderBy('p.quantity', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($category) {
            $qb->andWhere('p.category = :cat')->setParameter('cat', $category);
        }
        return $qb;
    }

    public function sellerQueryBuilder(User $seller, ?string $q = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.user = :seller')->setParameter('seller', $seller)
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.name LIKE :q')->setParameter('q', '%' . $q . '%');
        }
        return $qb;
    }

    public function adminQueryBuilder(?string $q = null, ?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.name LIKE :q OR u.name LIKE :q')->setParameter('q', '%' . $q . '%');
        }
        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        return $qb;
    }

    public function lowStockProducts(int $threshold = 10, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id AS productId, p.name AS productName, p.category AS category, p.quantity AS quantity')
            ->where('p.status = :status')
            ->andWhere('p.quantity <= :threshold')
            ->setParameter('status', 'approved')
            ->setParameter('threshold', $threshold)
            ->orderBy('p.quantity', 'ASC')
            ->addOrderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
