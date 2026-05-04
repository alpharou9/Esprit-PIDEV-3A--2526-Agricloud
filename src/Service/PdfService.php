<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function generateOrderPdf(Order $order, ?array $shippingDetails = null): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->twig->render('market/order_pdf.html.twig', [
            'order' => $order,
            'shippingDetails' => $shippingDetails,
        ]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function generateProfilePdf(User $user, string $qrCodeUrl): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->twig->render('profile/profile_pdf.html.twig', [
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl,
        ]));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
