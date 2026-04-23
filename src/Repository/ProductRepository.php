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

    public function marketplaceQueryBuilder(?string $q = null, ?string $category = null, ?string $sort = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')->addSelect('u')
            ->where('p.status = :status')->setParameter('status', 'approved');

        if ($q) {
            $qb->andWhere('p.name LIKE :q OR p.description LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($category) {
            $qb->andWhere('p.category = :cat')->setParameter('cat', $category);
        }

        switch ($sort) {
            case 'name_asc':
                $qb->orderBy('p.name', 'ASC')
                    ->addOrderBy('p.createdAt', 'DESC');
                break;
            case 'name_desc':
                $qb->orderBy('p.name', 'DESC')
                    ->addOrderBy('p.createdAt', 'DESC');
                break;
            case 'price_asc':
                $qb->orderBy('p.price', 'ASC')
                    ->addOrderBy('p.name', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('p.price', 'DESC')
                    ->addOrderBy('p.name', 'ASC');
                break;
            case 'newest':
                $qb->orderBy('p.createdAt', 'DESC')
                    ->addOrderBy('p.quantity', 'DESC');
                break;
            default:
                $qb->orderBy('p.quantity', 'DESC')
                    ->addOrderBy('p.createdAt', 'DESC');
                break;
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

    public function findBestMarketplaceMatchForTerms(array $terms): ?Product
    {
        $terms = array_values(array_unique(array_filter(array_map(static function ($term) {
            $normalized = mb_strtolower(trim((string) $term));

            return $normalized !== '' ? $normalized : null;
        }, $terms))));

        if ($terms === []) {
            return null;
        }

        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->andWhere('p.quantity > 0')
            ->setParameter('status', 'approved')
            ->setMaxResults(12);

        $orX = $qb->expr()->orX();
        foreach ($terms as $index => $term) {
            $orX->add(sprintf('LOWER(p.name) LIKE :term_%d', $index));
            $qb->setParameter(sprintf('term_%d', $index), '%' . $term . '%');
        }

        $products = $qb
            ->andWhere($orX)
            ->orderBy('p.quantity', 'DESC')
            ->addOrderBy('p.views', 'DESC')
            ->getQuery()
            ->getResult();

        if ($products === []) {
            return null;
        }

        usort($products, function (Product $left, Product $right) use ($terms): int {
            return $this->scoreRecipeMatch($right, $terms) <=> $this->scoreRecipeMatch($left, $terms);
        });

        return $products[0] ?? null;
    }

    private function scoreRecipeMatch(Product $product, array $terms): int
    {
        $name = mb_strtolower($product->getName());
        $score = 0;

        foreach ($terms as $term) {
            if ($name === $term) {
                $score += 100;
                continue;
            }

            if (str_starts_with($name, $term)) {
                $score += 60;
                continue;
            }

            if (str_contains($name, $term)) {
                $score += 30;
            }
        }

        return $score + min($product->getQuantity(), 20);
    }
}
