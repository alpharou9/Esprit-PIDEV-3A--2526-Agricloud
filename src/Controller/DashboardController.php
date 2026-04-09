<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_FARMER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(ProductRepository $productRepository, OrderRepository $orderRepository): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'stats' => [
                'products' => $productRepository->count([]),
                'approved_products' => $productRepository->countByStatus('approved'),
                'low_stock_products' => $productRepository->countLowStock(5),
                'orders' => $orderRepository->count([]),
                'pending_orders' => $orderRepository->countByStatus('pending'),
                'delivered_revenue' => $orderRepository->getDeliveredRevenue(),
            ],
        ]);
    }
}
