<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\BlogAudioApiService;
use App\Service\BlogChatbotService;
use App\Service\BlogModerationApiService;
use App\Service\BlogRecommendationApiService;
use App\Service\BlogSummaryApiService;
use App\Service\TranslationAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blog')]
#[IsGranted('ROLE_USER')]
class PostController extends AbstractController
{
    #[Route('', name: 'blog_index', methods: ['GET'])]
    public function index(Request $request, PostRepository $postRepo, PaginatorInterface $paginator): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $category = (string) $request->query->get('category', '');
        $sort = (string) $request->query->get('sort', 'newest');

        $pagination = $paginator->paginate(
            $postRepo->publicQueryBuilder($q ?: null, $category ?: null, $sort),
            $request->query->getInt('page', 1),
            9,
        );

        return $this->render('blog/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'category' => $category,
            'sort' => $sort,
            'categories' => $postRepo->findPublishedCategories(),
        ]);
    }

    #[Route('/my-posts', name: 'blog_my_posts', methods: ['GET'])]
    public function myPosts(Request $request, PostRepository $postRepo, PaginatorInterface $paginator): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');

        $qb = $this->isGranted('ROLE_ADMIN')
            ? $postRepo->adminQueryBuilder($q ?: null, $status ?: null)
            : $postRepo->authorQueryBuilder($this->getUser(), $q ?: null, $status ?: null);

        $pagination = $paginator->paginate($qb, $request->query->getInt('page', 1), 10);

        return $this->render('blog/my_posts.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, BlogSummaryApiService $summaryApi): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setCreatedAt(new \DateTime());
            $post->setViews(0);
            $post->setSlug(strtolower((string) $slugger->slug($post->getTitle())) . '-' . uniqid());

            $rawTags = $form->get('tagsInput')->getData() ?? '';
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags))));
            $post->setTags($tags ?: null);

            if (!$post->getExcerpt()) {
                $generated = $summaryApi->generateSummary($post->getTitle(), $post->getContent());
                $post->setExcerpt($generated['summary']);
            }

            if ($post->getStatus() === 'published') {
                $post->setPublishedAt(new \DateTime());
            }

            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);
            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Post created successfully.');
            return $this->redirectToRoute('blog_my_posts');
        }

        return $this->render('blog/post_form.html.twig', ['form' => $form, 'post' => null]);
    }

    #[Route('/api/summarize', name: 'blog_api_summarize', methods: ['POST'])]
    public function summarize(Request $request, BlogSummaryApiService $summaryApi): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '') {
            return $this->json(['error' => 'Content is required.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($summaryApi->generateSummary($title, $content));
    }

    #[Route('/api/comment/moderate', name: 'blog_api_moderate_comment', methods: ['POST'])]
    public function moderateComment(Request $request, BlogModerationApiService $moderationApi): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $text = trim((string) ($payload['text'] ?? ''));
        if ($text === '') {
            return $this->json(['error' => 'Comment text is required.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($moderationApi->analyze($text, 'comment'));
    }

    #[Route('/api/translation/track', name: 'blog_api_translation_track', methods: ['POST'])]
    public function trackTranslation(Request $request, TranslationAnalyticsService $translationAnalytics): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $language = trim((string) ($payload['language'] ?? ''));
        $context = trim((string) ($payload['context'] ?? 'post'));

        if ($language === '') {
            return $this->json(['error' => 'Language is required.'], Response::HTTP_BAD_REQUEST);
        }

        $translationAnalytics->record($language, $context);

        return $this->json(['ok' => true]);
    }

    #[Route('/api/chat', name: 'blog_api_chat', methods: ['POST'])]
    public function chat(Request $request, BlogChatbotService $blogChatbot): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return $this->json(['error' => 'Question is required.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $blogChatbot->answer($message);
        $items = [];

        foreach ($result['items'] as $item) {
            $items[] = [
                'title' => $item['title'],
                'url' => $this->generateUrl('blog_show', ['slug' => $item['slug']]),
                'category' => $item['category'],
                'views' => $item['views'],
                'excerpt' => $item['excerpt'],
            ];
        }

        return $this->json([
            'reply' => $result['reply'],
            'items' => $items,
        ]);
    }

    #[Route('/api/post/{id}/related', name: 'blog_api_related', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function relatedPosts(Post $post, BlogRecommendationApiService $recommendationApi): JsonResponse
    {
        $items = [];
        foreach ($recommendationApi->recommend($post, 3) as $related) {
            $items[] = [
                'title' => $related->getTitle(),
                'url' => $this->generateUrl('blog_show', ['slug' => $related->getSlug()]),
                'category' => $related->getCategory(),
                'views' => $related->getViews(),
                'excerpt' => $related->getExcerpt() ?: mb_substr(strip_tags($related->getContent()), 0, 110) . 'Ã¢â‚¬Â¦',
            ];
        }

        return $this->json(['items' => $items]);
    }


    #[Route('/api/post/{id}/audio', name: 'blog_api_audio', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function postAudio(Post $post, Request $request, BlogAudioApiService $audioApi): Response
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        $title = trim((string) ($payload['title'] ?? $post->getTitle()));
        $content = trim((string) ($payload['content'] ?? $post->getContent()));
        $language = trim((string) ($payload['language'] ?? ''));

        if ($content === '') {
            return $this->json(['error' => 'Audio text is required.'], Response::HTTP_BAD_REQUEST);
        }

        $audio = $audioApi->synthesize($title, $content, $language);
        if ($audio === null) {
            return $this->json(['error' => 'Audio generation is unavailable. Add your Voice RSS key first.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response($audio['audio'], 200, [
            'Content-Type' => $audio['content_type'],
            'Content-Disposition' => 'inline; filename="post-' . $post->getId() . '.mp3"',
        ]);
    }
    #[Route('/{id}/edit', name: 'blog_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Post $post, Request $request, EntityManagerInterface $em, SluggerInterface $slugger, BlogSummaryApiService $summaryApi): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $post->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PostType::class, $post);
        $form->get('tagsInput')->setData(implode(', ', $post->getTags() ?? []));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUpdatedAt(new \DateTime());
            $rawTags = $form->get('tagsInput')->getData() ?? '';
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags))));
            $post->setTags($tags ?: null);

            if (!$post->getExcerpt()) {
                $generated = $summaryApi->generateSummary($post->getTitle(), $post->getContent());
                $post->setExcerpt($generated['summary']);
            }

            if ($post->getStatus() === 'published' && !$post->getPublishedAt()) {
                $post->setPublishedAt(new \DateTime());
            }

            $this->handleImageUpload($form->get('imageFile')->getData(), $post, $slugger);
            $em->flush();

            $this->addFlash('success', 'Post updated successfully.');
            return $this->redirectToRoute('blog_my_posts');
        }

        return $this->render('blog/post_form.html.twig', ['form' => $form, 'post' => $post]);
    }

    #[Route('/{id}/delete', name: 'blog_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
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

    #[Route('/{id}/comment', name: 'blog_comment_add', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function addComment(Post $post, Request $request, EntityManagerInterface $em, BlogModerationApiService $moderationApi): Response
    {
        $isOwnerOrAdmin = $this->isGranted('ROLE_ADMIN') || $post->getUser() === $this->getUser();
        if ($post->getStatus() !== 'published' && !$isOwnerOrAdmin) {
            throw $this->createNotFoundException();
        }

        $comment = new Comment();
        $rawContent = $request->request->all('comment')['content'] ?? null;

        if ($rawContent !== null) {
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
                $this->addFlash('error', 'Comment is invalid.');
                return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
            }
        }

        $analysis = $moderationApi->analyze($comment->getContent(), 'comment');
        if ($analysis['action'] === 'reject') {
            $this->addFlash('error', 'Comment blocked: ' . $analysis['reason']);
            return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
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

        if ($analysis['action'] === 'pending' && $analysis['label'] !== 'safe') {
            $this->addFlash('warning', 'Comment submitted and flagged for manual review.');
        } else {
            $this->addFlash('success', 'Comment submitted - it will appear after moderation.');
        }
        return $this->redirectToRoute('blog_show', ['slug' => $post->getSlug()]);
    }

    #[Route('/admin/comments', name: 'blog_admin_comments', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminComments(Request $request, CommentRepository $commentRepo, PaginatorInterface $paginator): Response
    {
        $status = (string) $request->query->get('status', '');
        $q = trim((string) $request->query->get('q', ''));

        $pagination = $paginator->paginate($commentRepo->adminQueryBuilder($status ?: null, $q ?: null), $request->query->getInt('page', 1), 15);

        return $this->render('blog/admin_comments.html.twig', ['pagination' => $pagination, 'status' => $status, 'q' => $q]);
    }

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
            $referer = $request->headers->get('referer', '');
            if (str_contains($referer, '/blog/admin/comments')) {
                return $this->redirectToRoute('blog_admin_comments');
            }
            return $this->redirectToRoute('blog_show', ['slug' => $postSlug]);
        }
        return $this->redirectToRoute('blog_admin_comments');
    }

    #[Route('/{slug}', name: 'blog_show', methods: ['GET'])]
    public function show(Post $post, EntityManagerInterface $em, CommentRepository $commentRepo): Response
    {
        $isOwnerOrAdmin = $this->isGranted('ROLE_ADMIN') || $post->getUser() === $this->getUser();
        if ($post->getStatus() !== 'published' && !$isOwnerOrAdmin) {
            throw $this->createNotFoundException('Post not found.');
        }

        $post->setViews($post->getViews() + 1);
        $em->flush();

        $approvedComments = $commentRepo->findApprovedForPost($post);
        $commentForm = $this->createForm(CommentType::class, new Comment(), [
            'action' => $this->generateUrl('blog_comment_add', ['id' => $post->getId()]),
            'method' => 'POST',
        ]);

        return $this->render('blog/show.html.twig', ['post' => $post, 'approvedComments' => $approvedComments, 'commentForm' => $commentForm]);
    }

    private function handleImageUpload(mixed $imageFile, Post $post, SluggerInterface $slugger): void
    {
        if (!$imageFile) {
            return;
        }

        $safeName = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newName = $safeName . '-' . uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move($this->getParameter('posts_upload_dir'), $newName);
            $post->setImage($newName);
        } catch (FileException $e) {
            $this->addFlash('error', 'Image upload failed: ' . $e->getMessage());
        }
    }

    private function currentFilters(Request $request): array
    {
        return array_filter([
            'status' => $request->request->get('_status') ?? $request->query->get('status'),
            'q' => $request->request->get('_q') ?? $request->query->get('q'),
            'page' => $request->query->getInt('page', 1),
        ]);
    }
}
