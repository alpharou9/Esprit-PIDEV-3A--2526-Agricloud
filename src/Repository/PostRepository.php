<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Post> */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function publicQueryBuilder(?string $q = null, ?string $category = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->where('p.status = :status')->setParameter('status', 'published')
            ->orderBy('p.publishedAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.title LIKE :q OR p.content LIKE :q OR p.excerpt LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($category) {
            $qb->andWhere('p.category = :cat')->setParameter('cat', $category);
        }
        return $qb;
    }

    public function authorQueryBuilder(User $author, ?string $q = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.user = :author')->setParameter('author', $author)
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.title LIKE :q')->setParameter('q', '%' . $q . '%');
        }
        return $qb;
    }

    public function adminQueryBuilder(?string $q = null, ?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->orderBy('p.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('p.title LIKE :q OR u.name LIKE :q')->setParameter('q', '%' . $q . '%');
        }
        if ($status) {
            $qb->andWhere('p.status = :status')->setParameter('status', $status);
        }
        return $qb;
    }
}
