<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('', name: 'user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $repo): Response
    {
        $q     = $request->query->get('q', '');
        $users = $q ? $repo->search($q) : $repo->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'q'     => $q,
        ]);
    }

    #[Route('/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $plain));

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            if ($plain) {
                $user->setPassword($hasher->hashPassword($user, $plain));
            }

            $em->flush();
            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', ['form' => $form, 'user' => $user]);
    }

    #[Route('/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_index');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('user_index');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted.');
        return $this->redirectToRoute('user_index');
    }

    #[Route('/{id}/toggle-block', name: 'user_toggle_block', methods: ['POST'])]
    public function toggleBlock(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle_block_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_index');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot block your own account.');
            return $this->redirectToRoute('user_index');
        }

        $user->setStatus($user->getStatus() === 'blocked' ? 'active' : 'blocked');
        $em->flush();

        $this->addFlash('success', 'User ' . ($user->getStatus() === 'blocked' ? 'blocked' : 'unblocked') . '.');

        $redirect = $request->request->get('_redirect');
        if ($redirect && str_starts_with($redirect, '/')) {
            return $this->redirect($redirect);
        }
        return $this->redirectToRoute('user_index');
    }
}
