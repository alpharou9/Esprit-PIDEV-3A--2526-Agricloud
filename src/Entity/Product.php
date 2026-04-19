<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Farm::class)]
    #[ORM\JoinColumn(name: 'farm_id', referencedColumnName: 'id', nullable: true)]
    private ?Farm $farm = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Product name is required.')]
    #[Assert\Length(min: 2, max: 150, minMessage: 'Name must be at least 2 characters.', maxMessage: 'Name cannot exceed 150 characters.')]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Description cannot exceed 2000 characters.')]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Positive(message: 'Price must be positive.')]
    #[Assert\LessThan(value: 1000000, message: 'Price seems unrealistically high.')]
    private string $price;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Quantity is required.')]
    #[Assert\PositiveOrZero(message: 'Quantity cannot be negative.')]
    #[Assert\LessThanOrEqual(value: 999999, message: 'Quantity cannot exceed 999 999.')]
    private int $quantity;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Unit is required.')]
    #[Assert\Length(max: 20, maxMessage: 'Unit cannot exceed 20 characters.')]
    private string $unit;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'pending';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $views = 0;

    #[ORM\Column(name: 'approved_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by', referencedColumnName: 'id', nullable: true)]
    private ?User $approvedBy = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }

    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): static { $this->price = $price; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }

    public function getUnit(): string { return $this->unit; }
    public function setUnit(string $unit): static { $this->unit = $unit; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getImageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }

        $image = trim($this->image);

        if (preg_match('#^https?://#i', $image) === 1) {
            return $image;
        }

        $image = ltrim($image, '/');

        if (str_starts_with($image, 'uploads/products/')) {
            return $image;
        }

        return 'uploads/products/' . $image;
    }

    public function getImagePath(): ?string
    {
        return $this->getImageUrl();
    }

    public function isImageExternal(): bool
    {
        return $this->image !== null && preg_match('#^https?://#i', $this->image) === 1;
    }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }

    public function getViews(): ?int { return $this->views; }
    public function setViews(?int $views): static { $this->views = $views; return $this; }

    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $v): static { $this->approvedAt = $v; return $this; }

    public function getApprovedBy(): ?User { return $this->approvedBy; }
    public function setApprovedBy(?User $u): static { $this->approvedBy = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }

    public function getBenefitsTitle(): string
    {
        return match ($this->normalizeCategory()) {
            'fruits' => 'Fresh fruit benefits',
            'vegetables' => 'Everyday vegetable benefits',
            'dairy' => 'Dairy product benefits',
            'meat' => 'Protein-rich benefits',
            'grains' => 'Grain and pantry benefits',
            'herbs' => 'Fresh herb benefits',
            default => 'Why shoppers choose this product',
        };
    }

    public function getBenefitsText(): string
    {
        $name = $this->name ?? 'This product';
        $quantity = $this->quantity ?? 0;

        return match ($this->normalizeCategory()) {
            'fruits' => sprintf(
                '%s is a practical choice for fresh snacks, desserts, and light meals. It brings natural flavor, everyday freshness, and a simple way to add more fruit to the kitchen.',
                $name
            ),
            'vegetables' => sprintf(
                '%s is useful for daily cooking because it works well in salads, sauces, and home-style meals. It adds freshness, color, and a healthy base for quick recipes.',
                $name
            ),
            'dairy' => sprintf(
                '%s is convenient for breakfast, cooking, or simple family meals. It offers a fresh farm-style option that feels practical, nourishing, and easy to use every day.',
                $name
            ),
            'meat' => sprintf(
                '%s is a strong option for balanced meals thanks to its filling and versatile nature. It is well suited for hearty dishes, meal prep, and protein-focused recipes.',
                $name
            ),
            'grains' => sprintf(
                '%s is a dependable pantry staple that helps build affordable and satisfying meals. It is easy to store, useful in many recipes, and practical for regular cooking.',
                $name
            ),
            'herbs' => sprintf(
                '%s brings fresh aroma and a clean finishing touch to many dishes. A small amount can quickly improve flavor and make simple meals feel more vibrant.',
                $name
            ),
            default => sprintf(
                '%s is a simple marketplace choice for buyers who want reliable quality and everyday usefulness. With %d %s available, it is well suited for practical shopping and regular home use.',
                $name,
                $quantity,
                $this->unit ?? 'units'
            ),
        };
    }

    private function normalizeCategory(): string
    {
        return strtolower(trim((string) $this->category));
    }
}
