<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/blog')]
#[IsGranted('ROLE_USER')]
class PostController extends AbstractController
{
    // ── Public blog listing ───────────────────────────────────────
    #[Route('', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $repo, PaginatorInterface $paginator): Response
    {
        $q        = $request->query->get('q', '');
        $category = $request->query->get('category', '');

        $pagination = $paginator->paginate(
            $repo->publicQueryBuilder($q ?: null, $category ?: null),
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('blog/index.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'category'   => $category,
        ]);
    }

    // ── My posts ──────────────────────────────────────────────────
    #[Route('/my-posts', name: 'blog_my_posts', methods: ['GET'])]
    public function myPosts(Request $request, PostRepository $repo, PaginatorInterface $paginator): Response
    {
        $q  = $request->query->get('q', '');
        $qb = $this->isGranted('ROLE_ADMIN')
            ? $repo->adminQueryBuilder($q ?: null, $request->query->get('status') ?: null)
            : $repo->authorQueryBuilder($this->getUser(), $q ?: null);

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        return $this->render('blog/my_posts.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'status'     => $request->query->get('status', ''),
        ]);
    }

    // ── New post ─────────────────────────────────────────────────
    #[Route('/new', name: 'blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTime());
            $post->setViews(0);

            $slug = strtolower((string) $slugger->slug($post->getTitle())) . '-' . uniqid();
            $post->setSlug($slug);

            $tags = array_filter(array_map('trim', explode(',', $form->get('tagsInput')->getData() ?? '')));
            $post->setTags($tags ?: null);

            if ($post->getStatus() === 'published') {
                $post->setPublishedAt(new \DateTime());
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safe    = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newName = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('posts_upload_dir'), $newName);
                $post->setImage($newName);
            }

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post created.');
            return $this->redirectToRoute('blog_my_posts');
        }

        return $this->render('blog/post_form.html.twig', ['form' => $form, 'post' => null]);
    }

    // ── Admin: comment moderation ─────────────────────────────────
    #[Route('/admin/comments', name: 'blog_admin_comments', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminComments(Request $request, CommentRepository $commentRepo, PaginatorInterface $paginator): Response
    {
        $status     = $request->query->get('status', '');
        $pagination = $paginator->paginate(
            $commentRepo->adminQueryBuilder($status ?: null),
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('blog/admin_comments.html.twig', [
            'pagination' => $pagination,
            'status'     => $status,
        ]);
    }

    // ── Admin: approve / reject comment ───────────────────────────
    #[Route('/admin/comment/{id}/approve', name: 'comment_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function approveComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('approve_comment_' . $comment->getId(), $request->request->get('_token'))) {
            $comment->setStatus('approved');
            $comment->setApprovedAt(new \DateTime());
            $comment->setApprovedBy($this->getUser());
            $em->flush();
            $this->addFlash('success', 'Comment approved.');
        }
        return $this->redirectToRoute('blog_admin_comments');
    }

    #[Route('/admin/comment/{id}/reject', name: 'comment_reject', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('reject_comment_' . $comment->getId(), $request->request->get('_token'))) {
            $comment->setStatus('rejected');
            $em->flush();
            $this->addFlash('success', 'Comment rejected.');
        }
        return $this->redirectToRoute('blog_admin_comments');
    }

    // ── Edit post ─────────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'blog_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Post $post, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PostType::class, $post);
        $form->get('tagsInput')->setData(implode(', ', $post->getTags() ?? []));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTime());

            $tags = array_filter(array_map('trim', explode(',', $form->get('tagsInput')->getData() ?? '')));
            $post->setTags($tags ?: null);

            if ($post->getStatus() === 'published' && !$post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTime());
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $safe    = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newName = $safe . '-' . uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('posts_upload_dir'), $newName);
                $post->setImage($newName);
            }

            $em->flush();
            $this->addFlash('success', 'Post updated.');
            return $this->redirectToRoute('blog_my_posts');
        }

        return $this->render('blog/post_form.html.twig', ['form' => $form, 'post' => $post]);
    }

    // ── Delete post ───────────────────────────────────────────────
    #[Route('/{id}/delete', name: 'blog_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_post_' . $post->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('blog_my_posts');
        }

        $em->remove($post);
        $em->flush();
        $this->addFlash('success', 'Post deleted.');
        return $this->redirectToRoute('blog_my_posts');
    }

    // ── Admin: delete comment ─────────────────────────────────────
    #[Route('/admin/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_comment_' . $comment->getId(), $request->request->get('_token'))) {
            $slug = $comment->getPost()->getSlug();
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted.');
            return $this->redirectToRoute('blog_show', ['slug' => $slug]);
        }
        return $this->redirectToRoute('blog_admin_comments');
    }

    // ── Add comment ───────────────────────────────────────────────
    #[Route('/{id}/comment', name: 'blog_comment_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addComment(Post $post, Request $request, EntityManagerInterface $em): Response
    {
        if ($post->getStatus() !== 'published' && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $comment = new Comment();

        // Support both the Symfony form (main box) and plain HTML form (reply box)
        $rawContent = $request->request->all('comment')['content'] ?? null;

        if ($rawContent !== null) {
            // Reply form submitted as plain HTML
            $content = trim($rawContent);
            if ($content === '') {
                $this->addFlash('error', 'Reply cannot be empty.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
            $comment->setContent($content);
        } else {
            $form = $this->createForm(CommentType::class, $comment);
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid()) {
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
        }

        $parentId = $request->request->get('parent_id');
        if ($parentId) {
            $parent = $em->find(Comment::class, (int) $parentId);
            if ($parent && $parent->getPost() === $post) {
                $comment->setParent($parent);
            }
        }

        $comment->setPost($post);
        $comment->setUser($this->getUser());
        $comment->setStatus('pending');
        $comment->setCreatedAt(new \DateTime());
        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Comment submitted and awaiting moderation.');
        return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
    }

    // ── Chatbot (HuggingFace Inference API) ──────────────────────
    #[Route('/chatbot', name: 'blog_chatbot', methods: ['POST'])]
    public function chatbot(
        Request $request,
        HttpClientInterface $httpClient,
        #[Autowire('%env(HUGGINGFACE_API_TOKEN)%')] string $hfToken,
    ): JsonResponse {
        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return $this->json(['reply' => 'Please type a message.']);
        }

        try {
            $response = $httpClient->request('POST',
                'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.3',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $hfToken,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'inputs'     => '<s>[INST] You are AgriCloud assistant, a helpful agriculture expert. Answer briefly. ' . $message . ' [/INST]',
                        'parameters' => ['max_new_tokens' => 200, 'temperature' => 0.7],
                    ],
                    'timeout' => 20,
                ]
            );

            $result = $response->toArray();
            $text   = $result[0]['generated_text'] ?? '';

            // Strip the prompt — keep only the response after [/INST]
            if (str_contains($text, '[/INST]')) {
                $text = trim(substr($text, strrpos($text, '[/INST]') + 7));
            }

            return $this->json(['reply' => $text ?: 'I could not generate a response.']);
        } catch (\Throwable) {
            return $this->json(['reply' => 'The AI service is unavailable right now. Please try again later.']);
        }
    }

    // ── Show post (slug — must be LAST) ───────────────────────────
    #[Route('/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(Post $post, EntityManagerInterface $em): Response
    {
        if ($post->getStatus() !== 'published' && !$this->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Post not found.');
        }

        $post->setViews($post->getViews() + 1);
        $em->flush();

        $commentForm = $this->createForm(CommentType::class, new Comment(), [
            'action' => $this->generateUrl('blog_comment_add', ['id' => $post->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('blog/show.html.twig', [
            'post'        => $post,
            'commentForm' => $commentForm,
        ]);
    }
}
