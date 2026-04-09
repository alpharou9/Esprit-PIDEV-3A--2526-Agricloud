<?php

namespace App\Entity;

use App\Repository\FieldRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FieldRepository::class)]
#[ORM\Table(name: 'fields')]
class Field
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Farm::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(name: 'farm_id', referencedColumnName: 'id', nullable: false)]
    private ?Farm $farm = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Field name is required.')]
    #[Assert\Length(min: 2, minMessage: 'Name must be at least 2 characters.')]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Area is required.')]
    #[Assert\Positive(message: 'Area must be a positive number.')]
    private string $area;

    #[ORM\Column(name: 'soil_type', length: 50, nullable: true)]
    private ?string $soilType = null;

    #[ORM\Column(name: 'crop_type', length: 50, nullable: true)]
    private ?string $cropType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $coordinates = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'active';

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getFarm(): ?Farm { return $this->farm; }
    public function setFarm(?Farm $farm): static { $this->farm = $farm; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getArea(): string { return $this->area; }
    public function setArea(string $area): static { $this->area = $area; return $this; }

    public function getSoilType(): ?string { return $this->soilType; }
    public function setSoilType(?string $soilType): static { $this->soilType = $soilType; return $this; }

    public function getCropType(): ?string { return $this->cropType; }
    public function setCropType(?string $cropType): static { $this->cropType = $cropType; return $this; }

    public function getCoordinates(): ?array { return $this->coordinates; }
    public function setCoordinates(?array $coordinates): static { $this->coordinates = $coordinates; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
