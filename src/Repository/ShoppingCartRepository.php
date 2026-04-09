<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ShoppingCart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShoppingCart>
 */
class ShoppingCartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShoppingCart::class);
    }

    /**
     * @return ShoppingCart[]
     */
    public function findCartForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.product', 'p')
            ->leftJoin('p.user', 'seller')
            ->leftJoin('p.farm', 'farm')
            ->addSelect('p', 'seller', 'farm')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndProduct(User $user, Product $product): ?ShoppingCart
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.product = :product')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getCartTotal(User $user): float
    {
        return (float) $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.quantity * p.price), 0)')
            ->join('c.product', 'p')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
