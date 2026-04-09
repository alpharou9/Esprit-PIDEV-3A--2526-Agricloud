<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_FARMER')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_index', methods: ['GET'])]
    public function index(
        Request $request,
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        UserRepository $userRepository
    ): Response
    {
        $currentUser = $this->getAuthenticatedUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $query = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));
        $productId = $request->query->getInt('product');
        $customerId = $request->query->getInt('customer');
        $sellerId = $isAdmin ? $request->query->getInt('seller') : (int) $currentUser->getId();
        $sort = trim((string) $request->query->get('sort', 'newest'));

        $orders = $orderRepository->findByFilters(
            $query !== '' ? $query : null,
            $productId > 0 ? $productId : null,
            $customerId > 0 ? $customerId : null,
            $sellerId > 0 ? $sellerId : null,
            $status !== '' ? $status : null,
            $sort !== '' ? $sort : 'newest'
        );

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'q' => $query,
            'selectedStatus' => $status,
            'selectedProduct' => $productId > 0 ? $productId : null,
            'selectedCustomer' => $customerId > 0 ? $customerId : null,
            'selectedSeller' => $sellerId > 0 ? $sellerId : null,
            'selectedSort' => $sort !== '' ? $sort : 'newest',
            'isAdmin' => $isAdmin,
            'products' => $productRepository->findBy([], ['name' => 'ASC']),
            'customers' => $userRepository->findBy([], ['name' => 'ASC']),
            'sellers' => $isAdmin ? $userRepository->findBy([], ['name' => 'ASC']) : [$currentUser],
            'statuses' => [
                'pending' => 'Pending',
                'confirmed' => 'Confirmed',
                'processing' => 'Processing',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled',
            ],
            'sortOptions' => [
                'newest' => 'Newest first',
                'oldest' => 'Oldest first',
                'total_asc' => 'Total low to high',
                'total_desc' => 'Total high to low',
                'status_asc' => 'Status A-Z',
                'status_desc' => 'Status Z-A',
                'customer_asc' => 'Customer A-Z',
                'seller_asc' => 'Seller A-Z',
            ],
        ]);
    }

    #[Route('/new', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only admins can create orders from the backoffice.');
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();

            $order->setOrderDate($now);
            $order->setCreatedAt($now);
            $order->setUpdatedAt($now);
            $this->synchronizeOrderProductData($order);
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
        $this->denyAccessUnlessCanManageOrder($order, $this->getAuthenticatedUser());
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUpdatedAt(new \DateTimeImmutable());
            $this->synchronizeOrderProductData($order);
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
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only admins can delete orders.');
        }

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

    private function synchronizeOrderProductData(Order $order): void
    {
        $product = $order->getProduct();
        if ($product === null) {
            return;
        }

        $seller = $product->getUser();
        if ($seller === null) {
            throw new \LogicException('Selected product has no seller assigned.');
        }

        $order->setSeller($seller);
        $order->setUnitPrice((string) $product->getPrice());
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        return $user;
    }

    private function denyAccessUnlessCanManageOrder(Order $order, User $user): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($order->getSeller()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only manage orders linked to your products.');
        }
    }
}
