<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/farm')]
final class FarmController extends AbstractController
{
    /**
     * 1. PUBLIC MARKETPLACE
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
     */
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function adminDashboard(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/admin_index.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * 3. MY FARMS (Farmer View)
     */
    #[Route('/my-farms', name: 'app_my_farms', methods: ['GET'])]
    public function myFarms(FarmRepository $farmRepository): Response
    {
        return $this->render('farm/my_farms.html.twig', [
            'farms' => $farmRepository->findAll(),
        ]);
    }

    /**
     * CREATE NEW FARM WITH IMAGE UPLOAD
     */
    #[Route('/new', name: 'app_farm_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $farm = new Farm();
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                $farm->setImage($newFilename);
            }

            $entityManager->persist($farm);
            $entityManager->flush();

            $this->addFlash('success', 'Farm created and pending approval!');
            return $this->redirectToRoute('app_my_farms', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('farm/new.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    /**
     * EDIT FARM WITH IMAGE UPDATE
     */
    #[Route('/{id}/edit', name: 'app_farm_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Farm $farm, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                $farm->setImage($newFilename);
            }

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
     * ADMIN ACTION: APPROVE
     */
    #[Route('/{id}/approve', name: 'app_farm_approve', methods: ['POST'])]
    public function approve(Farm $farm, EntityManagerInterface $entityManager): Response
    {
        $farm->setStatus('approved');
        $entityManager->flush();

        $this->addFlash('success', 'Farm approved successfully!');
        return $this->redirectToRoute('admin_dashboard');
    }

    /**
     * DELETE FARM
     */
    #[Route('/{id}', name: 'app_farm_delete', methods: ['POST'])]
    public function delete(Request $request, Farm $farm, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$farm->getId(), $request->request->get('_token'))) {
            $entityManager->remove($farm);
            $entityManager->flush();
        }

        if ($request->query->get('from') === 'admin') {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('app_my_farms', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * HELPER METHOD: HANDLES FILE UPLOAD LOGIC
     */
    private function uploadImage($imageFile, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

        try {
            $imageFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/farms',
                $newFilename
            );
        } catch (FileException $e) {
            throw new \Exception('Failed to upload image.');
        }

        return $newFilename;
    }

    #[Route('/{id}', name: 'app_farm_show', methods: ['GET'])]
    public function show(Farm $farm): Response
    {
        return $this->render('farm/show.html.twig', [
            'farm' => $farm,
        ]);
    }
}