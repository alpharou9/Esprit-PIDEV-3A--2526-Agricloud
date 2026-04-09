<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return Order[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'customer')
            ->leftJoin('o.seller', 'seller')
            ->leftJoin('o.product', 'product')
            ->addSelect('customer', 'seller', 'product')
            ->where('customer.name LIKE :q OR seller.name LIKE :q OR product.name LIKE :q OR o.status LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('o.createdAt', 'DESC')
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
}
