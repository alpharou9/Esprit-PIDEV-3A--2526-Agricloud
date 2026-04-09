<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'parent_comment_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Comment $parent = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'parent', cascade: ['remove'])]
    private Collection $replies;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Comment cannot be empty.')]
    #[Assert\Length(min: 2, minMessage: 'Comment must be at least 2 characters.')]
    private string $content = '';

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(name: 'approved_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by', referencedColumnName: 'id', nullable: true)]
    private ?User $approvedBy = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $post): static { $this->post = $post; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getParent(): ?Comment { return $this->parent; }
    public function setParent(?Comment $parent): static { $this->parent = $parent; return $this; }

    public function getReplies(): Collection { return $this->replies; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getApprovedAt(): ?\DateTimeInterface { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeInterface $v): static { $this->approvedAt = $v; return $this; }

    public function getApprovedBy(): ?User { return $this->approvedBy; }
    public function setApprovedBy(?User $u): static { $this->approvedBy = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $v): static { $this->updatedAt = $v; return $this; }
}
