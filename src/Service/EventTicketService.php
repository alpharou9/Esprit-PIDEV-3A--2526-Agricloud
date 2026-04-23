<?php

namespace App\Service;

use App\Entity\Participation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EventTicketService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EventTicketContextBuilder $ticketContextBuilder,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $fromEmail,
    ) {
    }

    public function sendRegistrationTicket(Participation $participation): array
    {
        $event = $participation->getEvent();
        $user = $participation->getUser();

        if ($event === null || $user === null) {
            return [
                'sent' => false,
                'message' => 'Registration saved, but the ticket could not be prepared.',
            ];
        }

        $customerEmail = trim($user->getEmail());
        if ($customerEmail === '') {
            return [
                'sent' => false,
                'message' => 'Registration saved, but the customer has no email address.',
            ];
        }

        $ticketNumber = sprintf(
            'EVT-%d-%d',
            $event->getId() ?? 0,
            $participation->getId() ?? 0
        );

        $availability = $this->ticketContextBuilder->buildAvailability($event);
        $mapSearchUrl = $this->ticketContextBuilder->buildMapSearchUrl($event);
        $qrCodeUrl = $this->ticketContextBuilder->buildQrCodeUrl($event);
        $pdfAttachment = $this->buildTicketAttachment($participation, $ticketNumber, $availability, $mapSearchUrl, $qrCodeUrl);

        try {
            $email = (new TemplatedEmail())
                ->from(new Address($this->fromEmail, 'AgriCloud'))
                ->to(new Address($customerEmail, $user->getName()))
                ->subject(sprintf('Your ticket for %s', $event->getTitle()))
                ->htmlTemplate('emails/event_ticket.html.twig')
                ->context([
                    'event' => $event,
                    'participant' => $user,
                    'participation' => $participation,
                    'ticketNumber' => $ticketNumber,
                    'availability' => $availability,
                    'mapSearchUrl' => $mapSearchUrl,
                    'qrCodeUrl' => $qrCodeUrl,
                    'logo_cid' => null,
                ]);

            if ($pdfAttachment !== null) {
                $email->attach(
                    $pdfAttachment,
                    sprintf('event-ticket-%s.pdf', strtolower($ticketNumber)),
                    'application/pdf'
                );
            }

            $logoPath = $this->getLogoPath();
            if ($logoPath !== null) {
                $email->embedFromPath($logoPath, 'agricloud-logo');
                $email->context([
                    'event' => $event,
                    'participant' => $user,
                    'participation' => $participation,
                    'ticketNumber' => $ticketNumber,
                    'availability' => $availability,
                    'mapSearchUrl' => $mapSearchUrl,
                    'qrCodeUrl' => $qrCodeUrl,
                    'logo_cid' => 'cid:agricloud-logo',
                ]);
            }

            $this->mailer->send($email);
        } catch (TransportExceptionInterface|\Throwable) {
            return [
                'sent' => false,
                'message' => 'Registration saved, but the ticket email could not be sent.',
            ];
        }

        return [
            'sent' => true,
            'message' => $pdfAttachment !== null
                ? 'Your ticket has been sent to your email address.'
                : 'Your registration email was sent, but the PDF ticket attachment could not be generated.',
        ];
    }

    private function buildTicketAttachment(
        Participation $participation,
        string $ticketNumber,
        array $availability,
        ?string $mapSearchUrl,
        ?string $qrCodeUrl
    ): ?string {
        try {
            return $this->generateTicketPdf(
                $participation,
                $ticketNumber,
                $availability,
                $mapSearchUrl,
                $qrCodeUrl
            );
        } catch (\Throwable) {
            try {
                return $this->generateTicketPdf(
                    $participation,
                    $ticketNumber,
                    $availability,
                    $mapSearchUrl,
                    null
                );
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function generateTicketPdf(
        Participation $participation,
        string $ticketNumber,
        array $availability,
        ?string $mapSearchUrl,
        ?string $qrCodeUrl
    ): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->twig->render('event/ticket_pdf.html.twig', [
            'event' => $participation->getEvent(),
            'participant' => $participation->getUser(),
            'participation' => $participation,
            'ticketNumber' => $ticketNumber,
            'availability' => $availability,
            'mapSearchUrl' => $mapSearchUrl,
            'qrCodeUrl' => $qrCodeUrl,
            'generatedAt' => new \DateTimeImmutable(),
        ]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function getLogoPath(): ?string
    {
        $logoPath = $this->parameterBag->get('kernel.project_dir') . '/public/theme/img/agricloud-email-logo.svg';

        if (!is_file($logoPath)) {
            return null;
        }

        return $logoPath;
    }
}
