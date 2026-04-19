<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\CloudinaryService;
use App\Service\CurrencyConverterService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market')]
#[IsGranted('ROLE_USER')]
class ProductController extends AbstractController
{
    // ── Marketplace (all approved products) ──────────────────────
    #[Route('', name: 'market_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $repo, PaginatorInterface $paginator): Response
    {
        $q        = $request->query->get('q', '');
        $category = $request->query->get('category', '');
        $sort     = $request->query->get('sort', '');

        $pagination = $paginator->paginate(
            $repo->marketplaceQueryBuilder($q ?: null, $category ?: null, $sort ?: null),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('market/index.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'category'   => $category,
            'sort'       => $sort,
        ]);
    }

    // ── New product ───────────────────────────────────────────────
    #[Route('/product/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, CloudinaryService $cloudinaryService): Response
    {
        if (!$this->isGranted('ROLE_FARMER') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only farmers can list products for sale.');
            return $this->redirectToRoute('market_index');
        }
        $product = new Product();
        $form    = $this->createForm(ProductType::class, $product, [
            'current_user' => $this->getUser(),
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUser($this->getUser());
            $product->setStatus('pending');
            $product->setViews(0);
            $product->setCreatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $upload = $cloudinaryService->storeProductImage($imageFile);
                $product->setImage($upload['image']);

                if (($upload['warning'] ?? null) !== null) {
                    $this->addFlash('warning', $upload['warning']);
                }
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product submitted for approval.');
            return $this->redirectToRoute('my_products');
        }

        return $this->render('market/product_form.html.twig', ['form' => $form, 'product' => null]);
    }

    // ── Product detail ────────────────────────────────────────────
    #[Route('/product/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product, EntityManagerInterface $em, CurrencyConverterService $currencyConverter): Response
    {
        $product->setViews(($product->getViews() ?? 0) + 1);
        $em->flush();

        return $this->render('market/show.html.twig', [
            'product' => $product,
            'convertedPrice' => $currencyConverter->convertAmount($product->getPrice()),
        ]);
    }

    // ── My listings ───────────────────────────────────────────────
    #[Route('/my-products', name: 'my_products', methods: ['GET'])]
    public function myProducts(Request $request, ProductRepository $repo, PaginatorInterface $paginator): Response
    {
        $q = $request->query->get('q', '');

        $qb = $this->isGranted('ROLE_ADMIN')
            ? $repo->adminQueryBuilder($q ?: null, $request->query->get('status') ?: null)
            : $repo->sellerQueryBuilder($this->getUser(), $q ?: null);

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        return $this->render('market/my_products.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'status'     => $request->query->get('status', ''),
        ]);
    }

    // ── Edit product ──────────────────────────────────────────────
    #[Route('/product/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em, CloudinaryService $cloudinaryService): Response
    {
        if (!$this->isGranted('ROLE_FARMER') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Only farmers can edit products.');
            return $this->redirectToRoute('market_index');
        }
        if (!$this->isGranted('ROLE_ADMIN') && $product->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProductType::class, $product, [
            'current_user' => $this->getUser(),
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $upload = $cloudinaryService->storeProductImage($imageFile);
                $product->setImage($upload['image']);

                if (($upload['warning'] ?? null) !== null) {
                    $this->addFlash('warning', $upload['warning']);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Product updated.');
            return $this->redirectToRoute('my_products');
        }

        return $this->render('market/product_form.html.twig', ['form' => $form, 'product' => $product]);
    }

    // ── Delete product ────────────────────────────────────────────
    #[Route('/product/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em, OrderRepository $orderRepository, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($id);

        if ($product === null) {
            $this->addFlash('warning', 'This product was already removed or could not be found.');
            return $this->redirectToRoute('my_products');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $product->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('my_products');
        }

        if ($orderRepository->countForProduct($product) > 0) {
            $product->setStatus('sold_out');
            $product->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('warning', 'This product already has orders, so it was hidden from sale instead of being deleted.');
            return $this->redirectToRoute('my_products');
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted.');
        return $this->redirectToRoute('my_products');
    }

    // ── Admin: approve / reject ───────────────────────────────────
    #[Route('/product/{id}/approve', name: 'product_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve_product_' . $product->getId(), $request->request->get('_token'))) {
            $product->setStatus('approved');
            $product->setApprovedAt(new \DateTime());
            $product->setApprovedBy($this->getUser());
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
