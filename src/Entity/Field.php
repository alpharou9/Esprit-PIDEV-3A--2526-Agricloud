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

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Farm $farm_id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $area = null;

    #[ORM\Column(length: 255)]
    private ?string $soil_type = null;

    #[ORM\Column(length: 255)]
    private ?string $crop_type = null;

    #[ORM\Column]
    private ?float $coordinates = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFarmId(): ?Farm
    {
        return $this->farm_id;
    }

    public function setFarmId(?Farm $farm_id): static
    {
        $this->farm_id = $farm_id;

        return $this;
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
        return $this->soil_type;
    }

    public function setSoilType(string $soil_type): static
    {
        $this->soil_type = $soil_type;

        return $this;
    }

    public function getCropType(): ?string
    {
        return $this->crop_type;
    }

    public function setCropType(string $crop_type): static
    {
        $this->crop_type = $crop_type;

        return $this;
    }

    public function getCoordinates(): ?float
    {
        return $this->coordinates;
    }

    public function setCoordinates(float $coordinates): static
    {
        $this->coordinates = $coordinates;

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
}
