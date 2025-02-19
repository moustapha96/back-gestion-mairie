<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CalculRedevanceRepository;
use Doctrine\ORM\Mapping as ORM;


use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ORM\Entity(repositoryClass: CalculRedevanceRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['calcul-redevance:item']]),
        new Post(normalizationContext: ['groups' => ['calcul-redevance:write']]),
        new GetCollection(normalizationContext: ['groups' => ['calcul-redevance:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_calcul_redevances`')]
#[ORM\UniqueConstraint(columns: ['id'])]
class CalculRedevance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $baseCalcul = null;

    #[ORM\Column(nullable: true)]
    private ?int $taux = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $montantRedevanceCalcule = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formuleCalcul = null;

    #[ORM\OneToOne(inversedBy: 'calculRedevance', cascade: ['persist', 'remove'])]
    private ?DocumentGenere $document = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseCalcul(): ?string
    {
        return $this->baseCalcul;
    }

    public function setBaseCalcul(?string $baseCalcul): static
    {
        $this->baseCalcul = $baseCalcul;

        return $this;
    }

    public function getTaux(): ?int
    {
        return $this->taux;
    }

    public function setTaux(?int $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getMontantRedevanceCalcule(): ?string
    {
        return $this->montantRedevanceCalcule;
    }

    public function setMontantRedevanceCalcule(?string $montantRedevanceCalcule): static
    {
        $this->montantRedevanceCalcule = $montantRedevanceCalcule;

        return $this;
    }

    public function getFormuleCalcul(): ?string
    {
        return $this->formuleCalcul;
    }

    public function setFormuleCalcul(?string $formuleCalcul): static
    {
        $this->formuleCalcul = $formuleCalcul;

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
            'baseCalcul' => $this->getBaseCalcul(),
            'taux' => $this->getTaux(),
            'montantRedevanceCalcule' => $this->getMontantRedevanceCalcule(),
            'formuleCalcul' => $this->getFormuleCalcul(),
            // 'document' => $this->getDocument() ? $this->getDocument()->toArray() : null,
        ];
    }
}
