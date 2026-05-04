<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\FarmRepository;
use App\Repository\ProductRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:market-products',
    description: 'Create starter marketplace products from images in public/uploads/products.',
)]
class SeedMarketProductsCommand extends Command
{
    private const PRODUCTS = [
        'Aardappel-groenten-Veggipedia__FitMaxWzYwMCw2MDBd.webp' => ['Pommes de terre', 'vegetables', '3.80', 120, 'kg'],
        'ail-rouge-sec-importe.webp' => ['Ail rouge sec', 'vegetables', '6.20', 70, 'kg'],
        'baguette-traditionnelle-francaise.webp' => ['Baguette traditionnelle', 'grains', '1.20', 40, 'piece'],
        'blueberries.png.webp' => ['Blueberries', 'fruits', '14.50', 35, 'box'],
        'carotte-sans-fanes.webp' => ['Carottes', 'vegetables', '4.20', 90, 'kg'],
        'celeri-vert.webp' => ['Celeri vert', 'vegetables', '3.60', 45, 'piece'],
        'champignon.webp' => ['Champignons', 'vegetables', '8.00', 55, 'box'],
        'chou-rouge.webp' => ['Chou rouge', 'vegetables', '3.50', 40, 'piece'],
        'citron-eureka.webp' => ['Citron Eureka', 'fruits', '5.40', 65, 'kg'],
        'concombre.webp' => ['Concombres', 'vegetables', '3.20', 75, 'kg'],
        'courge-musquee.webp' => ['Courge musquee', 'vegetables', '4.90', 28, 'piece'],
        'courgette.webp' => ['Courgettes', 'vegetables', '3.90', 80, 'kg'],
        'emmental-de-savoie.jpg' => ['Emmental de Savoie', 'dairy', '18.00', 18, 'kg'],
        'homemade-yogurt.jpg' => ['Yaourt maison', 'dairy', '2.80', 32, 'piece'],
        'iStock_000007671231Large-e1565725651658-700x700.jpg' => ['Pommes rouges', 'fruits', '6.80', 50, 'kg'],
        'jambon-de-campagne-de-dinde.webp' => ['Jambon de dinde', 'meat', '16.50', 22, 'kg'],
        'jambon-de-poulet-fume.webp' => ['Jambon de poulet fume', 'meat', '15.90', 24, 'kg'],
        'jarret-sans-os-jeune-bovin.webp' => ['Jarret sans os', 'meat', '24.00', 16, 'kg'],
        'kiwi.webp' => ['Kiwi', 'fruits', '9.50', 40, 'kg'],
        'merguez-de-boeuf.webp' => ['Merguez de boeuf', 'meat', '19.50', 26, 'kg'],
        'navet-avec-fanes.webp' => ['Navet avec fanes', 'vegetables', '3.70', 35, 'bunch'],
        'oignon-rebeii.webp' => ['Oignons', 'vegetables', '2.90', 95, 'kg'],
        'persil.webp' => ['Persil frais', 'herbs', '1.80', 55, 'bunch'],
        'petit-pois.webp' => ['Petits pois', 'vegetables', '5.10', 38, 'kg'],
        'red raspberries.png' => ['Framboises rouges', 'fruits', '12.50', 28, 'box'],
        'Red-peppers-afa27f8.jpg' => ['Poivrons rouges', 'vegetables', '6.30', 42, 'kg'],
        'salade-romaine.webp' => ['Salade romaine', 'vegetables', '2.40', 48, 'piece'],
        'salami-de-boeuf-special.webp' => ['Salami de boeuf', 'meat', '21.00', 18, 'kg'],
        'saucisse-fumee.webp' => ['Saucisse fumee', 'meat', '17.40', 24, 'kg'],
        'saucisson-de-dinde.webp' => ['Saucisson de dinde', 'meat', '18.20', 20, 'kg'],
        'tomate-cerise-allongee.webp' => ['Tomates cerises', 'vegetables', '7.20', 36, 'box'],
        'viande-hachee-jeune-bovin.webp' => ['Viande hachee bovine', 'meat', '22.80', 20, 'kg'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
        private readonly FarmRepository $farmRepository,
        private readonly RoleRepository $roleRepository,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('farmer-email', null, InputOption::VALUE_REQUIRED, 'Farmer email that will own the imported products.', 'farmer@farmer.com')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Admin email used as approver.', 'admin@admin.com');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $farmer = $this->userRepository->findOneBy(['email' => $input->getOption('farmer-email')]);
        if ($farmer === null) {
            $io->error('Farmer account not found.');
            return Command::FAILURE;
        }

        $admin = $this->userRepository->findOneBy(['email' => $input->getOption('admin-email')]);
        if ($admin === null) {
            $io->error('Admin account not found.');
            return Command::FAILURE;
        }

        $farmerRole = $this->roleRepository->findOneBy(['name' => 'Farmer']);
        if ($farmer->getRole() !== $farmerRole) {
            $io->warning('The selected user is not linked to the Farmer role. The products will still be created.');
        }

        $farm = $this->farmRepository->findOneBy(['user' => $farmer, 'status' => 'approved']);
        if ($farm === null) {
            $io->error('No approved farm found for the selected farmer.');
            return Command::FAILURE;
        }

        $uploadsDir = $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
        $now = new \DateTimeImmutable();
        $created = 0;
        $skipped = 0;

        foreach (self::PRODUCTS as $filename => [$name, $category, $price, $quantity, $unit]) {
            $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $filename;

            if (!is_file($filePath)) {
                $io->warning(sprintf('Image not found, skipped: %s', $filename));
                ++$skipped;
                continue;
            }

            if ($this->productRepository->findOneBy(['image' => $filename]) !== null) {
                ++$skipped;
                continue;
            }

            $product = new Product();
            $product->setUser($farmer);
            $product->setFarm($farm);
            $product->setName($name);
            $product->setCategory($category);
            $product->setPrice($price);
            $product->setQuantity($quantity);
            $product->setUnit($unit);
            $product->setImage($filename);
            $product->setStatus('approved');
            $product->setViews(0);
            $product->setApprovedAt($now);
            $product->setApprovedBy($admin);
            $product->setCreatedAt($now);

            $this->entityManager->persist($product);
            ++$created;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Market seed completed. Created: %d, skipped: %d.', $created, $skipped));

        return Command::SUCCESS;
    }
}
