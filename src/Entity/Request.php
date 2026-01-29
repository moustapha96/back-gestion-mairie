<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\RequestRepository;
use Doctrine\ORM\Mapping as ORM;


use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

use App\services\FonctionsService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: RequestRepository::class)]


#[ApiResource(
    normalizationContext: [
        'groups' => ['demande_demandeur:item', 'demande_demandeur:list'],
        'enable_max_depth' => true
    ],
    denormalizationContext: ['groups' => ['demande_demandeur:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]
#[ORM\Table(name: 'gs_mairie_demandes')]


class Request
{
    const STATUT_REJETE = 'Rejetée';
    const STATUT_APPROUVE = 'Approuvée';
    const STATUT_EN_ATTENTE = 'En attente';
    const STATUT_EN_COURS_TRAITEMENT = 'En cours de traitement';

    const PERMIS_OCCUPATION = "Permis d'occuper";
    const BAIL_COMMUNAL = "Bail communal";
    const PROPOSITION_BAIL = "Proposition de bail";
    const TRANSFERT_DEFINITIF = "Transfert définitif";

    const TYPE_DEMANDE_ATTRIBUTION = "Attribution";
    const TYPE_DEMANDE_REGULARISATION = "Régularisation";
    const TYPE_DEMANDE_AUTHENTIFICATION = "Authentification";

    const SITATION_MATRIMONIALE_CELIBATAIRE = "Célibataire";
    const SITATION_MATRIMONIALE_MARIE = "Marié(e)";
    const SITATION_MATRIMONIALE_DIVORCE = "Divorcé(e)";
    const SITATION_MATRIMONIALE_VEUF = "Veuf(ve)";


    const SITUATION_DEMANDEUR_PROPRIETAIRE = "Propriétaire";
    const SITUATION_DEMANDEUR_LOCATAIRE = "Locataire";
    const SITUATION_DEMANDEUR_HEBERGER = "Hébergé(e)";



    #[ORM\Id]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    #[ORM\Column(length: 30)]
    private ?string $typeDemande = null;

    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    #[ORM\Column(nullable: true)]
    private ?float $superficie = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $usagePrevu = null;


    #[ORM\Column(nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?bool $possedeAutreTerrain = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $motif_refus = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?\DateTimeInterface $dateModification = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $typeDocument = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recto = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verso = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $typeTitre = null;


    #[ORM\Column(nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?bool $terrainAKaolack = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?bool $terrainAilleurs = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $decisionCommission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $rapport = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:item', 'demande_demandeur:list'])]
    private ?string $recommandation = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $profession = null;

    #[ORM\Column(length: 13, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['demande_demandeur:read', 'demande_demandeur:list', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    private ?string $numeroElecteur = null;

    #[ORM\Column(nullable: true)]
    private ?bool $habitant = null;


    #[Groups(['demande_demandeur:list', 'demande_demandeur:item', 'demande_demandeur:write', 'demande_demandeur:list', 'demande_demandeur:item'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;
    /**
     * @param PreUpdateEventArgs $event
     */
    #[PreUpdate]
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        // Check if numeroElecteur has changed
        if ($event->hasChangedField('numeroElecteur')) {
            $this->updateHabitantStatus();
        }
    }
    /**
     * @param PrePersistEventArgs $event
     */
    #[PrePersist]
    public function prePersist(PrePersistEventArgs $event): void
    {
        // Update habitant status before initial persist
        if ($this->getNumeroElecteur() !== null) {
            $this->updateHabitantStatus();
        }
    }
    private ?FonctionsService $fonctionsService = null;


    #[ORM\Column(length: 255, nullable: true)]
    private ?string $situationMatrimoniale = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreEnfant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statutLogement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $localite = null;

    #[ORM\ManyToOne(inversedBy: 'requests')]
    private ?Localite $quartier = null;




    #[ORM\ManyToOne(inversedBy: 'demande_demandeurs', fetch: 'LAZY')]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'localite:item', 'localite:list'])]
    #[MaxDepth(1)]
    private ?User $utilisateur = null;

    #[ORM\OneToOne(mappedBy: 'demande', cascade: ['persist', 'remove'])]
    private ?AttributionParcelle $parcelleAttribuer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numero = null;


    public function __construct(?FonctionsService $fonctionsService = null)
    {

        $this->fonctionsService = $fonctionsService;
        if ($fonctionsService !== null && $this->getNumeroElecteur() !== null) {
            $this->updateHabitantStatus();
        }
        $this->dateCreation = new \DateTime();
        $this->statut = self::STATUT_EN_ATTENTE;
        $this->numero = $this->generateCode();
    }

    public function generateCode(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');
        $hours = $date->format('H');
        $minutes = $date->format('i');
        return 'DP' . $year . $month . $day . $hours . $minutes;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    /**
     * Updates the habitant status by checking the numeroElecteur
     * This method should be called whenever numeroElecteur changes
     * 
     * @return void
     */
    public function updateHabitantStatus(): void
    {
        if ($this->fonctionsService === null || $this->getNumeroElecteur() === null) {
            return;
        }

        $resultat = $this->fonctionsService->checkNumeroElecteurExist($this->getNumeroElecteur());
        $this->habitant = $resultat ?? false;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDemande(): ?string
    {
        return $this->typeDemande;
    }

    public function setTypeDemande(?string $typeDemande): static
    {
        $this->typeDemande = $typeDemande;
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


    public static function statutsValides(): array
    {
        return [
            self::STATUT_REJETE,
            self::STATUT_APPROUVE,
            self::STATUT_EN_ATTENTE,
            self::STATUT_EN_COURS_TRAITEMENT,
        ];
    }


    public function setStatut(?string $statut): static
    {
        if ($statut === null) {
            $this->statut = null;
            return $this;
        }

        if (
            !in_array($statut, [
                self::STATUT_EN_ATTENTE,
                self::STATUT_EN_COURS_TRAITEMENT,
                self::STATUT_REJETE,
                self::STATUT_APPROUVE
            ])
        ) {
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


    public function getInformationDemandeur(): array
    {
        return [
            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'email' => $this->getEmail(),
            'telephone' => $this->getTelephone(),
            'dateNaissance' => $this->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $this->getLieuNaissance(),
            'adresse' => $this->getAdresse(),
            'numeroElecteur' => $this->getNumeroElecteur() ?? null,
            'profession' => $this->getProfession(),
            'isHabitant' => $this->isHabitant() ? true : false,
            'nombreEnfant' => $this->getNombreEnfant(),
            'situationMatrimoniale' => $this->getSituationMatrimoniale(),
            
        ];
    }

    public function toArray(): array
    {


        return [
            'id' => $this->getId(),
            'numero' => $this->getNumero(),
            'typeDemande' => $this->getTypeDemande(),
            'typeDocument' => $this->getTypeDocument(),
            'superficie' => $this->getSuperficie(),
            'usagePrevu' => $this->getUsagePrevu(),
            'possedeAutreTerrain' => $this->isPossedeAutreTerrain(),
            'statut' => $this->getStatut(),
            'dateCreation' => $this->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $this->getDateModification()?->format('Y-m-d H:i:s'),
            'motif_refus' => $this->getMotifRefus(),
            'recto' => $this->getRecto(),
            'verso' => $this->getVerso(),
            'rapport' => $this->getRapport(),
            'typeTitre' => $this->getTypeTitre(),
            'terrainAKaolack' => $this->isTerrainAKaolack(),
            'terrainAilleurs' => $this->isTerrainAilleurs(),
            'decisionCommission' => $this->getDecisionCommission(),
            'recommandation' => $this->getRecommandation(),

            // <<< AJOUT ICI
            'localite' => $this->getLocalite(),


            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'email' => $this->getEmail(),
            'telephone' => $this->getTelephone(),
            // (optionnel) formatte la date de naissance pour éviter un objet DateTime dans le JSON
            'dateNaissance' => $this->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $this->getLieuNaissance(),
            'adresse' => $this->getAdresse(),
            'numeroElecteur' => $this->getNumeroElecteur() ?? null,
            'profession' => $this->getProfession(),
            'isHabitant' => $this->isHabitant(),

            'quartier' => $this->getQuartier() ? [
                'id' => $this->getQuartier()->getId(),
                'nom' => $this->getQuartier()->getNom(),
                'prix' => $this->getQuartier()->getPrix(),
                'longitude' => $this->getQuartier()->getLongitude(),
                'latitude' => $this->getQuartier()->getLatitude(),
                'description' => $this->getQuartier()->getDescription(),
            ] : null
        ];
    }

    public function toArraySimple(): array
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
            'recto' => $this->getRecto(),
            'verso' => $this->getVerso(),
            'rapport' => $this->getRapport(),
            'typeTitre' => $this->getTypeTitre(),
            'terrainAKaolack' => $this->isTerrainAKaolack(),
            'terrainAilleurs' => $this->isTerrainAilleurs(),
            'decisionCommission' => $this->getDecisionCommission(),
            'recommandation' => $this->getRecommandation(),

            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'email' => $this->getEmail(),
            'telephone' => $this->getTelephone(),
            'dateNaissance' => $this->getDateNaissance(),
            'lieuNaissance' => $this->getLieuNaissance(),
            'adresse' => $this->getAdresse(),
            'numeroElecteur' => $this->getNumeroElecteur() ?? null,
            'profession' => $this->getProfession(),
            'isHabitant' => $this->isHabitant(),
        ];
    }

    public function getTypeDocument(): ?string
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(?string $typeDocument): self
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
        if ($typeTitre === null) {
            $this->typeTitre = null;
            return $this;
        }
        if (
            !in_array(
                $typeTitre,

                [
                    self::PERMIS_OCCUPATION,
                    self::BAIL_COMMUNAL,
                    self::TRANSFERT_DEFINITIF,
                    self::PROPOSITION_BAIL,
                ]
            )
        ) {
            throw new \InvalidArgumentException("Type de titre invalide: " . $typeTitre);
        }

        $this->typeTitre = $typeTitre;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(?string $lieuNaissance): static
    {
        $this->lieuNaissance = $lieuNaissance;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getNumeroElecteur(): ?string
    {
        return $this->numeroElecteur;
    }

    public function setNumeroElecteur(?string $numeroElecteur): static
    {
        $this->numeroElecteur = $numeroElecteur;

        // Update habitant status whenever numeroElecteur is changed
        if ($numeroElecteur !== null && $this->fonctionsService !== null) {
            $this->updateHabitantStatus();
        }
        return $this;
    }


    /**
     * Check if the user is a habitant.
     *
     * @return bool|null Returns true if the user is a habitant, false if not, or null if the status is unknown.
     */

    public function isHabitant(): ?bool
    {
        if ($this->fonctionsService === null || $this->getNumeroElecteur() === null) {
            return $this->habitant;
        }

        $resultat = $this->fonctionsService->checkNumeroElecteurExist($this->getNumeroElecteur());
        return $resultat ?? $this->habitant;
    }

    public function setHabitant(?bool $habitant): static
    {
        $this->habitant = $habitant;

        return $this;
    }




    public function getSituationMatrimoniale(): ?string
    {
        return $this->situationMatrimoniale;
    }

    public function setSituationMatrimoniale(?string $situationMatrimoniale): static
    {
        if ($situationMatrimoniale === null) {
            $this->situationMatrimoniale = null;
            return $this;
        }
        if (
            !in_array(
                $situationMatrimoniale,

                [
                    self::SITATION_MATRIMONIALE_CELIBATAIRE,
                    self::SITATION_MATRIMONIALE_MARIE,
                    self::SITATION_MATRIMONIALE_DIVORCE,
                    self::SITATION_MATRIMONIALE_VEUF,
                ]
            )
        ) {
            throw new \InvalidArgumentException("Type de document invalide: " . $situationMatrimoniale);
        }

        $this->situationMatrimoniale = $situationMatrimoniale;

        return $this;
    }

    public function getNombreEnfant(): ?int
    {
        return $this->nombreEnfant;
    }

    public function setNombreEnfant(?int $nombreEnfant): static
    {
        $this->nombreEnfant = $nombreEnfant;

        return $this;
    }


    public function getStatutLogement(): ?string
    {
        return $this->statutLogement;
    }

    public function setStatutLogement(?string $statutLogement): static
    {

        if ($statutLogement === null) {
            $this->statutLogement = null;
            return $this;
        }
        if (
            !in_array(
                $statutLogement,

                [
                    self::SITUATION_DEMANDEUR_PROPRIETAIRE,
                    self::SITUATION_DEMANDEUR_LOCATAIRE,
                    self::SITUATION_DEMANDEUR_HEBERGER,
                ]
            )
        ) {
            throw new \InvalidArgumentException("Type Situation de demandeur invalide: " . $statutLogement);
        }
        $this->statutLogement = $statutLogement;

        return $this;
    }

    public function getLocalite(): ?string
    {
        return $this->localite;
    }

    public function setLocalite(?string $localite): static
    {
        $this->localite = $localite;

        return $this;
    }

    public function getQuartier(): ?Localite
    {
        return $this->quartier;
    }

    public function setQuartier(?Localite $quartier): static
    {
        $this->quartier = $quartier;

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

    public function getParcelleAttribuer(): ?AttributionParcelle
    {
        return $this->parcelleAttribuer;
    }

    public function setParcelleAttribuer(?AttributionParcelle $parcelleAttribuer): static
    {
        // unset the owning side of the relation if necessary
        if ($parcelleAttribuer === null && $this->parcelleAttribuer !== null) {
            $this->parcelleAttribuer->setDemande(null);
        }

        // set the owning side of the relation if necessary
        if ($parcelleAttribuer !== null && $parcelleAttribuer->getDemande() !== $this) {
            $parcelleAttribuer->setDemande($this);
        }

        $this->parcelleAttribuer = $parcelleAttribuer;

        return $this;
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

}
