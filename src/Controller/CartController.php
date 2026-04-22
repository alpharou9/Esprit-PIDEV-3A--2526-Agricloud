<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ShoppingCart;
use App\Entity\User;
use App\Repository\ShoppingCartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_CUSTOMER')]
class CartController extends AbstractController
{
    #[Route('', name: 'cart_index', methods: ['GET'])]
    public function index(ShoppingCartRepository $shoppingCartRepository): Response
    {
        $user = $this->getAuthenticatedUser();
        $items = $shoppingCartRepository->findCartForUser($user);

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'cartTotal' => $shoppingCartRepository->getCartTotal($user),
        ]);
    }

    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        Product $product,
        Request $request,
        ShoppingCartRepository $shoppingCartRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');
            return $this->redirectToRoute('market_index');
        }

        if ($product->getStatus() !== 'approved' || (int) $product->getQuantity() <= 0) {
            $this->addFlash('error', 'This product is not available right now.');
            return $this->redirectToRoute('market_index');
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $quantity = min($quantity, (int) $product->getQuantity());

        $user = $this->getAuthenticatedUser();
        $cartItem = $shoppingCartRepository->findOneByUserAndProduct($user, $product) ?? new ShoppingCart();
        $now = new \DateTimeImmutable();

        if ($cartItem->getId() === null) {
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setCreatedAt($now);
            $entityManager->persist($cartItem);
        }

        $newQuantity = min(((int) $cartItem->getQuantity()) + $quantity, (int) $product->getQuantity());
        $cartItem->setQuantity($newQuantity);
        $cartItem->setUpdatedAt($now);

        $entityManager->flush();

        $this->addFlash('success', 'Product added to cart.');
        return $this->redirectToRoute('market_index');
    }

    #[Route('/{id}/update', name: 'cart_update', methods: ['POST'])]
    public function update(ShoppingCart $shoppingCart, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCartOwner($shoppingCart);

        if (!$this->isCsrfTokenValid('cart_update_' . $shoppingCart->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');
            return $this->redirectToRoute('cart_index');
        }

        $maxQuantity = (int) ($shoppingCart->getProduct()?->getQuantity() ?? 1);
        $quantity = max(1, min((int) $request->request->get('quantity', 1), max(1, $maxQuantity)));

        $shoppingCart->setQuantity($quantity);
        $shoppingCart->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Cart updated.');
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/{id}/remove', name: 'cart_remove', methods: ['POST'])]
    public function remove(ShoppingCart $shoppingCart, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCartOwner($shoppingCart);

        if (!$this->isCsrfTokenValid('cart_remove_' . $shoppingCart->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');
            return $this->redirectToRoute('cart_index');
        }

        $entityManager->remove($shoppingCart);
        $entityManager->flush();

        $this->addFlash('success', 'Item removed from cart.');
        return $this->redirectToRoute('cart_index');
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        return $user;
    }

    private function denyAccessUnlessCartOwner(ShoppingCart $shoppingCart): void
    {
        $user = $this->getAuthenticatedUser();
        if ($shoppingCart->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own cart.');
        }
    }
}
