<?php

namespace App\Controller;

use App\Repository\PostRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(PostRepository $postRepo, ProductRepository $productRepo): Response
    {
        $latestPosts    = $postRepo->publicQueryBuilder()->setMaxResults(3)->getQuery()->getResult();
        $latestProducts = array_slice(
            $productRepo->findApprovedCatalog(null, null, 'newest'),
            0,
            4
        );

        return $this->render('home/index.html.twig', [
            'latestPosts'    => $latestPosts,
            'latestProducts' => $latestProducts,
        ]);
    }
}
