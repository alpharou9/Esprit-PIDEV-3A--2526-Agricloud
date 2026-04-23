<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\RecaptchaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher,
        RecaptchaService            $recaptcha,
        #[Autowire('%recaptcha_site_key%')]
        string                      $siteKey,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        $recaptchaError = null;

        if ($form->isSubmitted() && $form->isValid()) {
            // Verify reCAPTCHA before creating the account
            $token = $request->request->get('g-recaptcha-response', '');
            if (!$recaptcha->verify($token)) {
                $recaptchaError = 'Security check failed. Please try again.';
            } else {
                $plain = $form->get('plainPassword')->getData();
                $user->setPassword($hasher->hashPassword($user, $plain));
                $user->setStatus('active');
                $user->setCreatedAt(new \DateTime());

                // Assign role based on user choice
                $accountType = $request->request->get('account_type', 'customer');
                $roleName    = ($accountType === 'farmer') ? 'Farmer' : 'Customer';
                $role        = $em->getRepository(Role::class)->findOneBy(['name' => $roleName]);
                if (!$role) {
                    $role = $em->getRepository(Role::class)->findOneBy(['name' => 'Customer']);
                }
                if ($role) {
                    $user->setRole($role);
                }

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Account created! You can now sign in.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'form'            => $form,
            'recaptcha_error' => $recaptchaError,
            'recaptcha_key'   => $siteKey,
        ]);
    }
}
