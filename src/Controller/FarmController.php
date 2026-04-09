<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Entity\User;
use App\Form\FarmType;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/farm')]
#[IsGranted('ROLE_FARMER')]
final class FarmController extends AbstractController
{
    #[Route(name: 'app_farm_index', methods: ['GET'])]
    public function index(FarmRepository $farmRepository): Response
    {
        $currentUser = $this->getAuthenticatedUser();

        return $this->render('farm/index.html.twig', [
            'farms' => $this->isGranted('ROLE_ADMIN')
                ? $farmRepository->findBy([], ['id' => 'DESC'])
                : $farmRepository->findBy(['user' => $currentUser], ['id' => 'DESC']),
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/new', name: 'app_farm_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getAuthenticatedUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $farm = new Farm();

        if (!$isAdmin) {
            $farm->setUser($currentUser);
            $farm->setStatus('pending');
        }

        $form = $this->createForm(FarmType::class, $farm, [
            'show_owner' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isAdmin) {
                $farm->setUser($currentUser);
                $farm->setStatus('pending');
            }

            $now = new \DateTimeImmutable();
            if ($farm->getCreatedAt() === null) {
                $farm->setCreatedAt($now);
            }
            $farm->setUpdatedAt($now);

            $entityManager->persist($farm);
            $entityManager->flush();

            $this->addFlash('success', 'Farm created successfully.');

            return $this->redirectToRoute('app_farm_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/new.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_farm_show', methods: ['GET'])]
    public function show(Farm $farm): Response
    {
        $this->denyAccessUnlessCanManageFarm($farm);

        return $this->render('farm/show.html.twig', [
            'farm' => $farm,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_farm_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getAuthenticatedUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $this->denyAccessUnlessCanManageFarm($farm);

        $form = $this->createForm(FarmType::class, $farm, [
            'show_owner' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isAdmin) {
                $farm->setUser($currentUser);
            }

            $farm->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Farm updated successfully.');

            return $this->redirectToRoute('app_farm_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/edit.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_farm_delete', methods: ['POST'])]
    public function delete(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCanManageFarm($farm);

        if ($this->isCsrfTokenValid('delete' . $farm->getId(), $request->request->get('_token'))) {
            $entityManager->remove($farm);
            $entityManager->flush();
            $this->addFlash('success', 'Farm deleted.');
        }

        return $this->redirectToRoute('app_farm_index', [], Response::HTTP_SEE_OTHER);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        return $user;
    }

    private function denyAccessUnlessCanManageFarm(Farm $farm): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($farm->getUser()?->getId() !== $this->getAuthenticatedUser()->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own farms.');
        }
    }
}
