<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            return $this->redirectToRoute('post_login_redirect');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error'         => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the firewall.');
    }

    #[Route('/post-login', name: 'post_login_redirect')]
    public function postLoginRedirect(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_FARMER')) {
            return $this->redirectToRoute('dashboard');
        }

        if ($user->hasRole('ROLE_CUSTOMER')) {
            return $this->redirectToRoute('market_index');
        }

        return $this->redirectToRoute('market_index');
    }
}
