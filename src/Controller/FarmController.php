<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType;
use App\Repository\FarmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        $sort  = $request->query->get('sort', 'newest');
        $owner = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();

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
        $form = $this->createForm(FarmType::class, $farm);
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
    public function approve(Farm $farm, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('approve_farm_' . $farm->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
        }

        $farm->setStatus('approved');
        $farm->setApprovedAt(new \DateTime());
        $farm->setApprovedBy($this->getUser());
        $em->flush();

        $this->sendFarmStatusEmail($mailer, $farm, 'approved');
        $this->addFlash('success', 'Farm approved.');
        return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
    }

    #[Route('/{id}/reject', name: 'farm_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function reject(Farm $farm, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('reject_farm_' . $farm->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
        }

        $farm->setStatus('rejected');
        $farm->setApprovedAt(null);
        $farm->setApprovedBy(null);
        $em->flush();

        $this->sendFarmStatusEmail($mailer, $farm, 'rejected');
        $this->addFlash('success', 'Farm rejected.');
        return $this->redirectToRoute('farm_show', ['id' => $farm->getId()]);
    }

    private function sendFarmStatusEmail(MailerInterface $mailer, Farm $farm, string $status): void
    {
        try {
            $html = $this->renderView('emails/farm_status.html.twig', [
                'farm'     => $farm,
                'user'     => $farm->getUser(),
                'status'   => $status,
                'farm_url' => $this->generateUrl('farm_show', ['id' => $farm->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailer->send((new Email())
                ->from('noreply@agricloud.tn')
                ->to($farm->getUser()->getEmail())
                ->subject('Your farm "' . $farm->getName() . '" has been ' . $status)
                ->html($html));
        } catch (\Throwable) {}
    }
}
