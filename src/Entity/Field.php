<?php

namespace App\Entity;

use App\Repository\FieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FieldRepository::class)]
class Field
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false)]
    private ?farm $farmid = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $area = null;

    #[ORM\Column(length: 100)]
    private ?string $soiltype = null;

    #[ORM\Column(length: 100)]
    private ?string $coiltype = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

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

    public function getFarmid(): ?farm
    {
        return $this->farmid;
    }

    public function setFarmid(?farm $farmid): static
    {
        $this->farmid = $farmid;

        return $this;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(string $area): static
    {
        $this->area = $area;

        return $this;
    }

    public function getSoiltype(): ?string
    {
        return $this->soiltype;
    }

    public function setSoiltype(string $soiltype): static
    {
        $this->soiltype = $soiltype;

        return $this;
    }

    public function getCoiltype(): ?string
    {
        return $this->coiltype;
    }

    public function setCoiltype(string $coiltype): static
    {
        $this->coiltype = $coiltype;

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
