<?php

namespace App\Entity;

use App\Repository\ParcelleRepository;

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParcelleRepository::class)]

#[ApiResource(
    normalizationContext: ['groups' => ['parcelle:item', 'parcelle:list']],
    denormalizationContext: ['groups' => ['parcelle:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_parcelle`')]

class Parcelle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(groups: ['parcelle:list', 'parcelle:item', 'parcelle:write', 'lotissement:item', 'lotissement:list'])]
    private ?int $id = null;

    #[Groups(groups: [
        'parcelle:list',
        'parcelle:item',
        'parcelle:write',
        'lotissement:item',
        'lotissement:list'
    ])]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(nullable: true)]
    #[Groups(groups: [
        'parcelle:list',
        'parcelle:item',
        'parcelle:write',
        'lotissement:item',
        'lotissement:list'
    ])]
    private ?float $surface = null;

    #[ORM\ManyToOne(inversedBy: 'parcelles', fetch: 'EAGER')]
    #[Groups(groups: [
        'parcelle:list',
        'parcelle:item',
        'parcelle:write'
    ])]
    private ?Lotissement $lotissement = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(groups: [
        'parcelle:list',
        'parcelle:item',
        'parcelle:write'
    ])]
    private ?string $statut = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'parcelle:write'])]
    private ?float $latitude = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'parcelle:write'])]
    private ?float $longitude = null;

    #[ORM\ManyToOne(inversedBy: 'parcelles')]
    private ?User $proprietaire = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeParcelle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tfDe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getSurface(): ?float
    {
        return $this->surface;
    }

    public function setSurface(?float $surface): static
    {
        $this->surface = $surface;

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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'numero' => $this->getNumero(),
            'surface' => $this->getSurface(),
            'statut' => $this->getStatut(),
            'lotissement' => $this->getLotissement() ? $this->getLotissement()->toArray() : null,
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
        ];
    }

    public function getProprietaire(): ?User
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?User $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getTypeParcelle(): ?string
    {
        return $this->typeParcelle;
    }

    public function setTypeParcelle(?string $typeParcelle): static
    {
        $this->typeParcelle = $typeParcelle;

        return $this;
    }

    public function getTfDe(): ?string
    {
        return $this->tfDe;
    }

    public function setTfDe(?string $tfDe): static
    {
        $this->tfDe = $tfDe;

        return $this;
    }
}
