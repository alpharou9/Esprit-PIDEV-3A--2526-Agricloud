<?php

namespace App\Controller;

use App\Entity\Field;
use App\Form\FieldType;
use App\Repository\FieldRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/field')]
final class FieldController extends AbstractController
{
    #[Route(name: 'app_field_index', methods: ['GET'])]
    public function index(FieldRepository $fieldRepository): Response
    {
        return $this->render('field/index.html.twig', [
            'fields' => $fieldRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_field_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $field = new Field();
        $form = $this->createForm(FieldType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($field);
            $entityManager->flush();

            return $this->redirectToRoute('app_field_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('field/new.html.twig', [
            'field' => $field,
            'form' => $form,
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

            return $this->redirectToRoute('app_field_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('field/edit.html.twig', [
            'field' => $field,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_field_delete', methods: ['POST'])]
    public function delete(Request $request, Field $field, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$field->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($field);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_field_index', [], Response::HTTP_SEE_OTHER);
    }
}
