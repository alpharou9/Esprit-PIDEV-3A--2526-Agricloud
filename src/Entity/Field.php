<?php

namespace App\Entity;

use App\Repository\FieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FieldRepository::class)]
class Field
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $area = null;

    #[ORM\Column(length: 255)]
    private ?string $soilType = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $Farmid = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getArea(): ?float
    {
        return $this->area;
    }

    public function setArea(float $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function getSoilType(): ?string
    {
        return $this->soilType;
    }

    public function setSoilType(string $soilType): static
    {
        $this->soilType = $soilType;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFarmid(): ?Farm
    {
        return $this->Farmid;
    }

    public function setFarmid(?Farm $Farmid): static
    {
        $this->Farmid = $Farmid;

        return $this;
    }
}
