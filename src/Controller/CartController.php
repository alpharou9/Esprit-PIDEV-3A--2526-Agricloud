<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ShoppingCart;
use App\Entity\User;
use App\Form\AddToCartType;
use App\Form\CartQuantityType;
use App\Form\CheckoutType;
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
        $updateForms = [];

        foreach ($items as $item) {
            $updateForms[$item->getId()] = $this->createForm(CartQuantityType::class, null, [
                'action' => $this->generateUrl('cart_update', ['id' => $item->getId()]),
                'max_quantity' => max(1, (int) ($item->getProduct()?->getQuantity() ?? 1)),
                'current_quantity' => max(1, (int) $item->getQuantity()),
            ])->createView();
        }

        return $this->render('market/customer/cart.html.twig', [
            'items' => $items,
            'cartTotal' => $shoppingCartRepository->getCartTotal($user),
            'updateForms' => $updateForms,
        ]);
    }

    #[Route('/checkout', name: 'cart_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        ShoppingCartRepository $shoppingCartRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getAuthenticatedUser();
        $items = $shoppingCartRepository->findCartForUser($user);

        if ($items === []) {
            $this->addFlash('error', 'Your cart is empty.');

            return $this->redirectToRoute('cart_index');
        }

        $form = $this->createForm(CheckoutType::class, [
            'shippingEmail' => $user->getEmail(),
            'shippingPhone' => $user->getPhone(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $checkoutData = $form->getData();
            $now = new \DateTimeImmutable();

            foreach ($items as $item) {
                $product = $item->getProduct();

                if ($product === null || $product->getStatus() !== 'approved' || (int) $product->getQuantity() <= 0) {
                    $this->addFlash('error', 'One of the products in your cart is no longer available.');

                    return $this->redirectToRoute('cart_index');
                }

                if ($product->getUser() === null) {
                    $this->addFlash('error', 'One of the products in your cart has no seller assigned.');

                    return $this->redirectToRoute('cart_index');
                }

                if ((int) $item->getQuantity() > (int) $product->getQuantity()) {
                    $this->addFlash('error', sprintf('The quantity for "%s" is no longer available. Please update your cart.', $product->getName()));

                    return $this->redirectToRoute('cart_index');
                }

                $order = new Order();
                $order
                    ->setCustomer($user)
                    ->setProduct($product)
                    ->setSeller($product->getUser())
                    ->setQuantity((int) $item->getQuantity())
                    ->setUnitPrice((string) $product->getPrice())
                    ->setTotalPrice(number_format(((float) $product->getPrice()) * (int) $item->getQuantity(), 2, '.', ''))
                    ->setStatus('pending')
                    ->setShippingAddress((string) $checkoutData['shippingAddress'])
                    ->setShippingCity($checkoutData['shippingCity'] ?: null)
                    ->setShippingPostal($checkoutData['shippingPostal'] ?: null)
                    ->setShippingEmail($checkoutData['shippingEmail'] ?: null)
                    ->setShippingPhone($checkoutData['shippingPhone'] ?: null)
                    ->setDeliveryDate($checkoutData['deliveryDate'] ?? null)
                    ->setNotes($checkoutData['notes'] ?: null)
                    ->setOrderDate($now)
                    ->setCreatedAt($now)
                    ->setUpdatedAt($now)
                ;

                $entityManager->persist($order);

                $remainingQuantity = max(0, (int) $product->getQuantity() - (int) $item->getQuantity());
                $product->setQuantity($remainingQuantity);
                $product->setUpdatedAt($now);
                $product->setStatus($remainingQuantity > 0 ? 'approved' : 'sold_out');

                $entityManager->remove($item);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Your order has been placed successfully.');

            return $this->redirectToRoute('customer_order_index');
        }

        return $this->render('market/customer/checkout.html.twig', [
            'items' => $items,
            'cartTotal' => $shoppingCartRepository->getCartTotal($user),
            'checkoutForm' => $form->createView(),
        ]);
    }

    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(
        Product $product,
        Request $request,
        ShoppingCartRepository $shoppingCartRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(AddToCartType::class, null, [
            'max_quantity' => max(1, (int) $product->getQuantity()),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Invalid request.');
            return $this->redirectToRoute('market_index');
        }

        if ($product->getStatus() !== 'approved' || (int) $product->getQuantity() <= 0) {
            $this->addFlash('error', 'This product is not available right now.');
            return $this->redirectToRoute('market_index');
        }

        $formData = $form->getData();
        $quantity = max(1, (int) ($formData['quantity'] ?? 1));
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

        $maxQuantity = max(1, (int) ($shoppingCart->getProduct()?->getQuantity() ?? 1));
        $form = $this->createForm(CartQuantityType::class, null, [
            'max_quantity' => $maxQuantity,
            'current_quantity' => max(1, (int) $shoppingCart->getQuantity()),
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Invalid request.');
            return $this->redirectToRoute('cart_index');
        }

        $formData = $form->getData();
        $quantity = max(1, min((int) ($formData['quantity'] ?? 1), $maxQuantity));

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
