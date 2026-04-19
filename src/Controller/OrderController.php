<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\CurrencyConverterService;
use App\Service\EmailService;
use App\Service\OrderStatusService;
use App\Service\PdfService;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market/orders')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_index', methods: ['GET'])]
    public function index(OrderRepository $repo, CurrencyConverterService $currencyConverter): Response
    {
        $role = $this->isGranted('ROLE_ADMIN') ? 'admin' : 'customer';

        $myOrders  = $repo->listQueryBuilder($this->getUser(), 'customer')->getQuery()->getResult();
        $mySales   = $this->isGranted('ROLE_ADMIN')
            ? $repo->listQueryBuilder($this->getUser(), 'admin')->getQuery()->getResult()
            : $repo->listQueryBuilder($this->getUser(), 'seller')->getQuery()->getResult();
        $orderConversions = [];

        foreach (array_merge($myOrders, $mySales) as $order) {
            $orderConversions[$order->getId()] = [
                'unitPrice' => $currencyConverter->convertAmount($order->getUnitPrice()),
                'totalPrice' => $currencyConverter->convertAmount($order->getTotalPrice()),
            ];
        }

        return $this->render('market/orders.html.twig', [
            'myOrders' => $myOrders,
            'mySales'  => $mySales,
            'orderConversions' => $orderConversions,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(Order $order, OrderStatusService $orderStatusService, CurrencyConverterService $currencyConverter): Response
    {
        $this->denyUnlessOrderVisible($order);

        return $this->render('market/order_show.html.twig', [
            'order' => $order,
            'availableStatuses' => $orderStatusService->getSelectableStatuses($order),
            'convertedUnitPrice' => $currencyConverter->convertAmount($order->getUnitPrice()),
            'convertedTotalPrice' => $currencyConverter->convertAmount($order->getTotalPrice()),
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

    #[Route('/{id}/pay', name: 'order_pay', methods: ['POST'])]
    public function pay(Order $order, Request $request, EntityManagerInterface $em, StripeCheckoutService $stripeCheckoutService): Response
    {
        if ($order->getCustomer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('order_pay_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        if ($order->getPaymentMethod() !== Order::PAYMENT_METHOD_STRIPE) {
            $this->addFlash('error', 'This order is not configured for Stripe payment.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        if ($order->getPaymentStatus() === Order::PAYMENT_STATUS_PAID) {
            $this->addFlash('success', 'This order has already been paid.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        if ($order->getStatus() === Order::STATUS_CANCELLED) {
            $this->addFlash('error', 'Cancelled orders cannot be paid.');
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $session = $stripeCheckoutService->createCheckoutSession([$order], $request);

        if (!$session['success']) {
            $this->addFlash('error', $session['message']);
            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        $order->setStripeSessionId($session['sessionId']);
        $order->setPaymentStatus(Order::PAYMENT_STATUS_PENDING);
        $order->setUpdatedAt(new \DateTime());
        $em->flush();

        return new RedirectResponse($session['checkoutUrl']);
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
