<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\HistoriqueValidationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: HistoriqueValidationRepository::class)]
#[ORM\Table(name: '`gs_mairie_historique_validations`')]
#[ApiResource(
    normalizationContext: [
        'groups' => ['historique:item', 'historique:list'],
        'enable_max_depth' => true
    ],
    denormalizationContext: ['groups' => ['historique:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]
class HistoriqueValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['historique:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DemandeTerrain::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['historique:read'])]
    private ?DemandeTerrain $demande = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['historique:read'])]
    private ?User $validateur = null;

    #[ORM\Column(length: 50)]
    #[Groups(['historique:read'])]
    private ?string $action = null; // "validé" ou "rejeté"

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['historique:read'])]
    private ?string $motif = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['historique:read'])]
    private ?\DateTimeInterface $dateAction = null;

    public function __construct()
    {
        $this->dateAction = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDemande(): ?DemandeTerrain
    {
        return $this->demande;
    }

    public function setDemande(?DemandeTerrain $demande): static
    {
        $this->demande = $demande;
        return $this;
    }

    public function getValidateur(): ?User
    {
        return $this->validateur;
    }

    public function setValidateur(?User $validateur): static
    {
        $this->validateur = $validateur;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;
        return $this;
    }

    public function getDateAction(): ?\DateTimeInterface
    {
        return $this->dateAction;
    }

    public function setDateAction(\DateTimeInterface $dateAction): static
    {
        $this->dateAction = $dateAction;
        return $this;
    }
}
