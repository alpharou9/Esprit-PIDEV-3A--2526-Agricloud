<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Order> */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function listQueryBuilder(User $user, string $role): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.product', 'p')->addSelect('p')
            ->leftJoin('o.customer', 'c')->addSelect('c')
            ->leftJoin('o.seller', 's')->addSelect('s')
            ->orderBy('o.createdAt', 'DESC');

        if ($role === 'customer') {
            $qb->where('o.customer = :user')->setParameter('user', $user);
        } elseif ($role === 'seller') {
            $qb->where('o.seller = :user')->setParameter('user', $user);
        }
        return $qb;
    }

    public function bestSellingProducts(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->select('p.id AS productId, p.name AS productName, p.category AS category, SUM(o.quantity) AS soldQty, SUM(o.totalPrice) AS revenue')
            ->join('o.product', 'p')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->groupBy('p.id, p.name, p.category')
            ->orderBy('soldQty', 'DESC')
            ->addOrderBy('revenue', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function orderStatusBreakdown(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.status AS status, COUNT(o.id) AS total')
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();
    }

    public function monthlySalesTrend(int $months = 4): array
    {
        $sql = <<<SQL
            SELECT DATE_FORMAT(COALESCE(order_date, created_at), '%Y-%m') AS sales_month,
                   SUM(quantity) AS units_sold,
                   SUM(total_price) AS revenue
            FROM orders
            WHERE status <> :cancelled
              AND COALESCE(order_date, created_at) IS NOT NULL
            GROUP BY sales_month
            ORDER BY sales_month DESC
            LIMIT :months
        SQL;

        $conn = $this->getEntityManager()->getConnection();

        return $conn->executeQuery($sql, [
            'cancelled' => 'cancelled',
            'months' => $months,
        ], [
            'months' => \PDO::PARAM_INT,
        ])->fetchAllAssociative();
    }

    public function countForProduct(Product $product): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
