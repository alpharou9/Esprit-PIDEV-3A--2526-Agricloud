<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Entity\Field;
use App\Form\FieldType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
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
            if ($this->validateFieldAreaConstraints($form, $farm, $field)) {
                $field->setCreatedAt(new \DateTime());
                $em->persist($field);
                $em->flush();

                $this->addFlash('success', 'Field added successfully.');
                return $this->redirectToRoute('farm_show', ['id' => $farmId]);
            }
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
            if ($this->validateFieldAreaConstraints($form, $farm, $field)) {
                $field->setUpdatedAt(new \DateTime());
                $em->flush();

                $this->addFlash('success', 'Field updated.');
                return $this->redirectToRoute('farm_show', ['id' => $farmId]);
            }
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

    private function validateFieldAreaConstraints(FormInterface $form, Farm $farm, Field $field): bool
    {
        $farmArea = $farm->getAreaValue();
        if ($farmArea === null) {
            $form->addError(new FormError('Set the farm area before creating or editing fields.'));
            return false;
        }

        $allocatedOtherFields = $farm->getAllocatedFieldArea($field);
        if ($allocatedOtherFields > $farmArea + 0.00001) {
            $form->addError(new FormError(sprintf(
                'This farm already has %.2f ha allocated across its other fields, which is above the farm total area of %.2f ha. Increase the farm area first.',
                $allocatedOtherFields,
                $farmArea
            )));
            return false;
        }

        $fieldArea = (float) $field->getArea();
        if ($fieldArea > $farmArea + 0.00001) {
            $form->get('area')->addError(new FormError(sprintf(
                'A single field cannot exceed the farm total area of %.2f ha.',
                $farmArea
            )));
            return false;
        }

        $remainingArea = round($farmArea - $allocatedOtherFields, 2);
        if ($fieldArea > $remainingArea + 0.00001) {
            $form->get('area')->addError(new FormError(sprintf(
                'This field is too large. Only %.2f ha remain available on the farm.',
                max($remainingArea, 0)
            )));
            return false;
        }

        return true;
    }
}
