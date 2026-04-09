<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/blog/comment')]
class CommentController extends AbstractController
{
    // ─── LIST ALL COMMENTS ───────────────────────────────────────────────────

    #[Route('/', name: 'app_comment_index', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): Response
    {
        return $this->render('comment/index.html.twig', [
            'comments' => $commentRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    // ─── EDIT ────────────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_comment_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CommentRepository $commentRepository, EntityManagerInterface $em): Response
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Comment not found.');
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Comment updated!');
            return $this->redirectToRoute('app_post_show', ['id' => $comment->getPost()->getId()]);
        }

        return $this->render('comment/edit.html.twig', [
            'form'    => $form,
            'comment' => $comment,
        ]);
    }

    // ─── DELETE ──────────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, CommentRepository $commentRepository, EntityManagerInterface $em): Response
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Comment not found.');
        }

        $postId = $comment->getPost()->getId();

        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted!');
        }

        return $this->redirectToRoute('app_post_show', ['id' => $postId]);
    }
}
