<?php

namespace App\Controller;

use App\Entity\Field;
use App\Form\FieldType;
use App\Repository\FieldRepository;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/field')]
final class FieldController extends AbstractController
{
    /**
     * Lists only fields belonging to a specific farm
     */
    #[Route('/farm/{farm_id}', name: 'app_field_index', methods: ['GET'])]
    public function index(int $farm_id, FieldRepository $fieldRepository, FarmRepository $farmRepository): Response
    {
        $farm = $farmRepository->find($farm_id);
        
        if (!$farm) {
            throw $this->createNotFoundException('Farm not found');
        }

        return $this->render('field/index.html.twig', [
            // Filter by the relation property 'Farmid' defined in your Field entity
            'fields' => $fieldRepository->findBy(['Farmid' => $farm]),
            'farm' => $farm,
        ]);
    }

    /**
     * Creates a new field and automatically links it to the parent farm
     */
    #[Route('/new/{farm_id}', name: 'app_field_new', methods: ['GET', 'POST'])]
    public function new(int $farm_id, Request $request, EntityManagerInterface $entityManager, FarmRepository $farmRepository): Response
    {
        $farm = $farmRepository->find($farm_id);

        if (!$farm) {
            throw $this->createNotFoundException('Farm not found');
        }

        $field = new Field();
        // Automatically link the field to the farm passed in the URL
        $field->setFarmid($farm);

        $form = $this->createForm(FieldType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($field);
            $entityManager->flush();

            return $this->redirectToRoute('app_field_index', ['farm_id' => $farm->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('field/new.html.twig', [
            'field' => $field,
            'form' => $form,
            'farm' => $farm,
        ]);
    }

    #[Route('/{id}', name: 'app_field_show', methods: ['GET'])]
    public function show(Field $field): Response
    {
        return $this->render('field/show.html.twig', [
            'field' => $field,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_field_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Field $field, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(FieldType::class, $field);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();
        return $this->redirectToRoute('app_field_index', ['farm_id' => $field->getFarmid()->getId()]);
    }

    return $this->render('field/edit.html.twig', [
        'field' => $field, // This allows the template to see the farm_id
        'form' => $form,
    ]);
}

    #[Route('/{id}', name: 'app_field_delete', methods: ['POST'])]
    public function delete(Request $request, Field $field, EntityManagerInterface $entityManager): Response
    {
        $farmId = $field->getFarmid()->getId();

        if ($this->isCsrfTokenValid('delete'.$field->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($field);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_field_index', ['farm_id' => $farmId], Response::HTTP_SEE_OTHER);
    }
}