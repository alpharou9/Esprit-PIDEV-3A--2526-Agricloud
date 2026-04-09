<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'A customer is required.')]
    private ?User $customer = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'A product is required.')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'seller_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'A seller is required.')]
    private ?User $seller = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotBlank(message: 'Quantity is required.')]
    #[Assert\Positive(message: 'Quantity must be greater than zero.')]
    private ?int $quantity = null;

    #[ORM\Column(name: 'unit_price', type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Unit price is required.')]
    #[Assert\PositiveOrZero(message: 'Unit price must be zero or greater.')]
    private ?string $unitPrice = null;

    #[ORM\Column(name: 'total_price', type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'pending'])]
    private ?string $status = 'pending';

    #[ORM\Column(name: 'shipping_address', type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Shipping address is required.')]
    private ?string $shippingAddress = null;

    #[ORM\Column(name: 'shipping_city', length: 100, nullable: true)]
    private ?string $shippingCity = null;

    #[ORM\Column(name: 'shipping_postal', length: 20, nullable: true)]
    private ?string $shippingPostal = null;

    #[ORM\Column(name: 'shipping_email', length: 150, nullable: true)]
    #[Assert\Email(message: 'Enter a valid shipping email address.')]
    private ?string $shippingEmail = null;

    #[ORM\Column(name: 'shipping_phone', length: 20, nullable: true)]
    private ?string $shippingPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'order_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $orderDate = null;

    #[ORM\Column(name: 'delivery_date', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveryDate = null;

    #[ORM\Column(name: 'cancelled_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(name: 'cancelled_reason', type: Types::TEXT, nullable: true)]
    private ?string $cancelledReason = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getCustomer(): ?User { return $this->customer; }
    public function setCustomer(?User $customer): static { $this->customer = $customer; return $this; }
    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }
    public function getSeller(): ?User { return $this->seller; }
    public function setSeller(?User $seller): static { $this->seller = $seller; return $this; }
    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }
    public function getUnitPrice(): ?string { return $this->unitPrice; }
    public function setUnitPrice(string $unitPrice): static { $this->unitPrice = $unitPrice; return $this; }
    public function getTotalPrice(): ?string { return $this->totalPrice; }
    public function setTotalPrice(string $totalPrice): static { $this->totalPrice = $totalPrice; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }
    public function getShippingAddress(): ?string { return $this->shippingAddress; }
    public function setShippingAddress(string $shippingAddress): static { $this->shippingAddress = $shippingAddress; return $this; }
    public function getShippingCity(): ?string { return $this->shippingCity; }
    public function setShippingCity(?string $shippingCity): static { $this->shippingCity = $shippingCity; return $this; }
    public function getShippingPostal(): ?string { return $this->shippingPostal; }
    public function setShippingPostal(?string $shippingPostal): static { $this->shippingPostal = $shippingPostal; return $this; }
    public function getShippingEmail(): ?string { return $this->shippingEmail; }
    public function setShippingEmail(?string $shippingEmail): static { $this->shippingEmail = $shippingEmail; return $this; }
    public function getShippingPhone(): ?string { return $this->shippingPhone; }
    public function setShippingPhone(?string $shippingPhone): static { $this->shippingPhone = $shippingPhone; return $this; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }
    public function getOrderDate(): ?\DateTimeInterface { return $this->orderDate; }
    public function setOrderDate(?\DateTimeInterface $orderDate): static { $this->orderDate = $orderDate; return $this; }
    public function getDeliveryDate(): ?\DateTimeInterface { return $this->deliveryDate; }
    public function setDeliveryDate(?\DateTimeInterface $deliveryDate): static { $this->deliveryDate = $deliveryDate; return $this; }
    public function getCancelledAt(): ?\DateTimeInterface { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTimeInterface $cancelledAt): static { $this->cancelledAt = $cancelledAt; return $this; }
    public function getCancelledReason(): ?string { return $this->cancelledReason; }
    public function setCancelledReason(?string $cancelledReason): static { $this->cancelledReason = $cancelledReason; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
