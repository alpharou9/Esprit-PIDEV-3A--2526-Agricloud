<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AddToCartType;
use App\Form\MarketFilterType;
use App\Repository\ProductRepository;
use App\Repository\ShoppingCartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/market')]
class CustomerMarketController extends AbstractController
{
    #[Route('', name: 'market_index', methods: ['GET'])]
    public function index(
        Request $request,
        ProductRepository $productRepository,
        ShoppingCartRepository $shoppingCartRepository
    ): Response {
        $categories = $productRepository->findAvailableCategories();
        $sortOptions = [
            'Newest first' => 'newest',
            'Name A-Z' => 'name_asc',
            'Name Z-A' => 'name_desc',
            'Price low to high' => 'price_asc',
            'Price high to low' => 'price_desc',
            'Most stock' => 'stock_desc',
            'Low stock first' => 'stock_asc',
        ];

        $filterForm = $this->createForm(MarketFilterType::class, [
            'q' => trim((string) $request->query->get('q', '')),
            'category' => trim((string) $request->query->get('category', '')),
            'sort' => trim((string) $request->query->get('sort', 'newest')) ?: 'newest',
        ], [
            'category_choices' => array_combine($categories, $categories) ?: [],
            'sort_choices' => $sortOptions,
        ]);
        $filterForm->handleRequest($request);

        $filterData = $filterForm->getData();
        $query = trim((string) ($filterData['q'] ?? ''));
        $category = trim((string) ($filterData['category'] ?? ''));
        $sort = trim((string) ($filterData['sort'] ?? 'newest')) ?: 'newest';

        $products = $productRepository->findApprovedCatalog(
            $query !== '' ? $query : null,
            $category !== '' ? $category : null,
            $sort
        );

        $cartCount = 0;
        $addCartForms = [];
        $user = $this->getUser();

        if ($user instanceof User) {
            $cartCount = count($shoppingCartRepository->findCartForUser($user));
        }

        if ($this->isGranted('ROLE_CUSTOMER')) {
            foreach ($products as $product) {
                $addCartForms[$product->getId()] = $this->createForm(AddToCartType::class, null, [
                    'action' => $this->generateUrl('cart_add', ['id' => $product->getId()]),
                    'max_quantity' => max(1, (int) $product->getQuantity()),
                ])->createView();
            }
        }

        return $this->render('market/customer/index.html.twig', [
            'products' => $products,
            'filterForm' => $filterForm->createView(),
            'addCartForms' => $addCartForms,
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
        $addCartForm = null;
        $user = $this->getUser();

        if ($user instanceof User) {
            $cartCount = count($shoppingCartRepository->findCartForUser($user));
        }

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $addCartForm = $this->createForm(AddToCartType::class, null, [
                'action' => $this->generateUrl('cart_add', ['id' => $product->getId()]),
                'max_quantity' => max(1, (int) $product->getQuantity()),
            ])->createView();
        }

        return $this->render('market/customer/show.html.twig', [
            'product' => $product,
            'cartCount' => $cartCount,
            'addCartForm' => $addCartForm,
        ]);
    }
}
