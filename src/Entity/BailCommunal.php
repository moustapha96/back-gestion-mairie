<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BailCommunalRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ORM\Entity(repositoryClass: BailCommunalRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['bail-communal:item']]),
        new Post(normalizationContext: ['groups' => ['bail-communal:write']]),
        new GetCollection(normalizationContext: ['groups' => ['bail-communal:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_bail_communals`')]

class BailCommunal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dureeBail = null;

    #[ORM\Column(nullable: true)]
    private ?float $montantRedevance = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $modalitePaiement = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $clausseObligation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceBail = null;

    #[ORM\OneToOne(inversedBy: 'bailCommunal', cascade: ['persist', 'remove'])]
    private ?DocumentGenere $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDureeBail(): ?string
    {
        return $this->dureeBail;
    }

    public function setDureeBail(?string $dureeBail): static
    {
        $this->dureeBail = $dureeBail;

        return $this;
    }

    public function getMontantRedevance(): ?float
    {
        return $this->montantRedevance;
    }

    public function setMontantRedevance(?float $montantRedevance): static
    {
        $this->montantRedevance = $montantRedevance;

        return $this;
    }

    public function getModalitePaiement(): ?string
    {
        return $this->modalitePaiement;
    }

    public function setModalitePaiement(?string $modalitePaiement): static
    {
        $this->modalitePaiement = $modalitePaiement;

        return $this;
    }

    public function getClausseObligation(): ?string
    {
        return $this->clausseObligation;
    }

    public function setClausseObligation(?string $clausseObligation): static
    {
        $this->clausseObligation = $clausseObligation;

        return $this;
    }

    public function getReferenceBail(): ?string
    {
        return $this->referenceBail;
    }

    public function setReferenceBail(?string $referenceBail): static
    {
        $this->referenceBail = $referenceBail;

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
            'dureeBail' => $this->getDureeBail(),
            'montantRedevance' => $this->getMontantRedevance(),
            'modalitePaiement' => $this->getModalitePaiement(),
            'clausseObligation' => $this->getClausseObligation(),
            'referenceBail' => $this->getReferenceBail(),
            // 'document' => $this->getDocument() ? $this->getDocument()->toArray() : null,
        ];
    }
}
