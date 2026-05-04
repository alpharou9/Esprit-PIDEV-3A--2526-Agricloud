<?php

namespace App\Entity;

use App\Repository\ParticipationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participations')]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'registration_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $registrationDate = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'cancelled_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $cancelledAt = null;

    #[ORM\Column(name: 'cancelled_reason', type: 'text', nullable: true)]
    private ?string $cancelledReason = null;

    #[ORM\Column(type: 'boolean')]
    private bool $attended = false;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getEvent(): ?Event { return $this->event; }
    public function setEvent(?Event $event): static { $this->event = $event; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRegistrationDate(): ?\DateTimeInterface { return $this->registrationDate; }
    public function setRegistrationDate(?\DateTimeInterface $v): static { $this->registrationDate = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCancelledAt(): ?\DateTimeInterface { return $this->cancelledAt; }
    public function setCancelledAt(?\DateTimeInterface $v): static { $this->cancelledAt = $v; return $this; }

    public function getCancelledReason(): ?string { return $this->cancelledReason; }
    public function setCancelledReason(?string $v): static { $this->cancelledReason = $v; return $this; }

    public function isAttended(): bool { return $this->attended; }
    public function setAttended(bool $attended): static { $this->attended = $attended; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }
}
