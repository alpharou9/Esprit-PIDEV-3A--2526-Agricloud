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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blog')]
#[IsGranted('ROLE_USER')]
class PostController extends AbstractController
{
    // =========================================================================
    // PUBLIC BLOG LISTING
    // =========================================================================

    /**
     * Public paginated blog listing with full-text search, category filter,
     * and sort order.
     */
    #[Route('', name: 'blog_index', methods: ['GET'])]
    public function index(
        Request $request,
        PostRepository $postRepo,
        PaginatorInterface $paginator,
    ): Response {
        $q        = trim((string) $request->query->get('q', ''));
        $category = (string) $request->query->get('category', '');
        $sort     = (string) $request->query->get('sort', 'newest');

        $pagination = $paginator->paginate(
            $postRepo->publicQueryBuilder($q ?: null, $category ?: null, $sort),
            $request->query->getInt('page', 1),
            9,
        );

        $categories = $postRepo->findPublishedCategories();

        return $this->render('blog/index.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'category'   => $category,
            'sort'       => $sort,
            'categories' => $categories,
        ]);
    }

    // =========================================================================
    // MY POSTS (author dashboard / admin all-posts)
    // =========================================================================

    /**
     * Author's own post list; admin sees all posts.
     * Supports search and status filter.
     */
    #[Route('/my-posts', name: 'blog_my_posts', methods: ['GET'])]
    public function myPosts(
        Request $request,
        PostRepository $postRepo,
        PaginatorInterface $paginator,
    ): Response {
        $q      = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');

        $qb = $this->isGranted('ROLE_ADMIN')
            ? $postRepo->adminQueryBuilder($q ?: null, $status ?: null)
            : $postRepo->authorQueryBuilder($this->getUser(), $q ?: null, $status ?: null);

        $pagination = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            10,
        );

        return $this->render('blog/my_posts.html.twig', [
            'pagination' => $pagination,
            'q'          => $q,
            'status'     => $status,
        ]);
    }

    // =========================================================================
    // CREATE POST
    // =========================================================================

    #[Route('/new', name: 'blog_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTime());
            $post->setViews(0);

            // Unique slug
            $slug = strtolower((string) $slugger->slug($post->getTitle())) . '-' . uniqid();
            $post->setSlug($slug);

            // Parse comma-separated tags
            $rawTags = $form->get('tagsInput')->getData() ?? '';
            $tags    = array_values(array_filter(array_map('trim', explode(',', $rawTags))));
            $post->setTags($tags ?: null);

            // Set publishedAt when going live
            if ($post->getStatus() === 'published') {
                $post->setPublishedAt(new \DateTime());
            }

            // Handle cover image upload
            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post created successfully.');
            return $this->redirectToRoute('blog_my_posts');
        }

        return $this->render('blog/post_form.html.twig', [
            'form' => $form,
            'post' => null,
        ]);
    }

    // =========================================================================
    // SHOW SINGLE POST
    // =========================================================================

    /**
     * Public single-post view. Increments the view counter.
     * Drafts/unpublished posts are visible to their author and admins only.
     * Slug-based route must be declared LAST to avoid matching other routes.
     */
    #[Route('/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(
        Post $post,
        EntityManagerInterface $em,
        CommentRepository $commentRepo,
    ): Response {
        $isOwnerOrAdmin = $this->isGranted('ROLE_ADMIN') || $post->getUser() === $this->getUser();

        if ($post->getStatus() !== 'published' && !$isOwnerOrAdmin) {
            throw $this->createNotFoundException('Post not found.');
        }

        // Increment view counter
        $post->setViews($post->getViews() + 1);
        $em->flush();

        // Eagerly load approved comments + replies to avoid N+1
        $approvedComments = $commentRepo->findApprovedForPost($post);

        $commentForm = $this->createForm(CommentType::class, new Comment(), [
            'action' => $this->generateUrl('blog_comment_add', ['id' => $post->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('blog/show.html.twig', [
            'post'             => $post,
            'approvedComments' => $approvedComments,
            'commentForm'      => $commentForm,
        ]);
    }

    // =========================================================================
    // EDIT POST
    // =========================================================================

    #[Route('/{id}/edit', name: 'blog_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Post $post,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PostType::class, $post);
        // Pre-fill the unmapped tags field
        $form->get('tagsInput')->setData(implode(', ', $post->getTags() ?? []));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTime());

            $rawTags = $form->get('tagsInput')->getData() ?? '';
            $tags    = array_values(array_filter(array_map('trim', explode(',', $rawTags))));
            $post->setTags($tags ?: null);

            // Set publishedAt the first time the post is published
            if ($post->getStatus() === 'published' && !$post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTime());
            }

            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);

            $em->flush();

            $this->addFlash('success', 'Post updated successfully.');
            return $this->redirectToRoute('blog_my_posts');
        }

        return $this->render('blog/post_form.html.twig', [
            'form' => $form,
            'post' => $post,
        ]);
    }

    // =========================================================================
    // DELETE POST
    // =========================================================================

    #[Route('/{id}/delete', name: 'blog_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Post $post,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
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

    // =========================================================================
    // ADD COMMENT (top-level or reply)
    // =========================================================================

    #[Route('/{id}/comment', name: 'blog_comment_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addComment(
        Post $post,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $isOwnerOrAdmin = $this->isGranted('ROLE_ADMIN') || $post->getUser() === $this->getUser();

        if ($post->getStatus() !== 'published' && !$isOwnerOrAdmin) {
            throw $this->createNotFoundException();
        }

        $comment    = new Comment();
        $rawContent = $request->request->all('comment')['content'] ?? null;

        if ($rawContent !== null) {
            // Reply submitted via plain HTML collapse form
            $content = trim($rawContent);
            if ($content === '') {
                $this->addFlash('error', 'Reply cannot be empty.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
            $comment->setContent($content);
        } else {
            // Main comment box via Symfony form
            $form = $this->createForm(CommentType::class, $comment);
            $form->handleRequest($request);
            if (!$form->isSubmitted() || !$form->isValid()) {
                $this->addFlash('error', 'Comment is invalid.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
        }

        // Attach parent comment for replies
        $parentId = $request->request->get('parent_id');
        if ($parentId) {
            /** @var Comment|null $parent */
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

        $this->addFlash('success', 'Comment submitted — it will appear after moderation.');
        return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
    }

    // =========================================================================
    // ADMIN — COMMENT MODERATION LIST
    // =========================================================================

    #[Route('/admin/comments', name: 'blog_admin_comments', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminComments(
        Request $request,
        CommentRepository $commentRepo,
        PaginatorInterface $paginator,
    ): Response {
        $status = (string) $request->query->get('status', '');
        $q      = trim((string) $request->query->get('q', ''));

        $pagination = $paginator->paginate(
            $commentRepo->adminQueryBuilder($status ?: null, $q ?: null),
            $request->query->getInt('page', 1),
            15,
        );

        return $this->render('blog/admin_comments.html.twig', [
            'pagination' => $pagination,
            'status'     => $status,
            'q'          => $q,
        ]);
    }

    // =========================================================================
    // ADMIN — APPROVE / REJECT / DELETE COMMENT
    // =========================================================================

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
        return $this->redirectToRoute('blog_admin_comments', $this->currentFilters($request));
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
        return $this->redirectToRoute('blog_admin_comments', $this->currentFilters($request));
    }

    #[Route('/admin/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(Comment $comment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_comment_' . $comment->getId(), $request->request->get('_token'))) {
            $postSlug = $comment->getPost()->getSlug();
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Comment deleted.');

            // If coming from the post page, go back there; otherwise back to moderation list
            $referer = $request->headers->get('referer', '');
            if (str_contains($referer, '/blog/admin/comments')) {
                return $this->redirectToRoute('blog_admin_comments');
            }
            return $this->redirectToRoute('blog_show', ['slug' => $postSlug]);
        }
        return $this->redirectToRoute('blog_admin_comments');
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Moves a validated uploaded image file to the posts upload directory
     * and stores the new filename on the Post entity.
     */
    private function handleImageUpload(mixed $imageFile, Post $post, SluggerInterface $slugger): void
    {
        if (!$imageFile) {
            return;
        }

        $safeName = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newName  = $safeName . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move($this->getParameter('posts_upload_dir'), $newName);
            $post->setImage($newName);
        } catch (FileException $e) {
            $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Extracts current filter params so approve/reject redirects preserve search state.
     */
    private function currentFilters(Request $request): array
    {
        return array_filter([
            'status' => $request->request->get('_status') ?? $request->query->get('status'),
            'q'      => $request->request->get('_q')      ?? $request->query->get('q'),
            'page'   => $request->query->getInt('page', 1),
        ]);
    }
}
