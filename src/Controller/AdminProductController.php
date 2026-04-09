<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    #[Route('/pending', name: 'admin_product_pending', methods: ['GET'])]
    public function pending(ProductRepository $productRepository): Response
    {
        return $this->render('market/admin/pending.html.twig', [
            'pendingProducts' => $productRepository->findByFilters(null, null, null, 'pending', 'newest'),
            'allProducts' => $productRepository->findByFilters(null, null, null, null, 'newest'),
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_product_approve', methods: ['POST'])]
    public function approve(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('approve_product_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('admin_product_pending');
        }

        $admin = $this->getAdminUser();
        $product->setStatus('approved');
        $product->setApprovedAt(new \DateTimeImmutable());
        $product->setApprovedBy($admin->getId());
        $product->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', sprintf('"%s" approved.', $product->getName()));

        return $this->redirectToRoute('admin_product_pending');
    }

    #[Route('/{id}/reject', name: 'admin_product_reject', methods: ['POST'])]
    public function reject(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('reject_product_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('admin_product_pending');
        }

        $product->setStatus('rejected');
        $product->setApprovedAt(null);
        $product->setApprovedBy(null);
        $product->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', sprintf('"%s" rejected.', $product->getName()));

        return $this->redirectToRoute('admin_product_pending');
    }

    private function getAdminUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->hasRole('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only admins can approve or reject products.');
        }

        return $user;
    }
}
