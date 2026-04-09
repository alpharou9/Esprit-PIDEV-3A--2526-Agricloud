<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products')]
#[IsGranted('ROLE_USER')]
class ProductController extends AbstractController
{
    #[Route('', name: 'product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $products = $query !== '' ? $productRepository->search($query) : $productRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'q' => $query,
        ]);
    }

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            if ($product->getViews() === null) {
                $product->setViews(0);
            }

            $product->setCreatedAt($now);
            $product->setUpdatedAt($now);
            $this->syncProductApproval($product);

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdatedAt(new \DateTimeImmutable());
            $this->syncProductApproval($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('product_index');
        }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('product_index');
    }

    private function syncProductApproval(Product $product): void
    {
        if ($product->getStatus() === 'approved') {
            $product->setApprovedAt(new \DateTimeImmutable());

            $currentUser = $this->getUser();
            if ($currentUser instanceof User && $currentUser->getId() !== null) {
                $product->setApprovedBy($currentUser->getId());
            }

            return;
        }

        $product->setApprovedAt(null);
        $product->setApprovedBy(null);
    }
}
