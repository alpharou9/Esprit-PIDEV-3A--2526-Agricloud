<?php

namespace App\Controller;

use App\Entity\Role;
use App\Form\RoleType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/roles')]
#[IsGranted('ROLE_ADMIN')]
class RoleController extends AbstractController
{
    #[Route('', name: 'role_index', methods: ['GET'])]
    public function index(RoleRepository $repo): Response
    {
        return $this->render('role/index.html.twig', ['roles' => $repo->findAll()]);
    }

    #[Route('/new', name: 'role_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $role = new Role();
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($role);
            $em->flush();
            $this->addFlash('success', 'Role created successfully.');
            return $this->redirectToRoute('role_index');
        }

        return $this->render('role/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'role_edit', methods: ['GET', 'POST'])]
    public function edit(Role $role, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RoleType::class, $role);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Role updated successfully.');
            return $this->redirectToRoute('role_index');
        }

        return $this->render('role/edit.html.twig', ['form' => $form, 'role' => $role]);
    }

    #[Route('/{id}/delete', name: 'role_delete', methods: ['POST'])]
    public function delete(
        Role $role,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepo
    ): Response {
        if (!$this->isCsrfTokenValid('delete_role_' . $role->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('role_index');
        }

        $usersWithRole = $userRepo->findBy(['role' => $role]);
        if (count($usersWithRole) > 0) {
            $this->addFlash('error', 'Cannot delete role: ' . count($usersWithRole) . ' user(s) are assigned to it.');
            return $this->redirectToRoute('role_index');
        }

        $em->remove($role);
        $em->flush();

        $this->addFlash('success', 'Role deleted.');
        return $this->redirectToRoute('role_index');
    }
}
