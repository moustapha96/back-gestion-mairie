<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PermisOccupationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ORM\Entity(repositoryClass: PermisOccupationRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['permisOccupation:item']]),
        new Post(normalizationContext: ['groups' => ['permisOccupation:write']]),
        new GetCollection(normalizationContext: ['groups' => ['permisOccupation:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_permis_occupations`')]
class PermisOccupation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroPermis = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDelivrance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dureeValidite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsOccupation = null;

    #[ORM\OneToOne(inversedBy: 'permisOccupation', cascade: ['persist', 'remove'])]
    private ?DocumentGenere $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroPermis(): ?string
    {
        return $this->numeroPermis;
    }

    public function setNumeroPermis(?string $numeroPermis): static
    {
        $this->numeroPermis = $numeroPermis;

        return $this;
    }

    public function getDateDelivrance(): ?\DateTimeInterface
    {
        return $this->dateDelivrance;
    }

    public function setDateDelivrance(?\DateTimeInterface $dateDelivrance): static
    {
        $this->dateDelivrance = $dateDelivrance;

        return $this;
    }

    public function getDureeValidite(): ?string
    {
        return $this->dureeValidite;
    }

    public function setDureeValidite(?string $dureeValidite): static
    {
        $this->dureeValidite = $dureeValidite;

        return $this;
    }

    public function getConditionsOccupation(): ?string
    {
        return $this->conditionsOccupation;
    }

    public function setConditionsOccupation(?string $conditionsOccupation): static
    {
        $this->conditionsOccupation = $conditionsOccupation;

        return $this;
    }

    public function getDocument(): ?DocumentGenere
    {
        return $this->document;
    }

    public function setDocument(?DocumentGenere $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'numeroPermis' => $this->getNumeroPermis(),
            'dateDelivrance' => $this->getDateDelivrance() ? $this->getDateDelivrance()->format('Y-m-d') : null,
            'dureeValidite' => $this->getDureeValidite(),
            'conditionsOccupation' => $this->getConditionsOccupation(),
            // 'document' => $this->getDocument() ? $this->getDocument()->toArray() : null,
        ];
    }
}
