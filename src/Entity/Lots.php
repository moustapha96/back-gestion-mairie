<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LotsRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LotsRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['lot:item']]),
        new Post(normalizationContext: ['groups' => ['lot:write']]),
        new GetCollection(normalizationContext: ['groups' => ['lot:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_lots`')]
class Lots
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255,nullable: true)]
    private ?string $numeroLot = null;

    #[ORM\Column(nullable: true)]
    private ?float $superficie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeUsage  = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    private ?float $prix = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $longitude = null;

    #[ORM\ManyToOne(inversedBy: 'lots', fetch: 'EAGER')]
    #[Groups(['lot:item', 'lot:list'])]
    private ?Lotissement $lotissement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroLot(): ?string
    {
        return $this->numeroLot;
    }

    public function setNumeroLot(string $numeroLot): static
    {
        $this->numeroLot = $numeroLot;

        return $this;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(?float $superficie): static
    {
        $this->superficie = $superficie;

        return $this;
    }

    public function getUsage(): ?string
    {
        return $this->typeUsage;
    }

    public function setUsage(?string $usage): static
    {
        $this->typeUsage  = $usage;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLotissement(): ?Lotissement
    {
        return $this->lotissement;
    }

    public function setLotissement(?Lotissement $lotissement): static
    {
        $this->lotissement = $lotissement;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'numeroLot' => $this->getNumeroLot(),
            'superficie' => $this->getSuperficie(),
            'typeUsage' => $this->getUsage(),
            'statut' => $this->getStatut(),
            'prix' => $this->getPrix(),
            'lotissement' => $this->getLotissement() ? $this->getLotissement()->toArray() : null,
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
        ];
    }

    public function getTypeUsage(): ?string
    {
        return $this->typeUsage;
    }

    public function setTypeUsage(?string $typeUsage): static
    {
        $this->typeUsage = $typeUsage;

        return $this;
    }
}
