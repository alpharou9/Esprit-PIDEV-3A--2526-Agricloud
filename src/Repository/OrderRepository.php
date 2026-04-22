<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    private const SORT_FIELDS = [
        'newest' => ['o.createdAt', 'DESC'],
        'oldest' => ['o.createdAt', 'ASC'],
        'total_asc' => ['o.totalPrice', 'ASC'],
        'total_desc' => ['o.totalPrice', 'DESC'],
        'status_asc' => ['o.status', 'ASC'],
        'status_desc' => ['o.status', 'DESC'],
        'customer_asc' => ['customer.name', 'ASC'],
        'seller_asc' => ['seller.name', 'ASC'],
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return Order[]
     */
    public function findByFilters(
        ?string $query,
        ?int $productId,
        ?int $customerId,
        ?int $sellerId,
        ?string $status,
        string $sort = 'newest'
    ): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'customer')
            ->leftJoin('o.seller', 'seller')
            ->leftJoin('o.product', 'product')
            ->addSelect('customer', 'seller', 'product')
        ;

        if ($query !== null && $query !== '') {
            $queryBuilder
                ->andWhere('customer.name LIKE :q OR seller.name LIKE :q OR product.name LIKE :q OR o.status LIKE :q OR o.shippingCity LIKE :q')
                ->setParameter('q', '%' . $query . '%');
        }

        if ($productId !== null) {
            $queryBuilder
                ->andWhere('product.id = :productId')
                ->setParameter('productId', $productId);
        }

        if ($customerId !== null) {
            $queryBuilder
                ->andWhere('customer.id = :customerId')
                ->setParameter('customerId', $customerId);
        }

        if ($sellerId !== null) {
            $queryBuilder
                ->andWhere('seller.id = :sellerId')
                ->setParameter('sellerId', $sellerId);
        }

        if ($status !== null && $status !== '') {
            $queryBuilder
                ->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        [$field, $direction] = self::SORT_FIELDS[$sort] ?? self::SORT_FIELDS['newest'];

        return $queryBuilder
            ->orderBy($field, $direction)
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getDeliveredRevenue(): float
    {
        return (float) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.totalPrice), 0)')
            ->where('o.status = :status')
            ->setParameter('status', 'delivered')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Order[]
     */
    public function findForCustomer(User $customer): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.product', 'product')
            ->leftJoin('o.seller', 'seller')
            ->addSelect('product', 'seller')
            ->where('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
