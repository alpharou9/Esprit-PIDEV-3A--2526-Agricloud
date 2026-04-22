<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_STRIPE = 'stripe';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_CANCELLED = 'cancelled';

    public const FLOW_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PREPARING,
        self::STATUS_SHIPPED,
        self::STATUS_DELIVERED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: false)]
    private ?User $customer = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'seller_id', referencedColumnName: 'id', nullable: false)]
    private ?User $seller = null;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    #[ORM\Column(name: 'unit_price', type: 'decimal', precision: 10, scale: 2)]
    private string $unitPrice;

    #[ORM\Column(name: 'total_price', type: 'decimal', precision: 10, scale: 2)]
    private string $totalPrice;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'payment_method', length: 20, nullable: true)]
    private ?string $paymentMethod = self::PAYMENT_METHOD_CASH;

    #[ORM\Column(name: 'payment_status', length: 20, nullable: true)]
    private ?string $paymentStatus = self::PAYMENT_STATUS_PENDING;

    #[ORM\Column(name: 'stripe_session_id', length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(name: 'shipping_address', type: 'text')]
    #[Assert\NotBlank(message: 'Shipping address is required.')]
    #[Assert\Length(min: 5, max: 500, minMessage: 'Address must be at least 5 characters.', maxMessage: 'Address cannot exceed 500 characters.')]
    private string $shippingAddress;

    #[ORM\Column(name: 'shipping_city', length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'City cannot exceed 100 characters.')]
    private ?string $shippingCity = null;

    #[ORM\Column(name: 'shipping_postal', length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'Postal code cannot exceed 20 characters.')]
    #[Assert\Regex(pattern: '/^\d{4,10}$/', message: 'Postal code must contain only digits (4–10 numbers).')]
    private ?string $shippingPostal = null;

    #[ORM\Column(name: 'shipping_email', length: 150, nullable: true)]
    #[Assert\Email(message: 'Enter a valid email.')]
    #[Assert\Length(max: 150, maxMessage: 'Email cannot exceed 150 characters.')]
    private ?string $shippingEmail = null;

    #[ORM\Column(name: 'shipping_phone', length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^\+?[0-9\s\-]{8,20}$/', message: 'Phone must be 8-20 digits (optionally starting with +).')]
    private ?string $shippingPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'Notes cannot exceed 1000 characters.')]
    private ?string $notes = null;

    #[ORM\Column(name: 'order_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $orderDate = null;

    #[ORM\Column(name: 'delivery_date', type: 'date', nullable: true)]
    private ?\DateTimeInterface $deliveryDate = null;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(name: 'cancelled_reason', type: 'text', nullable: true)]
    private ?string $cancelledReason = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getCustomer(): ?User { return $this->customer; }
    public function setCustomer(?User $u): static { $this->customer = $u; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $p): static { $this->product = $p; return $this; }

    public function getSeller(): ?User { return $this->seller; }
    public function setSeller(?User $u): static { $this->seller = $u; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $q): static { $this->quantity = $q; return $this; }

    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $p): static { $this->unitPrice = $p; return $this; }

    public function getTotalPrice(): string { return $this->totalPrice; }
    public function setTotalPrice(string $p): static { $this->totalPrice = $p; return $this; }

    public function getStatus(): ?string
    {
        return $this->normalizeStatus($this->status);
    }

    public function setStatus(?string $s): static
    {
        $this->status = $this->normalizeStatus($s);
        return $this;
    }

    public static function getAllowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PREPARING => 'Preparing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getStatusColorMap(): array
    {
        return [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'primary',
            self::STATUS_PREPARING => 'info',
            self::STATUS_SHIPPED => 'primary',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_CANCELLED => 'danger',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::getStatusLabels()[$this->getStatus()] ?? ucfirst((string) $this->getStatus());
    }

    public function getStatusColor(): string
    {
        return self::getStatusColorMap()[$this->getStatus()] ?? 'secondary';
    }

    public function getStatusProgressPercent(): int
    {
        $status = $this->getStatus();

        if ($status === self::STATUS_CANCELLED) {
            return 100;
        }

        $index = array_search($status, self::FLOW_STATUSES, true);

        if ($index === false) {
            return 0;
        }

        return (int) round((($index + 1) / count(self::FLOW_STATUSES)) * 100);
    }

    public function getStatusStepNumber(): int
    {
        $status = $this->getStatus();

        if ($status === self::STATUS_CANCELLED) {
            return 0;
        }

        $index = array_search($status, self::FLOW_STATUSES, true);

        return $index === false ? 0 : $index + 1;
    }

    public function getShippingAddress(): string { return $this->shippingAddress; }
    public function setShippingAddress(string $a): static { $this->shippingAddress = $a; return $this; }

    public function getShippingCity(): ?string { return $this->shippingCity; }
    public function setShippingCity(?string $v): static { $this->shippingCity = $v; return $this; }

    public function getShippingPostal(): ?string { return $this->shippingPostal; }
    public function setShippingPostal(?string $v): static { $this->shippingPostal = $v; return $this; }

    public function getShippingEmail(): ?string { return $this->shippingEmail; }
    public function setShippingEmail(?string $v): static { $this->shippingEmail = $v; return $this; }

    public function getShippingPhone(): ?string { return $this->shippingPhone; }
    public function setShippingPhone(?string $v): static { $this->shippingPhone = $v; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $v): static { $this->notes = $v; return $this; }

    public function getOrderDate(): ?\DateTimeInterface { return $this->orderDate; }
    public function setOrderDate(?\DateTimeInterface $v): static { $this->orderDate = $v; return $this; }

    public function getDeliveryDate(): ?\DateTimeInterface { return $this->deliveryDate; }
    public function setDeliveryDate(?\DateTimeInterface $v): static { $this->deliveryDate = $v; return $this; }

    public function getCancelledAt(): ?\DateTimeInterface { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTimeInterface $v): static { $this->cancelledAt = $v; return $this; }

    public function getCancelledReason(): ?string { return $this->cancelledReason; }
    public function setCancelledReason(?string $v): static { $this->cancelledReason = $v; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod ?? self::PAYMENT_METHOD_CASH;
    }

    public function setPaymentMethod(?string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod ?: self::PAYMENT_METHOD_CASH;

        return $this;
    }

    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus ?? self::PAYMENT_STATUS_PENDING;
    }

    public function setPaymentStatus(?string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus ?: self::PAYMENT_STATUS_PENDING;

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->getPaymentMethod()) {
            self::PAYMENT_METHOD_STRIPE => 'Stripe',
            default => 'Cash on delivery',
        };
    }

    public function getPaymentStatusLabel(): string
    {
        return match ($this->getPaymentStatus()) {
            self::PAYMENT_STATUS_PAID => 'Paid',
            self::PAYMENT_STATUS_FAILED => 'Failed',
            self::PAYMENT_STATUS_CANCELLED => 'Cancelled',
            default => 'Pending',
        };
    }

    public function getPaymentStatusColor(): string
    {
        return match ($this->getPaymentStatus()) {
            self::PAYMENT_STATUS_PAID => 'success',
            self::PAYMENT_STATUS_FAILED => 'danger',
            self::PAYMENT_STATUS_CANCELLED => 'secondary',
            default => 'warning',
        };
    }

    private function normalizeStatus(?string $status): ?string
    {
        $status = $status !== null ? strtolower(trim($status)) : null;

        if ($status === null || $status === '') {
            return self::STATUS_PENDING;
        }

        if ($status === 'processing') {
            return self::STATUS_PREPARING;
        }

        return $status;
    }
}
