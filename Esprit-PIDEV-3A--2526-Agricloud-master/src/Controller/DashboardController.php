<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Service\SalesInsightsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(
        UserRepository $userRepo,
        RoleRepository $roleRepo,
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        SalesInsightsService $salesInsightsService
    ): Response
    {
        $statusData = $userRepo->countByStatus();
        $roleData   = $userRepo->countByRole();
        $salesInsights = $this->isGranted('ROLE_ADMIN') ? $salesInsightsService->generateInsights() : [];

        return $this->render('dashboard/index.html.twig', [
            'userCount'    => $userRepo->count([]),
            'roleCount'    => $roleRepo->count([]),
            'activeCount'  => $userRepo->count(['status' => 'active']),
            'blockedCount' => $userRepo->count(['status' => 'blocked']),
            'recentUsers'  => $userRepo->findRecent(5),
            'statusLabels' => json_encode(array_column($statusData, 'status')),
            'statusValues' => json_encode(array_column($statusData, 'cnt')),
            'roleLabels'   => json_encode(array_column($roleData, 'roleName')),
            'roleValues'   => json_encode(array_column($roleData, 'cnt')),
            'salesInsightCount' => count($salesInsights),
            'salesInsights' => $salesInsights,
            'salesOrderCount' => $orderRepo->count([]),
            'salesProductCount' => $productRepo->count([]),
        ]);
    }
}
