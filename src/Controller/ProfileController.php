<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_show', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'profileQrUrl' => $this->buildProfileQrUrl(),
        ]);
    }

    #[Route('/profile/edit', name: 'profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }
            $user->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Profile updated successfully.');
            return $this->redirectToRoute('profile_show');
        }

        return $this->render('profile/edit.html.twig', ['form' => $form]);
    }

    #[Route('/profile/pdf', name: 'profile_pdf', methods: ['GET'])]
    public function exportPdf(PdfService $pdfService): Response
    {
        $user = $this->getUser();
        $pdf = $pdfService->generateProfilePdf($user, $this->buildProfileQrUrl());
        $filename = sprintf('agricloud-profile-%s.pdf', $user->getId());

        $response = new Response($pdf);
        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename);

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function buildProfileQrUrl(): string
    {
        $user = $this->getUser();
        $profileLines = [
            'AgriCloud Profile',
            'Name: ' . $user->getName(),
            'Email: ' . $user->getEmail(),
            'Phone: ' . ($user->getPhone() ?: '-'),
            'Role: ' . ($user->getRole()?->getName() ?: 'User'),
            'Status: ' . ucfirst($user->getStatus() ?: 'active'),
        ];

        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode(implode("\n", $profileLines));
    }
}
