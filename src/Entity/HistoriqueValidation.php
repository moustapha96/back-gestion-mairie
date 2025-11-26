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
    #[Groups(['historique:read', 'historique:write'])]
    private ?DemandeTerrain $demande = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?User $validateur = null;

    /** "valide" | "rejete" */
    #[ORM\Column(length: 50)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?string $action = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?string $motif = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['historique:read'])]
    private ?\DateTimeInterface $dateAction = null;

    /** --- Snapshots utiles pour audit --- */

    // Nom du niveau au moment de l’action
    #[ORM\Column(length: 150, nullable: true)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?string $niveauNom = null;

    // Ordre du niveau au moment de l’action
    #[ORM\Column(nullable: true)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?int $niveauOrdre = null;

    // Rôle requis au moment de l’action
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?string $roleRequis = null;

    // (Optionnel) statut avant/après
    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?string $statutAvant = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['historique:read', 'historique:write'])]
    private ?string $statutApres = null;

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

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'demande' => $this->getDemande() ? $this->getDemande()->toArraySimple() : null,
            'validateur' => $this->getValidateur() ? $this->getValidateur()->toArray() : null,
            'action' => $this->getAction(),
            'motif' => $this->getMotif(),
            'dateAction' => $this->getDateAction() ? $this->getDateAction()->format('Y-m-d H:i:s') : null,
        ];
    }

    public function getNiveauNom(): ?string
    {
        return $this->niveauNom;
    }

    public function setNiveauNom(?string $niveauNom): static
    {
        $this->niveauNom = $niveauNom;

        return $this;
    }

    public function getNiveauOrdre(): ?int
    {
        return $this->niveauOrdre;
    }

    public function setNiveauOrdre(?int $niveauOrdre): static
    {
        $this->niveauOrdre = $niveauOrdre;

        return $this;
    }

    public function getRoleRequis(): ?string
    {
        return $this->roleRequis;
    }

    public function setRoleRequis(?string $roleRequis): static
    {
        $this->roleRequis = $roleRequis;

        return $this;
    }

    public function getStatutAvant(): ?string
    {
        return $this->statutAvant;
    }

    public function setStatutAvant(?string $statutAvant): static
    {
        $this->statutAvant = $statutAvant;

        return $this;
    }

    public function getStatutApres(): ?string
    {
        return $this->statutApres;
    }

    public function setStatutApres(?string $statutApres): static
    {
        $this->statutApres = $statutApres;

        return $this;
    }
}
