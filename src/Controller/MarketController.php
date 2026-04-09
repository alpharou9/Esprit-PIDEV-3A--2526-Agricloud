<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ProductRepository;
use App\Repository\ShoppingCartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market')]
class MarketController extends AbstractController
{
    #[Route('', name: 'market_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        ShoppingCartRepository $shoppingCartRepository
    ): Response {
        $query = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $sort = trim((string) $request->query->get('sort', 'newest'));

        $products = $productRepository->findApprovedCatalog(
            $query !== '' ? $query : null,
            $category !== '' ? $category : null,
            $sort !== '' ? $sort : 'newest'
        );

        $cartCount = 0;
        $user = $this->getUser();
        if ($user instanceof User) {
            $cartCount = count($shoppingCartRepository->findCartForUser($user));
        }

        return $this->render('market/index.html.twig', [
            'products' => $products,
            'q' => $query,
            'selectedCategory' => $category,
            'selectedSort' => $sort !== '' ? $sort : 'newest',
            'categories' => $productRepository->findAvailableCategories(),
            'sortOptions' => [
                'newest' => 'Newest first',
                'name_asc' => 'Name A-Z',
                'name_desc' => 'Name Z-A',
                'price_asc' => 'Price low to high',
                'price_desc' => 'Price high to low',
                'stock_desc' => 'Most stock',
                'stock_asc' => 'Low stock first',
            ],
            'cartCount' => $cartCount,
        ]);
    }

    #[Route('/{id}', name: 'market_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        ProductRepository $productRepository,
        ShoppingCartRepository $shoppingCartRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $product = $productRepository->findApprovedVisibleById($id);

        if ($product === null) {
            throw $this->createNotFoundException('This product is not available.');
        }

        $product->setViews(((int) $product->getViews()) + 1);
        $product->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $cartCount = 0;
        $user = $this->getUser();
        if ($user instanceof User) {
            $cartCount = count($shoppingCartRepository->findCartForUser($user));
        }

        return $this->render('market/show.html.twig', [
            'product' => $product,
            'cartCount' => $cartCount,
        ]);
    }
}
