<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'This email is already registered.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(min: 2, minMessage: 'Name must be at least 2 characters.')]
    private string $name;

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Enter a valid email address.')]
    private string $email;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s\-]{8,20}$/',
        message: 'Invalid phone number format.'
    )]
    private ?string $phone = null;

    #[ORM\Column(name: 'profile_picture', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(length: 20, nullable: true, options: ['default' => 'active'])]
    private ?string $status = 'active';

    #[ORM\Column(name: 'email_verified_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'oauth_provider', length: 20, nullable: true)]
    private ?string $oauthProvider = null;

    #[ORM\Column(name: 'oauth_id', length: 255, nullable: true)]
    private ?string $oauthId = null;

    #[ORM\Column(name: 'face_embeddings', type: 'text', nullable: true)]
    private ?string $faceEmbeddings = null;

    #[ORM\Column(name: 'face_enrolled_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $faceEnrolledAt = null;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', nullable: true)]
    private ?Role $role = null;

    /** Plain password — not persisted, used only in forms */
    #[Assert\Length(min: 6, minMessage: 'Password must be at least 6 characters.')]
    private ?string $plainPassword = null;

    // --- UserInterface ---

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->role) {
            $roles[] = 'ROLE_' . strtoupper($this->role->getName());
        }
        return array_unique($roles);
    }

    public function getPassword(): ?string { return $this->password; }

    public function eraseCredentials(): void { $this->plainPassword = null; }

    // --- Getters / Setters ---

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function setPassword(?string $password): static { $this->password = $password; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getProfilePicture(): ?string { return $this->profilePicture; }
    public function setProfilePicture(?string $p): static { $this->profilePicture = $p; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }

    public function getRole(): ?Role { return $this->role; }
    public function setRole(?Role $role): static { $this->role = $role; return $this; }

    public function getEmailVerifiedAt(): ?\DateTimeInterface { return $this->emailVerifiedAt; }
    public function setEmailVerifiedAt(?\DateTimeInterface $v): static { $this->emailVerifiedAt = $v; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }

    public function getOauthProvider(): ?string { return $this->oauthProvider; }
    public function setOauthProvider(?string $v): static { $this->oauthProvider = $v; return $this; }

    public function getOauthId(): ?string { return $this->oauthId; }
    public function setOauthId(?string $v): static { $this->oauthId = $v; return $this; }

    public function getFaceEmbeddings(): ?string { return $this->faceEmbeddings; }
    public function setFaceEmbeddings(?string $v): static { $this->faceEmbeddings = $v; return $this; }

    public function getFaceEnrolledAt(): ?\DateTimeInterface { return $this->faceEnrolledAt; }
    public function setFaceEnrolledAt(?\DateTimeInterface $v): static { $this->faceEnrolledAt = $v; return $this; }

    public function getPlainPassword(): ?string { return $this->plainPassword; }
    public function setPlainPassword(?string $plainPassword): static { $this->plainPassword = $plainPassword; return $this; }
}
