<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(
        Request             $request,
        AuthenticationUtils $authenticationUtils,
        #[Autowire('%recaptcha_site_key%')]
        string              $siteKey,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('security/login.html.twig', [
            'last_username'   => $authenticationUtils->getLastUsername(),
            'error'           => $error,
            'recaptcha_error' => null,
            'recaptcha_key'   => $siteKey,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the firewall.');
    }

    // ── Forgot password ───────────────────────────────────────────
    #[Route('/forgot-password', name: 'forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $sent = false;

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $user  = $userRepo->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                $em->flush();

                $resetUrl = $this->generateUrl('reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

                try {
                    $html = $this->renderView('emails/reset_password.html.twig', [
                        'user'      => $user,
                        'reset_url' => $resetUrl,
                    ]);
                    $mailer->send((new Email())
                        ->from('noreply@agricloud.tn')
                        ->to($user->getEmail())
                        ->subject('Reset your AgriCloud password')
                        ->html($html));
                } catch (\Throwable) {}
            }

            // Always show "sent" to avoid email enumeration
            $sent = true;
        }

        return $this->render('security/forgot_password.html.twig', ['sent' => $sent]);
    }

    // ── Reset password ────────────────────────────────────────────
    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $user = $userRepo->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'This reset link is invalid or has expired.');
            return $this->redirectToRoute('forgot_password');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm', '');

            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match.';
            } else {
                $user->setPassword($hasher->hashPassword($user, $password));
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $em->flush();

                $this->addFlash('success', 'Password updated! You can now sign in.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', ['token' => $token, 'error' => $error]);
    }
}
