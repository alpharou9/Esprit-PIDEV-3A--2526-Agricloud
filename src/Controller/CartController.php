<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Form\CheckoutType;
use App\Repository\CartItemRepository;
use App\Service\CurrencyConverterService;
use App\Service\StripeCheckoutService;
use App\Service\TemporaryShippingStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/market/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    private const EXTERNAL_SHIPPING_PLACEHOLDER = 'Shipping details are collected outside AgriCloud.';

    #[Route('', name: 'cart_index', methods: ['GET'])]
    public function index(CartItemRepository $cartRepo, CurrencyConverterService $currencyConverter): Response
    {
        $items = $cartRepo->findByUser($this->getUser());
        $total = array_sum(array_map(fn($i) => $i->getSubtotal(), $items));
        $itemConversions = [];

        foreach ($items as $item) {
            $itemConversions[$item->getId()] = [
                'price' => $currencyConverter->convertAmount($item->getProduct()->getPrice()),
                'subtotal' => $currencyConverter->convertAmount($item->getSubtotal()),
            ];
        }

        return $this->render('market/cart.html.twig', [
            'items' => $items,
            'total' => $total,
            'convertedTotal' => $currencyConverter->convertAmount($total),
            'itemConversions' => $itemConversions,
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
    public function checkout(Request $request, EntityManagerInterface $em, CartItemRepository $cartRepo, StripeCheckoutService $stripeCheckoutService, CurrencyConverterService $currencyConverter, TemporaryShippingStorage $temporaryShippingStorage): Response
    {
        $items = $cartRepo->findByUser($this->getUser());

        if (empty($items)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('market_index');
        }

        $total = array_sum(array_map(fn($i) => $i->getSubtotal(), $items));
        $form  = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);
        $stripeCurrency = $stripeCheckoutService->getStripeCurrency();
        $convertedTotal = $currencyConverter->convertAmount($total);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $now  = new \DateTime();
            $paymentMethod = (string) ($data['paymentMethod'] ?? Order::PAYMENT_METHOD_CASH);
            $temporaryShippingDetails = null;

            if ($paymentMethod === Order::PAYMENT_METHOD_CASH) {
                $temporaryShippingDetails = $this->buildCashShippingDetails($form, $data);

                if ($temporaryShippingDetails === null) {
                    return $this->render('market/checkout.html.twig', [
                        'form'  => $form,
                        'items' => $items,
                        'total' => $total,
                        'stripeCurrency' => $stripeCurrency,
                        'stripeConvertedTotal' => $convertedTotal[$stripeCurrency] ?? null,
                    ]);
                }
            }

            $createdOrders = [];

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
                $order->setPaymentMethod($paymentMethod);
                $order->setPaymentStatus(Order::PAYMENT_STATUS_PENDING);
                $order->setShippingAddress(self::EXTERNAL_SHIPPING_PLACEHOLDER);
                $order->setShippingCity(null);
                $order->setShippingPostal(null);
                $order->setShippingEmail(null);
                $order->setShippingPhone(null);
                $order->setNotes(null);
                $order->setOrderDate($now);
                $order->setCreatedAt($now);
                $em->persist($order);
                $createdOrders[] = $order;

                $product->setQuantity($product->getQuantity() - $item->getQuantity());
                $em->remove($item);
            }

            $em->flush();

            if ($paymentMethod === Order::PAYMENT_METHOD_CASH && $temporaryShippingDetails !== null) {
                $temporaryShippingStorage->storeForOrders($createdOrders, $temporaryShippingDetails);
            }

            if ($paymentMethod === Order::PAYMENT_METHOD_STRIPE) {
                $session = $stripeCheckoutService->createCheckoutSession($createdOrders, $request);

                if (!$session['success']) {
                    foreach ($createdOrders as $order) {
                        $product = $order->getProduct();

                        if ($product !== null) {
                            $product->setQuantity($product->getQuantity() + $order->getQuantity());
                        }

                        $restoredItem = new CartItem();
                        $restoredItem->setUser($this->getUser());
                        $restoredItem->setProduct($order->getProduct());
                        $restoredItem->setQuantity($order->getQuantity());
                        $restoredItem->setCreatedAt(new \DateTime());
                        $em->persist($restoredItem);
                        $em->remove($order);
                    }

                    $em->flush();
                    $this->addFlash('error', $session['message']);

                    return $this->redirectToRoute('order_index');
                }

                foreach ($createdOrders as $order) {
                    $order->setStripeSessionId($session['sessionId']);
                }

                $em->flush();

                return new RedirectResponse($session['checkoutUrl']);
            }

            $this->addFlash('success', 'Order placed successfully.');
            return $this->redirectToRoute('order_index');
        }

        return $this->render('market/checkout.html.twig', [
            'form'  => $form,
            'items' => $items,
            'total' => $total,
            'stripeCurrency' => $stripeCurrency,
            'stripeConvertedTotal' => $convertedTotal[$stripeCurrency] ?? null,
        ]);
    }

    #[Route('/checkout/stripe/success', name: 'cart_checkout_stripe_success', methods: ['GET'])]
    public function stripeSuccess(Request $request, EntityManagerInterface $em, StripeCheckoutService $stripeCheckoutService): Response
    {
        $sessionId = (string) $request->query->get('session_id', '');
        $session = $stripeCheckoutService->retrieveSession($sessionId);

        if ($session === null) {
            $this->addFlash('error', 'Unable to verify the Stripe payment session.');
            return $this->redirectToRoute('order_index');
        }

        if (($session['payment_status'] ?? null) !== 'paid') {
            $this->addFlash('warning', 'Stripe did not confirm the payment yet.');
            return $this->redirectToRoute('order_index');
        }

        $orders = $em->getRepository(Order::class)->findBy([
            'customer' => $this->getUser(),
            'stripeSessionId' => $sessionId,
        ]);

        foreach ($orders as $order) {
            $order->setPaymentStatus(Order::PAYMENT_STATUS_PAID);
            $order->setUpdatedAt(new \DateTime());
        }

        $em->flush();

        $this->addFlash('success', 'Stripe payment completed successfully.');
        return $this->redirectToRoute('order_index');
    }

    #[Route('/checkout/stripe/cancel', name: 'cart_checkout_stripe_cancel', methods: ['GET'])]
    public function stripeCancel(Request $request, EntityManagerInterface $em): Response
    {
        $sessionId = (string) $request->query->get('session_id', '');
        $orders = $em->getRepository(Order::class)->findBy([
            'customer' => $this->getUser(),
            'stripeSessionId' => $sessionId,
        ]);

        foreach ($orders as $order) {
            if ($order->getPaymentStatus() === Order::PAYMENT_STATUS_PAID) {
                continue;
            }

            $order->setPaymentStatus(Order::PAYMENT_STATUS_CANCELLED);
            $order->setStatus(Order::STATUS_CANCELLED);
            $order->setCancelledAt(new \DateTime());
            $order->setCancelledReason('Stripe checkout was cancelled.');
            $order->setUpdatedAt(new \DateTime());
            $order->getProduct()?->setQuantity($order->getProduct()->getQuantity() + $order->getQuantity());
        }

        $em->flush();

        $this->addFlash('warning', 'Stripe checkout was cancelled. Your stock reservation has been released.');
        return $this->redirectToRoute('order_index');
    }

    private function isProductSellable(Product $product): bool
    {
        $seller = $product->getUser();

        return $product->getStatus() === 'approved'
            && $product->getQuantity() > 0
            && $seller instanceof UserInterface
            && $seller->getStatus() !== 'blocked';
    }

    /**
     * Validates and normalizes cash-on-delivery shipping data without persisting it.
     */
    private function buildCashShippingDetails(FormInterface $form, array $data): ?array
    {
        $name = trim((string) ($data['shippingName'] ?? ''));
        $address = trim((string) ($data['shippingAddress'] ?? ''));
        $city = trim((string) ($data['shippingCity'] ?? ''));
        $email = trim((string) ($data['shippingEmail'] ?? ''));
        $phone = trim((string) ($data['shippingPhone'] ?? ''));
        $postalCode = trim((string) ($data['shippingPostal'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        $isValid = true;

        if ($name === '' || mb_strlen($name) > 150) {
            $form->get('shippingName')->addError(new FormError('Please enter a valid full name for delivery.'));
            $isValid = false;
        }

        if ($address === '' || mb_strlen($address) < 5 || mb_strlen($address) > 500) {
            $form->get('shippingAddress')->addError(new FormError('Please enter a delivery address between 5 and 500 characters.'));
            $isValid = false;
        }

        if ($city === '' || mb_strlen($city) > 100) {
            $form->get('shippingCity')->addError(new FormError('Please enter a valid city.'));
            $isValid = false;
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $form->get('shippingEmail')->addError(new FormError('Please enter a valid contact email.'));
            $isValid = false;
        }

        if ($postalCode !== '' && !preg_match('/^\d{4,10}$/', $postalCode)) {
            $form->get('shippingPostal')->addError(new FormError('Postal code must contain 4 to 10 digits.'));
            $isValid = false;
        }

        if ($phone !== '' && !preg_match('/^\+?[0-9\s\-]{8,20}$/', $phone)) {
            $form->get('shippingPhone')->addError(new FormError('Phone must be 8 to 20 digits and may start with +.'));
            $isValid = false;
        }

        if (mb_strlen($notes) > 1000) {
            $form->get('notes')->addError(new FormError('Delivery notes cannot exceed 1,000 characters.'));
            $isValid = false;
        }

        if (!$isValid) {
            return null;
        }

        return [
            'name' => $name,
            'line1' => $address,
            'city' => $city,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'postal_code' => $postalCode !== '' ? $postalCode : null,
            'country' => 'TN',
            'notes' => $notes !== '' ? $notes : null,
        ];
    }
}
