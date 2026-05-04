<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GuestController extends AbstractController
{
    #[Route('/guest-login', name: 'guest_login', methods: ['GET'])]
    public function guestLogin(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        Security $security
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('market_index');
        }

        $token    = substr(bin2hex(random_bytes(4)), 0, 8);
        $name     = 'Guest_' . $token;
        $email    = 'guest_' . bin2hex(random_bytes(16)) . '@agricloud.com';
        $password = bin2hex(random_bytes(16));

        $guestRole = $em->getRepository(Role::class)->findOneBy(['name' => 'guest']);

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setStatus('active');
        $user->setRole($guestRole);
        $user->setCreatedAt(new \DateTime());

        $em->persist($user);
        $em->flush();

        $security->login($user, 'form_login', 'main');

        return $this->redirectToRoute('market_index');
    }
}
