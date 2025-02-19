<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PropositionBailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ORM\Entity(repositoryClass: PropositionBailRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['propositionBail:item']]),
        new Post(normalizationContext: ['groups' => ['propositionBail:write']]),
        new GetCollection(normalizationContext: ['groups' => ['propositionBail:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_proposition_bails`')]
class PropositionBail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dureeProposition = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantPropose = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $modaliteNegociation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsPartculiere = null;

    #[ORM\OneToOne(inversedBy: 'propositionBail', cascade: ['persist', 'remove'])]
    private ?DocumentGenere $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDureeProposition(): ?string
    {
        return $this->dureeProposition;
    }

    public function setDureeProposition(?string $dureeProposition): static
    {
        $this->dureeProposition = $dureeProposition;

        return $this;
    }

    public function getMontantPropose(): ?float
    {
        return $this->montantPropose;
    }

    public function setMontantPropose(?float $montantPropose): static
    {
        $this->montantPropose = $montantPropose;

        return $this;
    }

    public function getModaliteNegociation(): ?string
    {
        return $this->modaliteNegociation;
    }

    public function setModaliteNegociation(?string $modaliteNegociation): static
    {
        $this->modaliteNegociation = $modaliteNegociation;

        return $this;
    }

    public function getConditionsPartculiere(): ?string
    {
        return $this->conditionsPartculiere;
    }

    public function setConditionsPartculiere(?string $conditionsPartculiere): static
    {
        $this->conditionsPartculiere = $conditionsPartculiere;

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
            'dureeProposition' => $this->getDureeProposition(),
            'montantPropose' => $this->getMontantPropose(),
            'modaliteNegociation' => $this->getModaliteNegociation(),
            'conditionsPartculiere' => $this->getConditionsPartculiere(),
            // 'document' => $this->getDocument() ? $this->getDocument()->toArray() : null,
        ];
    }
}
