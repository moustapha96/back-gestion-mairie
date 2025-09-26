<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Controller\DemandeController;
use App\Repository\DemandeTerrainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\TextType;
use Dom\Text;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;



#[ORM\Entity(repositoryClass: DemandeTerrainRepository::class)]

#[ApiResource(
    normalizationContext: [
        'groups' => ['demande:item', 'demande:list'],
        'enable_max_depth' => true
    ],
    denormalizationContext: ['groups' => ['demande:write']],
    order: ["id" => "DESC"],
    paginationEnabled: true,
    paginationItemsPerPage: 10,
    paginationMaximumItemsPerPage: 50,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
)]
#[ORM\Table(name: '`gs_mairie_demande_terrains`')]
class DemandeTerrain
{


    const STATUT_REJETE = 'Rejetée';
    const STATUT_APPROUVE = 'Approuvée';
    const STATUT_EN_ATTENTE = 'En attente';
    const STATUT_EN_COURS_TRAITEMENT = 'En cours de traitement';


    const PERMIS_OCCUPATION  = "Permis d'occuper";
    const BAIL_COMMUNAL = "Bail communal";
    const PROPOSITION_BAIL = "Proposition de bail";
    const TRANSFERT_DEFINITIF = "Transfert définitif";


    const TYPE_DEMANDE_ATTRIBUTION = "Attribution";
    const TYPE_DEMANDE_REGULARISATION = "Régularisation";
    const TYPE_DEMANDE_AUTHENTIFICATION = "Authentification";




    #[ORM\Id]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    #[ORM\Column(length: 30)]
    private ?string $typeDemande = null;

    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    #[ORM\Column]
    private ?float $superficie = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $usagePrevu = null;


    #[ORM\Column(nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?bool $possedeAutreTerrain = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $motif_refus = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $document = null;

    #[ORM\ManyToOne(inversedBy: 'demandes', fetch: 'EAGER')]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'localite:item', 'localite:list'])]
    #[MaxDepth(1)]
    private ?User $utilisateur = null;


    #[ORM\ManyToOne(inversedBy: 'demandes', fetch: 'EAGER')]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?Localite $localite = null;


    #[ORM\Column(length: 255)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $typeDocument = null;


    #[ORM\OneToOne(mappedBy: 'demandeTerrain', cascade: ['persist', 'remove'], orphanRemoval: true, fetch: 'EAGER')]
    #[Groups(['demande:list', 'demande:item', 'demande:write'])]
    private ?DocumentGenere $documentGenerer = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verso = null;

    #[ORM\Column(length: 255, nullable: true)]
     #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $typeTitre = null;



    // Ajoutez ces propriétés dans la classe
    #[ORM\ManyToOne(targetEntity: NiveauValidation::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write'])]
    private ?NiveauValidation $niveauValidationActuel = null;

    #[ORM\OneToMany(mappedBy: 'demande', targetEntity: HistoriqueValidation::class)]
    #[Groups(['demande:item'])]
    private Collection $historiqueValidations;

    #[ORM\Column(nullable: true)]
     #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]   
    private ?bool $terrainAKaolack = null;

    #[ORM\Column(nullable: true)]
     #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?bool $terrainAilleurs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
     #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $decisionCommission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
     #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $rapport = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
     #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]
    private ?string $recommandation = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->historiqueValidations = new ArrayCollection();
        $this->statut = self::STATUT_EN_ATTENTE;

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDemande(): ?string
    {
        return $this->typeDemande;
    }

