<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/farm')]
final class FarmController extends AbstractController
{
    /**
     * 1. PUBLIC MARKETPLACE
     * Only shows 'approved' farms for public viewing.
     */
    #[Route('/', name: 'app_farm_index', methods: ['GET'])]
    public function index(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/index.html.twig', [
            'farms' => $farmRepository->findBy(['status' => 'approved']),
        ]);
    }

    /**
     * 2. ADMIN DASHBOARD
     * Shows ALL farms regardless of status, so the admin can track history.
     */
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function adminDashboard(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/admin_index.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * 3. MY FARMS (Farmer's Personal View)
     * Shows all farms belonging to the current session/user.
     */
    #[Route('/my-farms', name: 'app_my_farms', methods: ['GET'])]
    public function myFarms(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/my_farms.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * ADMIN ACTION: APPROVE
     */
    #[Route('/{id}/approve', name: 'app_farm_approve', methods: ['POST'])]
    public function approve(Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $farm->setStatus('approved');
        $entityManager->flush();

        $this->addFlash('success', 'Farm "' . $farm->getName() . '" has been approved.');
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * CREATE NEW FARM
     */
    #[Route('/new', name: 'app_farm_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $farm = new Farm();
        // Status is set to 'pending' by default in the Entity, but we'll be safe:
        $farm->setStatus('pending');

        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($farm);
            $entityManager->flush();

            $this->addFlash('success', 'Farm submitted for approval!');
            return $this->redirectToRoute('app_my_farms', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/new.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    /**
     * DETAILS VIEW (Public/Farmer)
     */
    #[Route('/{id}', name: 'app_farm_show', methods: ['GET'])]
    public function show(Farm $farm): Response
    {
        return $this->render('farm/show.html.twig', [
            'farm' => $farm,
            'fields' => $farm->getFields(),
        ]);
    }

    /**
     * EDIT FARM
     */
    #[Route('/{id}/edit', name: 'app_farm_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Farm updated successfully.');
            return $this->redirectToRoute('app_my_farms', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/edit.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    /**
     * DELETE FARM (Used by both Farmer and Admin)
     */
    #[Route('/{id}', name: 'app_farm_delete', methods: ['POST'])]
    public function delete(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$farm->getId(), $request->request->get('_token'))) {
            $entityManager->remove($farm);
            $entityManager->flush();
            $this->addFlash('success', 'Farm removed.');
        }

        // Redirect back to the page the user came from
        if ($request->query->get('from') === 'admin') {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('app_my_farms', [], Response::HTTP_SEE_OTHER);
    }
}