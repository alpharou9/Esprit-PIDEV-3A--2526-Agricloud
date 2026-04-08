<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/farm')]
final class FarmController extends AbstractController
{
    #[Route(name: 'app_farm_index', methods: ['GET'])]
    public function index(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/index.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_farm_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $farm = new Farm();
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($farm);
            $entityManager->flush();

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
        if ($this->isCsrfTokenValid('delete'.$farm->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($farm);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_farm_index', [], Response::HTTP_SEE_OTHER);
    }
}
