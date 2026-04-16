<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\EmailService;
use App\Service\OrderStatusService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
    public function show(Order $order, OrderStatusService $orderStatusService): Response
    {
        $this->denyUnlessOrderVisible($order);

        return $this->render('market/order_show.html.twig', [
            'order' => $order,
            'availableStatuses' => $orderStatusService->getSelectableStatuses($order),
        ]);
    }

    #[Route('/{id}/pdf', name: 'order_export_pdf', methods: ['GET'])]
    public function exportPdf(Order $order, PdfService $pdfService): Response
    {
        $this->denyUnlessOrderVisible($order);

        $response = new Response($pdfService->generateOrderPdf($order));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('order-%d.pdf', $order->getId())
            )
        );

        return $response;
    }

    #[Route('/{id}/status', name: 'order_status', methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em, EmailService $emailService, OrderStatusService $orderStatusService): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $order->getSeller() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $status = (string) $request->request->get('status');
        $result = $orderStatusService->applyStatusChange($order, $status, $request->request->get('reason'));

        if (!$result['success']) {
            $this->addFlash('error', $result['message']);
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $em->flush();

        if ($result['confirmedJustNow']) {
            $emailResult = $emailService->sendOrderConfirmedEmail($order);
            $this->addFlash($emailResult['sent'] ? 'success' : 'warning', $emailResult['message']);
        } else {
            $this->addFlash('success', $result['message']);
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/cancel', name: 'order_cancel', methods: ['POST'])]
    public function cancel(Order $order, Request $request, EntityManagerInterface $em, OrderStatusService $orderStatusService): Response
    {
        if ($order->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('order_cancel_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $result = $orderStatusService->applyStatusChange($order, Order::STATUS_CANCELLED, 'Cancelled by customer');

        if (!$result['success']) {
            $this->addFlash('error', $result['message']);
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $em->flush();

        $this->addFlash('success', 'Order cancelled.');
        return $this->redirectToRoute('order_index');
    }

    private function denyUnlessOrderVisible(Order $order): void
    {
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')
            && $order->getCustomer() !== $user
            && $order->getSeller() !== $user) {
            throw $this->createAccessDeniedException();
        }
    }
}
