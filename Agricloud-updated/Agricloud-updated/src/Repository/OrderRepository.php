<?php

namespace App\Repository;

use App\Entity\Order;
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
}
