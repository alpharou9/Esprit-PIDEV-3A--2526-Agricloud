<?php

namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\FavoriteRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market/favorites')]
#[IsGranted('ROLE_USER')]
class FavoriteController extends AbstractController
{
    #[Route('', name: 'favorite_index', methods: ['GET'])]
    public function index(FavoriteRepository $favoriteRepository, ReviewRepository $reviewRepository): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $favorites = $favoriteRepository->findFavoritesForUser($user);
        $products = array_map(static fn (Favorite $favorite): ?Product => $favorite->getProduct(), $favorites);

        return $this->render('market/favorites.html.twig', [
            'favorites' => $favorites,
            'productReviewStats' => $reviewRepository->findStatsForProducts($products),
        ]);
    }

    #[Route('/toggle/{id}', name: 'favorite_toggle', methods: ['POST'])]
    public function toggle(
        Product $product,
        Request $request,
        FavoriteRepository $favoriteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('favorite_toggle_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid favorite action.');

            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        $existingFavorite = $favoriteRepository->findOneForUserAndProduct($user, $product);

        if ($existingFavorite instanceof Favorite) {
            $entityManager->remove($existingFavorite);
            $message = 'Product removed from your favorites.';
        } else {
            $favorite = (new Favorite())
                ->setUser($user)
                ->setProduct($product)
                ->setCreatedAt(new \DateTime());

            $entityManager->persist($favorite);
            $message = 'Product added to your favorites.';
        }

        $entityManager->flush();
        $this->addFlash('success', $message);

        $redirectRoute = (string) $request->request->get('redirect_route', 'product_show');
        $redirectParams = [];

        if ($redirectRoute === 'product_show') {
            $redirectParams['id'] = $product->getId();
        }

        return $this->redirectToRoute($redirectRoute, $redirectParams);
    }
}