    public function setTypeDemande(string $typeDemande): static
    {
        $this->typeDemande = $typeDemande;
        return $this;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(float $superficie): static
    {
        $this->superficie = $superficie;

        return $this;
    }

    public function getUsagePrevu(): ?string
    {
        return $this->usagePrevu;
    }

    public function setUsagePrevu(?string $usagePrevu): static
    {
        $this->usagePrevu = $usagePrevu;

        return $this;
    }

    public function isPossedeAutreTerrain(): ?bool
    {
        return $this->possedeAutreTerrain;
    }

    public function setPossedeAutreTerrain(?bool $possedeAutreTerrain): static
    {
        $this->possedeAutreTerrain = $possedeAutreTerrain;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        if (!in_array($statut, [
            self::STATUT_EN_ATTENTE,
            self::STATUT_EN_COURS_TRAITEMENT,
            self::STATUT_REJETE,
            self::STATUT_APPROUVE
        ])) {
            throw new \InvalidArgumentException("Statut de la demande invalide: " . $statut);
        }
        $this->statut = $statut;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getDocumentGenerer(): ?DocumentGenere
    {
        return $this->documentGenerer ? $this->documentGenerer : null;
    }

    public function getLocalite(): ?Localite
    {
        return $this->localite;
    }

    public function setLocalite(?Localite $localite): static
    {
        $this->localite = $localite;

        return $this;
    }

    public function toArray(): array
    {
        return [

            'id' => $this->getId(),
            'typeDemande' => $this->getTypeDemande(),
            'typeDocument' => $this->getTypeDocument(),
            'superficie' => $this->getSuperficie(),
            'usagePrevu' => $this->getUsagePrevu(),
            'possedeAutreTerrain' => $this->isPossedeAutreTerrain(),
            'statut' => $this->getStatut(),
            'dateCreation' => $this->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $this->getDateModification()?->format('Y-m-d H:i:s'),
            'motif_refus' => $this->getMotifRefus(),
            'document' => $this->getDocument(),
            'demandeur' => $this->getUtilisateur() ? [
                'id' => $this->getUtilisateur()->getId(),
                'nom' => $this->getUtilisateur()->getNom(),
                'prenom' => $this->getUtilisateur()->getPrenom(),
                'email' => $this->getUtilisateur()->getEmail(),
                'telephone' => $this->getUtilisateur()->getTelephone(),
                'lieuNaissance' => $this->getUtilisateur()->getLieuNaissance(),
                'dateNaissance' => $this->getUtilisateur()->getDateNaissance()?->format('Y-m-d H:i:s'),
                'numeroElecteur' => $this->getUtilisateur()->getNumeroElecteur(),
                'profession' => $this->getUtilisateur()->getProfession(),
                'adresse' => $this->getUtilisateur()->getAdresse(),
            ] : null,
            'localite' => $this->getLocalite() ? [
                'id' => $this->getLocalite()->getId(),
                'nom' => $this->getLocalite()->getNom(),
            ] : null
        ];
    }

    public function getTypeDocument(): ?string
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(string $typeDocument): self
    {
        $this->typeDocument = $typeDocument;

        return $this;
    }


    public function getRecto(): ?string
    {
        return $this->recto;
    }

    public function setRecto(?string $recto): static
    {
        $this->recto = $recto;

        return $this;
    }

    public function getVerso(): ?string
    {
        return $this->verso;
    }

    public function setVerso(?string $verso): static
    {
        $this->verso = $verso;

        return $this;
    }


    public function setDocumentGenerer(?DocumentGenere $documentGenerer): self
    {
        $this->documentGenerer = $documentGenerer;
        if ($documentGenerer !== null) {
            $documentGenerer->setDemandeTerrain($this);
        }
        return $this;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motif_refus;
    }

    public function setMotifRefus(?string $motif_refus): static
    {
        $this->motif_refus = $motif_refus;

        return $this;
    }


    public function getTypeTitre(): ?string
    {
        return $this->typeTitre;
    }

    public function setTypeTitre(?string $typeTitre): static
    {
        if (!in_array(
            $typeTitre,

            [
                self::PERMIS_OCCUPATION,
                self::BAIL_COMMUNAL,
                self::TRANSFERT_DEFINITIF,
                self::PROPOSITION_BAIL,
            ]
        )) {
            throw new \InvalidArgumentException("Type de document invalide: " . $typeTitre);
        }

        $this->typeTitre = $typeTitre;
        return $this;
    }

    // Ajoutez ces getters/setters
    public function getNiveauValidationActuel(): ?NiveauValidation
    {
        return $this->niveauValidationActuel;
    }

    public function setNiveauValidationActuel(?NiveauValidation $niveauValidationActuel): static
    {
        $this->niveauValidationActuel = $niveauValidationActuel;
        return $this;
    }

    public function getHistoriqueValidations(): Collection
    {
        return $this->historiqueValidations;
    }

    public function addHistoriqueValidation(HistoriqueValidation $historiqueValidation): static
    {
        if (!$this->historiqueValidations->contains($historiqueValidation)) {
            $this->historiqueValidations->add($historiqueValidation);
            $historiqueValidation->setDemande($this);
        }
        return $this;
    }

    public function isTerrainAKaolack(): ?bool
    {
        return $this->terrainAKaolack;
    }

    public function setTerrainAKaolack(?bool $terrainAKaolack): static
    {
        $this->terrainAKaolack = $terrainAKaolack;

        return $this;
    }

    public function isTerrainAilleurs(): ?bool
    {
        return $this->terrainAilleurs;
    }

    public function setTerrainAilleurs(?bool $terrainAilleurs): static
    {
        $this->terrainAilleurs = $terrainAilleurs;

        return $this;
    }


    public function getDecisionCommission(): ?string
    {
        return $this->decisionCommission;
    }

    public function setDecisionCommission(?string $decisionCommission): static
    {
        $this->decisionCommission = $decisionCommission;

        return $this;
    }

    public function getRapport(): ?string
    {
        return $this->rapport;
    }

    public function setRapport(?string $rapport): static
    {
        $this->rapport = $rapport;

        return $this;
    }

    public function getRecommandation(): ?string
    {
        return $this->recommandation;
    }

    public function setRecommandation(?string $recommandation): static
    {
        $this->recommandation = $recommandation;

        return $this;
    }
}
