<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Title is required.')]
    #[Assert\Length(min: 3, max: 200, minMessage: 'Title must be at least 3 characters.', maxMessage: 'Title cannot exceed 200 characters.')]
    private string $title = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Description is required.')]
    #[Assert\Length(min: 10, minMessage: 'Description must be at least 10 characters.')]
    private string $description = '';

    #[ORM\Column(name: 'event_date', type: 'datetime')]
    #[Assert\NotBlank(message: 'Event date is required.')]
    #[Assert\GreaterThan('today', message: 'Event date must be in the future.')]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    #[Assert\Expression(
        expression: 'this.getEndDate() === null or this.getEventDate() === null or this.getEndDate() > this.getEventDate()',
        message: 'End date must be after the event date.'
    )]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Location is required.')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Location must be at least 2 characters.')]
    private string $location = '';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive(message: 'Capacity must be a positive number.')]
    private ?int $capacity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(length: 20)]
    private string $status = 'upcoming';

    #[ORM\Column(name: 'registration_deadline', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $registrationDeadline = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Participation::class, mappedBy: 'event', cascade: ['remove'])]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getEventDate(): ?\DateTimeInterface { return $this->eventDate; }
    public function setEventDate(?\DateTimeInterface $v): static { $this->eventDate = $v; return $this; }

    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $v): static { $this->endDate = $v; return $this; }

    public function getLocation(): string { return $this->location; }
    public function setLocation(string $location): static { $this->location = $location; return $this; }

    public function getLatitude(): ?string { return $this->latitude; }
    public function setLatitude(?string $v): static { $this->latitude = $v; return $this; }

    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $v): static { $this->longitude = $v; return $this; }

    public function getCapacity(): ?int { return $this->capacity; }
    public function setCapacity(?int $capacity): static { $this->capacity = $capacity; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getRegistrationDeadline(): ?\DateTimeInterface { return $this->registrationDeadline; }
    public function setRegistrationDeadline(?\DateTimeInterface $v): static { $this->registrationDeadline = $v; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }

    public function getParticipations(): Collection { return $this->participations; }

    public function getConfirmedCount(): int
    {
        return $this->participations->filter(
            fn(Participation $p) => in_array($p->getStatus(), ['confirmed', 'attended'])
        )->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->getConfirmedCount() >= $this->capacity;
    }

    public function isRegistrationOpen(): bool
    {
        if ($this->status === 'cancelled' || $this->status === 'completed') return false;
        if ($this->registrationDeadline && new \DateTime() > $this->registrationDeadline) return false;
        return true;
    }
}
