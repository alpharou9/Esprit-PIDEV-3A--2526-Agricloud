<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Form\CustomerOrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my-orders')]
#[IsGranted('ROLE_CUSTOMER')]
class MyOrderController extends AbstractController
{
    #[Route('', name: 'customer_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        $customer = $this->getAuthenticatedUser();

        return $this->render('customer_order/index.html.twig', [
            'orders' => $orderRepository->findForCustomer($customer),
        ]);
    }

    #[Route('/{id}', name: 'customer_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        $this->denyAccessUnlessCustomerOwnsOrder($order);

        return $this->render('customer_order/show.html.twig', [
            'order' => $order,
            'canManage' => $this->canCustomerManageOrder($order),
        ]);
    }

    #[Route('/{id}/edit', name: 'customer_order_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCustomerOwnsOrder($order);
        $this->denyAccessUnlessCustomerCanManageOrder($order);

        $originalQuantity = (int) $order->getQuantity();
        $product = $order->getProduct();
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found for this order.');
        }

        $form = $this->createForm(CustomerOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newQuantity = (int) $order->getQuantity();
            $maxAvailable = (int) $product->getQuantity() + $originalQuantity;

            if ($newQuantity > $maxAvailable) {
                $this->addFlash('error', sprintf('Only %d item(s) are available for this product.', $maxAvailable));

                return $this->redirectToRoute('customer_order_edit', ['id' => $order->getId()]);
            }

            $product->setQuantity($maxAvailable - $newQuantity);
            $product->setStatus($product->getQuantity() > 0 ? 'approved' : 'sold_out');
            $product->setUpdatedAt(new \DateTimeImmutable());

            $order->setTotalPrice(number_format($newQuantity * (float) $order->getUnitPrice(), 2, '.', ''));
            $order->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Your order has been updated.');

            return $this->redirectToRoute('customer_order_show', ['id' => $order->getId()]);
        }

        return $this->render('customer_order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/cancel', name: 'customer_order_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCustomerOwnsOrder($order);

        if (!$this->isCsrfTokenValid('cancel_order_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('customer_order_index');
        }

        if ($order->getStatus() !== 'pending') {
            $this->addFlash('error', 'Only pending orders can be cancelled.');

            return $this->redirectToRoute('customer_order_show', ['id' => $order->getId()]);
        }

        $this->restoreProductStock($order);
        $order->setStatus('cancelled');
        $order->setCancelledAt(new \DateTimeImmutable());
        $order->setCancelledReason('Cancelled by customer.');
        $order->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Your order has been cancelled.');

        return $this->redirectToRoute('customer_order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/delete', name: 'customer_order_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCustomerOwnsOrder($order);
        $this->denyAccessUnlessCustomerCanManageOrder($order);

        if (!$this->isCsrfTokenValid('delete_order_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('customer_order_index');
        }

        $this->restoreProductStock($order);
        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', 'Your order has been deleted.');

        return $this->redirectToRoute('customer_order_index');
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        return $user;
    }

    private function denyAccessUnlessCustomerOwnsOrder(Order $order): void
    {
        if ($order->getCustomer()?->getId() !== $this->getAuthenticatedUser()->getId()) {
            throw $this->createAccessDeniedException('You can only access your own orders.');
        }
    }

    private function canCustomerManageOrder(Order $order): bool
    {
        return $order->getStatus() === 'pending';
    }

    private function denyAccessUnlessCustomerCanManageOrder(Order $order): void
    {
        if (!$this->canCustomerManageOrder($order)) {
            throw $this->createAccessDeniedException('Only pending orders can be edited or deleted.');
        }
    }

    private function restoreProductStock(Order $order): void
    {
        $product = $order->getProduct();
        if (!$product instanceof Product) {
            return;
        }

        $product->setQuantity((int) $product->getQuantity() + (int) $order->getQuantity());
        if ($product->getStatus() === 'sold_out' || $product->getStatus() === 'approved') {
            $product->setStatus('approved');
        }
        $product->setUpdatedAt(new \DateTimeImmutable());
    }
}
