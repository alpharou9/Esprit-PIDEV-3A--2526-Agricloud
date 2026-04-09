<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_index', methods: ['GET'])]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $orders = $query !== '' ? $orderRepository->search($query) : $orderRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'q' => $query,
        ]);
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            $order->setOrderDate($now);
            $order->setCreatedAt($now);
            $order->setUpdatedAt($now);
            $this->synchronizeOrderTotals($order);

            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order created successfully.');

            return $this->redirectToRoute('order_index');
        }

        return $this->render('order/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    public function edit(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUpdatedAt(new \DateTimeImmutable());
            $this->synchronizeOrderTotals($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order updated successfully.');

            return $this->redirectToRoute('order_index');
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'order_delete', methods: ['POST'])]
    public function delete(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_order_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('order_index');
        }

        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', 'Order deleted.');

        return $this->redirectToRoute('order_index');
    }

    private function synchronizeOrderTotals(Order $order): void
    {
        $quantity = (int) $order->getQuantity();
        $unitPrice = (float) $order->getUnitPrice();

        $order->setTotalPrice(number_format($quantity * $unitPrice, 2, '.', ''));

        if ($order->getStatus() === 'cancelled') {
            $order->setCancelledAt(new \DateTimeImmutable());
        } else {
            $order->setCancelledAt(null);
            $order->setCancelledReason(null);
        }
    }
}
