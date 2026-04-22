<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
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

        $order->setStatus('cancelled');
        $order->setCancelledAt(new \DateTimeImmutable());
        $order->setCancelledReason('Cancelled by customer.');
        $order->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Your order has been cancelled.');

        return $this->redirectToRoute('customer_order_show', ['id' => $order->getId()]);
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
}
