<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SecurityController extends AbstractController
{
    // Changed path to '/' so this is the entry point of your website
    #[Route('/', name: 'app_choice')]
    public function index(): Response
    {
        return $this->render('security/choice.html.twig');
    }

    // We can also add temporary routes for the Farmer and Admin homepages 
    // to prevent "Route not found" errors while we build them.
    
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function adminDashboard(): Response
    {
        // For now, redirect to the Farm CRUD index we generated
        return $this->redirectToRoute('app_farm_index');
    }

    #[Route('/farmer/home', name: 'farmer_home')]
    public function farmerHome(): Response
    {
        // For now, redirect to the Farm CRUD index we generated
        return $this->redirectToRoute('app_farm_index');
    }
}