<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Entity\Field;
use App\Form\FieldType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/farms/{farmId}/fields')]
#[IsGranted('ROLE_USER')]
class FieldController extends AbstractController
{
    #[Route('/new', name: 'field_new', methods: ['GET', 'POST'])]
    public function new(int $farmId, Request $request, EntityManagerInterface $em): Response
    {
        $farm = $em->getRepository(Farm::class)->find($farmId);
        if (!$farm) {
            throw $this->createNotFoundException('Farm not found.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $farm->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $field = new Field();
        $field->setFarm($farm);
        $form  = $this->createForm(FieldType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $field->setCreatedAt(new \DateTime());
            $em->persist($field);
            $em->flush();

            $this->addFlash('success', 'Field added successfully.');
            return $this->redirectToRoute('farm_show', ['id' => $farmId]);
        }

        return $this->render('field/new.html.twig', ['form' => $form, 'farm' => $farm]);
    }

    #[Route('/{id}/edit', name: 'field_edit', methods: ['GET', 'POST'])]
    public function edit(int $farmId, Field $field, Request $request, EntityManagerInterface $em): Response
    {
        $farm = $field->getFarm();

        if (!$this->isGranted('ROLE_ADMIN') && $farm->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(FieldType::class, $field);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $field->setUpdatedAt(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Field updated.');
            return $this->redirectToRoute('farm_show', ['id' => $farmId]);
        }

        return $this->render('field/edit.html.twig', ['form' => $form, 'field' => $field, 'farm' => $farm]);
    }

    #[Route('/{id}/delete', name: 'field_delete', methods: ['POST'])]
    public function delete(int $farmId, Field $field, Request $request, EntityManagerInterface $em): Response
    {
        $farm = $field->getFarm();

        if (!$this->isGranted('ROLE_ADMIN') && $farm->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_field_' . $field->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('farm_show', ['id' => $farmId]);
        }

        $em->remove($field);
        $em->flush();

        $this->addFlash('success', 'Field deleted.');
        return $this->redirectToRoute('farm_show', ['id' => $farmId]);
    }
}
