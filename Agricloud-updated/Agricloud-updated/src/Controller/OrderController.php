<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market/orders')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_index', methods: ['GET'])]
    public function index(OrderRepository $repo): Response
    {
        $role = $this->isGranted('ROLE_ADMIN') ? 'admin' : 'customer';

        $myOrders  = $repo->listQueryBuilder($this->getUser(), 'customer')->getQuery()->getResult();
        $mySales   = $this->isGranted('ROLE_ADMIN')
            ? $repo->listQueryBuilder($this->getUser(), 'admin')->getQuery()->getResult()
            : $repo->listQueryBuilder($this->getUser(), 'seller')->getQuery()->getResult();

        return $this->render('market/orders.html.twig', [
            'myOrders' => $myOrders,
            'mySales'  => $mySales,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')
            && $order->getCustomer() !== $user
            && $order->getSeller() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('market/order_show.html.twig', ['order' => $order]);
    }

    #[Route('/{id}/status', name: 'order_status', methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $order->getSeller() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        $status  = $request->request->get('status');

        if (in_array($status, $allowed)) {
            $order->setStatus($status);
            $order->setUpdatedAt(new \DateTime());
            if ($status === 'cancelled') {
                $order->setCancelledAt(new \DateTime());
                $order->setCancelledReason($request->request->get('reason'));
            }
            $em->flush();
            $this->addFlash('success', 'Order status updated.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
    public function cancel(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if ($order->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('order_cancel_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        if (!in_array($order->getStatus(), ['pending', 'confirmed'])) {
            $this->addFlash('error', 'This order cannot be cancelled at this stage.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $order->setStatus('cancelled');
        $order->setCancelledAt(new \DateTime());
        $order->setCancelledReason('Cancelled by customer');
        $order->setUpdatedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Order cancelled.');
        return $this->redirectToRoute('order_index');
    }
}
