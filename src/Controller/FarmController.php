<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType;
use App\Repository\FarmRepository;
use App\Service\FarmNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/farms')]
#[IsGranted('ROLE_USER')]
class FarmController extends AbstractController
{
    #[Route('', name: 'farm_index', methods: ['GET'])]
    public function index(Request $request, FarmRepository $repo, PaginatorInterface $paginator): Response
    {
        $q     = $request->query->get('q', '');
        $sort  = $request->query->get('sort', 'latest');
        $owner = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();
        $allowedSorts = ['latest', 'fields_desc', 'fields_asc'];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $pagination = $paginator->paginate(
            $repo->listQueryBuilder($q ?: null, $owner, $sort),
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('farm/index.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'sort'       => $sort,
        ]);
    }

    #[Route('/new', name: 'farm_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $farm = new Farm();
        $form = $this->createForm(FarmType::class, $farm, [
            'require_coordinates' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $farm->setUser($this->getUser());
            $farm->setStatus('pending');
            $farm->setCreatedAt(new \DateTime());

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename     = $slugger->slug($originalFilename);
                $newFilename      = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('farms_upload_dir'), $newFilename);
                $farm->setImage($newFilename);
            }

            $em->persist($farm);
            $em->flush();

            $this->addFlash('success', 'Farm submitted for approval.');
            return $this->redirectToRoute('farm_index');
        }

        return $this->render('farm/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'farm_show', methods: ['GET'])]
    public function show(Farm $farm): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $farm->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('farm/show.html.twig', ['farm' => $farm]);
    }

    #[Route('/{id}/edit', name: 'farm_edit', methods: ['GET', 'POST'])]
    public function edit(
        Farm $farm,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $farm->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->validateFarmAreaAgainstFields($form, $farm)) {
                $farm->setUpdatedAt(new \DateTime());

                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $safeFilename = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                    $newFilename  = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                    $imageFile->move($this->getParameter('farms_upload_dir'), $newFilename);
                    $farm->setImage($newFilename);
                }

                $em->flush();
                $this->addFlash('success', 'Farm updated successfully.');
                return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
            }
        }

        return $this->render('farm/edit.html.twig', ['form' => $form, 'farm' => $farm]);
    }

    #[Route('/{id}/delete', name: 'farm_delete', methods: ['POST'])]
    public function delete(Farm $farm, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $farm->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_farm_' . $farm->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('farm_index');
        }

        $em->remove($farm);
        $em->flush();

        $this->addFlash('success', 'Farm deleted.');
        return $this->redirectToRoute('farm_index');
    }

    #[Route('/{id}/approve', name: 'farm_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Farm $farm, Request $request, EntityManagerInterface $em, FarmNotificationService $notificationService): Response
    {
        if (!$this->isCsrfTokenValid('approve_farm_' . $farm->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
        }

        $farm->setStatus('approved');
        $farm->setApprovedAt(new \DateTime());
        $farm->setApprovedBy($this->getUser());
        $notificationService->createReviewNotification($farm, 'approved');
        $em->flush();

        $this->addFlash('success', 'Farm approved.');
        return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
    }

    #[Route('/{id}/reject', name: 'farm_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Farm $farm, Request $request, EntityManagerInterface $em, FarmNotificationService $notificationService): Response
    {
        if (!$this->isCsrfTokenValid('reject_farm_' . $farm->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
        }

        $farm->setStatus('rejected');
        $farm->setApprovedAt(null);
        $farm->setApprovedBy(null);
        $notificationService->createReviewNotification($farm, 'rejected');
        $em->flush();

        $this->addFlash('success', 'Farm rejected.');
        return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
    }

    private function validateFarmAreaAgainstFields(FormInterface $form, Farm $farm): bool
    {
        $allocatedFieldArea = $farm->getAllocatedFieldArea();
        $farmArea = $farm->getAreaValue();

        if ($allocatedFieldArea <= 0) {
            return true;
        }

        if ($farmArea === null) {
            $form->get('area')->addError(new FormError(sprintf(
                'The farm area cannot be empty while %.2f ha are already allocated to fields.',
                $allocatedFieldArea
            )));
            return false;
        }

        if ($allocatedFieldArea > $farmArea + 0.00001) {
            $form->get('area')->addError(new FormError(sprintf(
                'The farm area must be at least %.2f ha because the existing fields already use that much area.',
                $allocatedFieldArea
            )));
            return false;
        }

        return true;
    }
}
