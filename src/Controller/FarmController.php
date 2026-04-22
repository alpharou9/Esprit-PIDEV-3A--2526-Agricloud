<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType; // Ensure this matches your form class name
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/farm')]
final class FarmController extends AbstractController
{
    /**
     * ADMIN: Dashboard view to manage and approve farms
     */
    #[Route('/admin/list', name: 'app_admin_farm_index', methods: ['GET'])]
    public function adminIndex(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/admin_index.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * FARMER: Main portal view showing approved farms
     */
    #[Route('/portal', name: 'app_farmer_farm_index', methods: ['GET'])]
    public function farmerIndex(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/farmer_index.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * FARMER: "My Farms" management table
     */
    #[Route('/my-farms', name: 'app_farmer_my_farms', methods: ['GET'])]
    public function myFarms(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/my_farms.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * ADMIN: Action to approve a pending farm
     */
    #[Route('/{id}/approve', name: 'app_farm_approve', methods: ['POST'])]
    public function approve(Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $farm->setStatus('approved');
        $entityManager->flush();

        $this->addFlash('success', 'Farm approved successfully!');

        return $this->redirectToRoute('app_admin_farm_index');
    }

    #[Route('/new', name: 'app_farm_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $farm = new Farm();
        // Updated to FarmType (check if your form is named Farm1Type or FarmType)
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($farm);
            $entityManager->flush();

            return $this->redirectToRoute('app_farmer_my_farms', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/new.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_farm_show', methods: ['GET'])]
    public function show(Farm $farm): Response
    {
        return $this->render('farm/show.html.twig', [
            'farm' => $farm,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_farm_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_farmer_my_farms', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/edit.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_farm_delete', methods: ['POST'])]
    public function delete(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$farm->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($farm);
            $entityManager->flush();
            $this->addFlash('success', 'Farm deleted successfully.');
        }

        // Redirect back to the page they came from (Admin or Farmer list)
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_choice'));
    }
}