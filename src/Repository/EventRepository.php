<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Event> */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function publicQueryBuilder(?string $q = null, ?string $category = null, ?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')->addSelect('u')
            ->orderBy('e.eventDate', 'ASC');

        if ($q) {
            $qb->andWhere('e.title LIKE :q OR e.description LIKE :q OR e.location LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($category) {
            $qb->andWhere('e.category = :cat')->setParameter('cat', $category);
        }
        if ($status) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }
        return $qb;
    }

    public function organizerQueryBuilder(User $user, ?string $q = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.user = :user')->setParameter('user', $user)
            ->orderBy('e.eventDate', 'DESC');

        if ($q) {
            $qb->andWhere('e.title LIKE :q')->setParameter('q', '%' . $q . '%');
        }
        return $qb;
    }

    public function adminQueryBuilder(?string $q = null, ?string $status = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')->addSelect('u')
            ->orderBy('e.createdAt', 'DESC');

        if ($q) {
            $qb->andWhere('e.title LIKE :q OR u.name LIKE :q')->setParameter('q', '%' . $q . '%');
        }
        if ($status) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }
        return $qb;
    }

    /**
     * @return Event[]
     */
    public function findBetweenDates(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')->addSelect('u')
            ->andWhere('e.eventDate >= :start')
            ->andWhere('e.eventDate < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Event[]
     */
    public function findRecommendationCandidates(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')->addSelect('u')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('statuses', ['upcoming', 'ongoing'])
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
