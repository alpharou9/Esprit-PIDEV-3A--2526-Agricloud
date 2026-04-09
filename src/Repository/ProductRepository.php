<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    private const SORT_FIELDS = [
        'newest' => ['p.createdAt', 'DESC'],
        'oldest' => ['p.createdAt', 'ASC'],
        'name_asc' => ['p.name', 'ASC'],
        'name_desc' => ['p.name', 'DESC'],
        'price_asc' => ['p.price', 'ASC'],
        'price_desc' => ['p.price', 'DESC'],
        'stock_asc' => ['p.quantity', 'ASC'],
        'stock_desc' => ['p.quantity', 'DESC'],
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findByFilters(
        ?string $query,
        ?string $category,
        ?int $farmId,
        ?string $status,
        string $sort = 'newest',
        ?int $ownerId = null
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.farm', 'f')
            ->addSelect('f')
            ->addSelect('u')
        ;

        if ($query !== null && $query !== '') {
            $queryBuilder
                ->andWhere('p.name LIKE :q OR p.category LIKE :q OR u.name LIKE :q')
                ->setParameter('q', '%' . $query . '%');
        }

        if ($category !== null && $category !== '') {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        if ($farmId !== null) {
            $queryBuilder
                ->andWhere('f.id = :farmId')
                ->setParameter('farmId', $farmId);
        }

        if ($status !== null && $status !== '') {
            $queryBuilder
                ->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        if ($ownerId !== null) {
            $queryBuilder
                ->andWhere('u.id = :ownerId')
                ->setParameter('ownerId', $ownerId);
        }

        [$field, $direction] = self::SORT_FIELDS[$sort] ?? self::SORT_FIELDS['newest'];

        return $queryBuilder
            ->orderBy($field, $direction)
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[]
     */
    public function findAvailableCategories(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category AS category')
            ->where('p.category IS NOT NULL')
            ->andWhere('p.category <> :empty')
            ->setParameter('empty', '')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn (array $row) => (string) $row['category'], $rows);
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countLowStock(int $threshold): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.quantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Product[]
     */
    public function findApprovedCatalog(?string $query, ?string $category, string $sort = 'newest'): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.farm', 'f')
            ->addSelect('u', 'f')
            ->where('p.status = :status')
            ->andWhere('p.quantity > 0')
            ->setParameter('status', 'approved');

        if ($query !== null && $query !== '') {
            $queryBuilder
                ->andWhere('p.name LIKE :q OR p.category LIKE :q OR u.name LIKE :q')
                ->setParameter('q', '%' . $query . '%');
        }

        if ($category !== null && $category !== '') {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        [$field, $direction] = self::SORT_FIELDS[$sort] ?? self::SORT_FIELDS['newest'];

        return $queryBuilder
            ->orderBy($field, $direction)
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findApprovedVisibleById(int $id): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.farm', 'f')
            ->addSelect('u', 'f')
            ->where('p.id = :id')
            ->andWhere('p.status = :status')
            ->andWhere('p.quantity > 0')
            ->setParameter('id', $id)
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
