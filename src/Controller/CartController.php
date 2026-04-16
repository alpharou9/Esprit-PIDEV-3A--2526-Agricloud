<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Form\CheckoutType;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('', name: 'cart_index', methods: ['GET'])]
    public function index(CartItemRepository $cartRepo): Response
    {
        $items = $cartRepo->findByUser($this->getUser());
        $total = array_sum(array_map(fn($i) => $i->getSubtotal(), $items));

        return $this->render('market/cart.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, EntityManagerInterface $em, CartItemRepository $cartRepo): Response
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid token.');
            return $this->redirectToRoute('market_index');
        }

        if ($product->getStatus() !== 'approved' || $product->getQuantity() <= 0) {
            $this->addFlash('error', 'Product not available.');
            return $this->redirectToRoute('market_index');
        }

        $qty = max(1, (int) $request->request->get('qty', 1));

        $existing    = $cartRepo->findOneBy(['user' => $this->getUser(), 'product' => $product]);
        $alreadyInCart = $existing ? $existing->getQuantity() : 0;
        $newTotal      = $alreadyInCart + $qty;

        if ($newTotal > $product->getQuantity()) {
            $available = $product->getQuantity() - $alreadyInCart;
            if ($available <= 0) {
                $this->addFlash('error', 'You already have the maximum available quantity in your cart.');
            } else {
                $this->addFlash('error', "Only {$available} more unit(s) can be added (stock limit).");
            }
            return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
        }

        if ($existing) {
            $existing->setQuantity($newTotal);
            $existing->setUpdatedAt(new \DateTime());
        } else {
            $item = new CartItem();
            $item->setUser($this->getUser());
            $item->setProduct($product);
            $item->setQuantity($qty);
            $item->setCreatedAt(new \DateTime());
            $em->persist($item);
        }

        $em->flush();
        $this->addFlash('success', 'Added to cart.');
        return $this->redirectToRoute('product_show', ['id' => $product->getId()]);
    }

    #[Route('/remove/{id}', name: 'cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem, Request $request, EntityManagerInterface $em): Response
    {
        if ($cartItem->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('cart_remove_' . $cartItem->getId(), $request->request->get('_token'))) {
            $em->remove($cartItem);
            $em->flush();
            $this->addFlash('success', 'Item removed from cart.');
        }

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/checkout', name: 'cart_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, EntityManagerInterface $em, CartItemRepository $cartRepo): Response
    {
        $items = $cartRepo->findByUser($this->getUser());

        if (empty($items)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('market_index');
        }

        $total = array_sum(array_map(fn($i) => $i->getSubtotal(), $items));
        $form  = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $now  = new \DateTime();

            foreach ($items as $item) {
                $product = $item->getProduct();

                if (!$this->isProductSellable($product)) {
                    $this->addFlash('error', "Product '{$product->getName()}' is no longer available for sale.");
                    return $this->redirectToRoute('cart_index');
                }

                if ($item->getQuantity() > $product->getQuantity()) {
                    $this->addFlash('error', "Not enough stock for '{$product->getName()}'.");
                    return $this->redirectToRoute('cart_index');
                }

                $order = new Order();
                $order->setCustomer($this->getUser());
                $order->setProduct($product);
                $order->setSeller($product->getUser());
                $order->setQuantity($item->getQuantity());
                $order->setUnitPrice($product->getPrice());
                $order->setTotalPrice((string)($product->getPrice() * $item->getQuantity()));
                $order->setStatus('pending');
                $order->setShippingAddress($data['shippingAddress']);
                $order->setShippingCity($data['shippingCity']);
                $order->setShippingPostal($data['shippingPostal'] ?? null);
                $order->setShippingEmail($data['shippingEmail']);
                $order->setShippingPhone($data['shippingPhone'] ?? null);
                $order->setNotes($data['notes'] ?? null);
                $order->setOrderDate($now);
                $order->setCreatedAt($now);
                $em->persist($order);

                $product->setQuantity($product->getQuantity() - $item->getQuantity());
                $em->remove($item);
            }

            $em->flush();
            $this->addFlash('success', 'Order placed successfully! We will contact you shortly.');
            return $this->redirectToRoute('order_index');
        }

        return $this->render('market/checkout.html.twig', [
            'form'  => $form,
            'items' => $items,
            'total' => $total,
        ]);
    }

    private function isProductSellable(Product $product): bool
    {
        $seller = $product->getUser();

        return $product->getStatus() === 'approved'
            && $product->getQuantity() > 0
            && $seller instanceof UserInterface
            && $seller->getStatus() !== 'blocked';
    }
}
