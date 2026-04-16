<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly PdfService $pdfService,
        private readonly string $fromEmail,
    ) {
    }

    public function sendOrderConfirmedEmail(Order $order): array
    {
        $customer = $order->getCustomer();
        $customerEmail = trim($customer->getEmail());

        if ($customerEmail === '') {
            return [
                'sent' => false,
                'message' => 'Order confirmed, but the customer has no email address.',
            ];
        }

        $pdfContent = $this->pdfService->generateOrderPdf($order);

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, 'AgriCloud'))
            ->to(new Address($customerEmail, $customer->getName()))
            ->subject(sprintf('Your AgriCloud order #%d is confirmed', $order->getId()))
            ->htmlTemplate('emails/order_confirmed.html.twig')
            ->context([
                'order' => $order,
            ])
            ->attach($pdfContent, sprintf('order-%d.pdf', $order->getId()), 'application/pdf');

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            return [
                'sent' => false,
                'message' => 'Order confirmed, but the confirmation email could not be sent.',
            ];
        }

        return [
            'sent' => true,
            'message' => 'Order confirmed and confirmation email sent.',
        ];
    }
}
