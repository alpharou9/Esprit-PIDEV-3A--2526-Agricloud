<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[UniqueEntity(fields: ['name', 'user'], message: 'This user already has a product with the same name.')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'An owner is required.')]
    private ?User $user = null;

    #[ORM\Column(name: 'farm_id', type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private ?int $farmId = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Product name is required.')]
    #[Assert\Length(min: 2, max: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\PositiveOrZero(message: 'Price must be zero or greater.')]
    private ?string $price = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\NotBlank(message: 'Quantity is required.')]
    #[Assert\PositiveOrZero(message: 'Quantity must be zero or greater.')]
    private ?int $quantity = 0;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Unit is required.')]
    private ?string $unit = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'pending'])]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['default' => 0])]
    private ?int $views = 0;

    #[ORM\Column(name: 'approved_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(name: 'approved_by', type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private ?int $approvedBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getFarmId(): ?int { return $this->farmId; }
    public function setFarmId(?int $farmId): static { $this->farmId = $farmId; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPrice(): ?string { return $this->price; }
    public function setPrice(string $price): static { $this->price = $price; return $this; }
    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }
    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(string $unit): static { $this->unit = $unit; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }
    public function getViews(): ?int { return $this->views; }
    public function setViews(?int $views): static { $this->views = $views; return $this; }
    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $approvedAt): static { $this->approvedAt = $approvedAt; return $this; }
    public function getApprovedBy(): ?int { return $this->approvedBy; }
    public function setApprovedBy(?int $approvedBy): static { $this->approvedBy = $approvedBy; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
