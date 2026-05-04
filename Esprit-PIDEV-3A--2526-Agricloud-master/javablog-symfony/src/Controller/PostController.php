<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use App\Form\PostType;
use App\Form\CommentType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/blog/post')]
class PostController extends AbstractController
{
    // ─── LIST ────────────────────────────────────────────────────────────────

    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAllOrderedByDate(),
        ]);
    }

    // ─── CREATE ──────────────────────────────────────────────────────────────

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($post);
            $em->flush();
            $this->addFlash('success', 'Post created successfully!');
            return $this->redirectToRoute('app_post_index');
        }

        return $this->render('post/new.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    // ─── READ / SHOW ─────────────────────────────────────────────────────────

    #[Route('/{id}', name: 'app_post_show', methods: ['GET', 'POST'])]
    public function show(int $id, Request $request, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        $post = $postRepository->findWithComments($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        // Inline comment form on show page
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $comment->setPost($post);
            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Comment added!');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post'        => $post,
            'commentForm' => $commentForm,
        ]);
    }

    // ─── UPDATE ──────────────────────────────────────────────────────────────

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    // ─── DELETE ──────────────────────────────────────────────────────────────

    #[Route('/{id}/delete', name: 'app_post_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, PostRepository $postRepository, EntityManagerInterface $em): Response
    {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Post deleted successfully!');
        }

        return $this->redirectToRoute('app_post_index');
    }
}
