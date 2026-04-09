<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\AddToCartType;
use App\Form\MarketFilterType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\ShoppingCartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/market')]
#[IsGranted('ROLE_USER')]
class ProductController extends AbstractController
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

    #[Route('/product/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException();
            }

            $product->setUser($user);
            $product->setStatus('pending');
            $product->setViews(0);
            $product->setCreatedAt(new \DateTime());
            $product->setUpdatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safe = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newName = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('products_upload_dir'), $newName);
                $product->setImage($newName);
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product submitted for approval.');

            return $this->redirectToRoute('my_products');
        }

        return $this->render('market/product_form.html.twig', [
            'form' => $form,
            'product' => null,
        ]);
    }

    #[Route('/product/{id}', name: 'product_show', methods: ['GET'])]
    public function show(
        Product $product,
        EntityManagerInterface $em,
        ShoppingCartRepository $shoppingCartRepository
    ): Response {
        if ($product->getStatus() !== 'approved' || (int) $product->getQuantity() <= 0) {
            throw $this->createNotFoundException('This product is not available.');
        }

        $product->setViews(($product->getViews() ?? 0) + 1);
        $product->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

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

    #[Route('/my-products', name: 'my_products', methods: ['GET'])]
    public function myProducts(): Response
    {
        return $this->redirectToRoute('product_index');
    }

    #[Route('/product/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $product->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safe = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newName = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('products_upload_dir'), $newName);
                $product->setImage($newName);
            }

            $em->flush();
            $this->addFlash('success', 'Product updated.');

            return $this->redirectToRoute('my_products');
        }

        return $this->render('market/product_form.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/product/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $product->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('my_products');
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('my_products');
    }

    #[Route('/product/{id}/approve', name: 'product_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve_product_' . $product->getId(), $request->request->get('_token'))) {
            $product->setStatus('approved');
            $product->setApprovedAt(new \DateTime());
            $product->setApprovedBy($this->getUser()?->getId());
            $em->flush();
            $this->addFlash('success', 'Product approved.');
        }

        return $this->redirectToRoute('my_products');
    }

    #[Route('/product/{id}/reject', name: 'product_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('reject_product_' . $product->getId(), $request->request->get('_token'))) {
            $product->setStatus('rejected');
            $product->setApprovedAt(null);
            $product->setApprovedBy(null);
            $em->flush();
            $this->addFlash('success', 'Product rejected.');
        }

        return $this->redirectToRoute('my_products');
    }
}
