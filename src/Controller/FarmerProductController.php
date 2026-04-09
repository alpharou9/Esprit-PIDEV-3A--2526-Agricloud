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
class FarmerProductController extends AbstractController
{
    #[Route('', name: 'product_index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $productRepository, FarmRepository $farmRepository): Response
    {
        $farmer = $this->getFarmerUser();
        $query = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $status = trim((string) $request->query->get('status', ''));
        $farmId = $request->query->getInt('farm');
        $sort = trim((string) $request->query->get('sort', 'newest')) ?: 'newest';

        $products = $productRepository->findByFilters(
            $query !== '' ? $query : null,
            $category !== '' ? $category : null,
            $farmId > 0 ? $farmId : null,
            $status !== '' ? $status : null,
            $sort,
            $farmer->getId()
        );

        return $this->render('market/farmer/index.html.twig', [
            'products' => $products,
            'q' => $query,
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedFarm' => $farmId > 0 ? $farmId : null,
            'selectedSort' => $sort,
            'categories' => $productRepository->findAvailableCategories(),
            'farms' => $farmRepository->findBy(['user' => $farmer], ['name' => 'ASC']),
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
        $farmer = $this->getFarmerUser();
        $product = new Product();
        $product->setUser($farmer);
        $product->setStatus('pending');

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $now = new \DateTimeImmutable();
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile !== null) {
                $product->setImage($this->uploadProductImage($imageFile, $slugger));
            }

            $product->setUser($farmer);
            $product->setStatus('pending');
            $product->setApprovedAt(null);
            $product->setApprovedBy(null);
            $product->setViews($product->getViews() ?? 0);
            $product->setCreatedAt($now);
            $product->setUpdatedAt($now);

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created and sent for approval.');

            return $this->redirectToRoute('product_index');
        }

        return $this->render('market/farmer/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessOwner($product);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile !== null) {
                $product->setImage($this->uploadProductImage($imageFile, $slugger));
            }

            $product->setStatus('pending');
            $product->setApprovedAt(null);
            $product->setApprovedBy(null);
            $product->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Product updated and sent for approval.');

            return $this->redirectToRoute('product_index');
        }

        return $this->render('market/farmer/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessOwner($product);

        if (!$this->isCsrfTokenValid('delete_product_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('product_index');
        }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('success', 'Product deleted.');

        return $this->redirectToRoute('product_index');
    }

    private function getFarmerUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->hasRole('ROLE_FARMER') || $user->hasRole('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Only farmers can manage farmer products.');
        }

        return $user;
    }

    private function denyAccessUnlessOwner(Product $product): void
    {
        $farmer = $this->getFarmerUser();

        if ($product->getUser()?->getId() !== $farmer->getId()) {
            throw $this->createAccessDeniedException('You can only edit your own products.');
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
