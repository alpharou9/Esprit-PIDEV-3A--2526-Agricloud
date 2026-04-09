<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\FarmRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/products')]
#[IsGranted('ROLE_FARMER')]
class ProductController extends AbstractController
{
    #[Route('', name: 'product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, FarmRepository $farmRepository): Response
    {
        $currentUser = $this->getAuthenticatedUser();
        $ownerId = $this->isGranted('ROLE_ADMIN') ? null : $currentUser->getId();
        $query = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $status = trim((string) $request->query->get('status', ''));
        $farmId = $request->query->getInt('farm');
        $sort = trim((string) $request->query->get('sort', 'newest'));

        $products = $productRepository->findByFilters(
            $query !== '' ? $query : null,
            $category !== '' ? $category : null,
            $farmId > 0 ? $farmId : null,
            $status !== '' ? $status : null,
            $sort !== '' ? $sort : 'newest',
            $ownerId
        );

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            'q' => $query,
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedFarm' => $farmId > 0 ? $farmId : null,
            'selectedSort' => $sort !== '' ? $sort : 'newest',
            'categories' => $productRepository->findAvailableCategories(),
            'farms' => $farmRepository->findBy([], ['name' => 'ASC']),
            'statuses' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'sold_out' => 'Sold out',
            ],
            'sortOptions' => [
                'newest' => 'Newest first',
                'oldest' => 'Oldest first',
                'name_asc' => 'Name A-Z',
                'name_desc' => 'Name Z-A',
                'price_asc' => 'Price low to high',
                'price_desc' => 'Price high to low',
                'stock_asc' => 'Stock low to high',
                'stock_desc' => 'Stock high to low',
            ],
        ]);
    }

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $currentUser = $this->getAuthenticatedUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $product = new Product();
        if (!$isAdmin) {
            $product->setUser($currentUser);
            $product->setStatus('pending');
        }
        $form = $this->createForm(ProductType::class, $product, [
            'show_owner' => $isAdmin,
            'show_status' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();
            $imageFile = $form->get('imageFile')->getData();

            if ($product->getViews() === null) {
                $product->setViews(0);
            }

            if ($imageFile !== null) {
                $product->setImage($this->uploadProductImage($imageFile, $slugger));
            }

            if (!$isAdmin) {
                $product->setUser($currentUser);
                $product->setStatus('pending');
            }

            $product->setCreatedAt($now);
            $product->setUpdatedAt($now);
            $this->syncProductApproval($product);

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully.');

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $currentUser = $this->getAuthenticatedUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $this->denyAccessUnlessCanManageProduct($product, $currentUser);

        $form = $this->createForm(ProductType::class, $product, [
            'show_owner' => $isAdmin,
            'show_status' => $isAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile !== null) {
                $product->setImage($this->uploadProductImage($imageFile, $slugger));
            }

            if (!$isAdmin) {
                $product->setUser($currentUser);
                $product->setStatus('pending');
            }

            $product->setUpdatedAt(new \DateTimeImmutable());
            $this->syncProductApproval($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCanManageProduct($product, $this->getAuthenticatedUser());

        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('product_index');
        }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('product_index');
    }

    private function syncProductApproval(Product $product): void
    {
        if ($product->getStatus() === 'approved') {
            $product->setApprovedAt(new \DateTimeImmutable());

            $currentUser = $this->getUser();
            if ($currentUser instanceof User && $currentUser->getId() !== null) {
                $product->setApprovedBy($currentUser->getId());
            }

            return;
        }

        $product->setApprovedAt(null);
        $product->setApprovedBy(null);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        return $user;
    }

    private function denyAccessUnlessCanManageProduct(Product $product, User $user): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($product->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You can only manage your own products.');
        }
    }

    private function uploadProductImage($imageFile, SluggerInterface $slugger): string
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/products';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        try {
            $imageFile->move($uploadDirectory, $newFilename);
        } catch (FileException $exception) {
            throw new \RuntimeException('The image upload failed.', 0, $exception);
        }

        return 'uploads/products/' . $newFilename;
    }
}
