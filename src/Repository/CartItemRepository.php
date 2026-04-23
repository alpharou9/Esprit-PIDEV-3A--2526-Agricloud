<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CartItem> */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /** @return CartItem[] */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.product', 'p')->addSelect('p')
            ->where('c.user = :user')->setParameter('user', $user)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function getCartTotal(User $user): float
    {
        $items = $this->findByUser($user);
        return array_sum(array_map(fn($i) => $i->getSubtotal(), $items));
    }

    public function countItems(User $user): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('SUM(c.quantity)')
            ->where('c.user = :user')->setParameter('user', $user)
            ->getQuery()->getSingleScalarResult();
    }
}
