<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DemandeTerrainRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DemandeTerrainRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['demande:item']]),
        new Post(normalizationContext: ['groups' => ['demande:write']]),
        new GetCollection(normalizationContext: ['groups' => ['demande:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_demande_terrains`')]
class DemandeTerrain
{

    const PERMIS_OCCUPATION = 'PERMIS_OCCUPATION';
    const BAIL_COMMUNAL = 'BAIL_COMMUNAL';
    const CALCUL_REDEVANCE = 'CALCUL_REDEVANCE';


    const STATUT_REJETE = 'REJETE';
    const STATUT_VALIDE = 'VALIDE';
    const STATUT_EN_COURS = 'EN_COURS';
    const STATUT_EN_TRAITEMENT = 'EN_TRAITEMENT';

    #[ORM\Id]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    #[ORM\Column(length: 30)]
    private ?string $typeDemande = null;

    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    #[ORM\Column]
    private ?float $superficie = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?string $usagePrevu = null;


    #[ORM\Column(nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?bool $possedeAutreTerrain = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?string $document = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?User $utilisateur = null;

    #[ORM\OneToOne(mappedBy: 'demande', cascade: ['persist', 'remove'])]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?DocumentGenere $documentGenerer = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?Localite $localite = null;

    #[ORM\Column(length: 255)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list'])]
    private ?string $typeDocument = null;

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
        if (!in_array($typeDemande, [self::PERMIS_OCCUPATION, self::BAIL_COMMUNAL, self::CALCUL_REDEVANCE])) {
            throw new \InvalidArgumentException("Type de document invalide: " . $typeDemande);
        }
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
        if (!in_array($statut, [self::STATUT_EN_COURS, self::STATUT_EN_TRAITEMENT, self::STATUT_REJETE, self::STATUT_VALIDE])) {
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
        return $this->documentGenerer;
    }

    public function setDocumentGenerer(?DocumentGenere $documentGenerer): static
    {
        // unset the owning side of the relation if necessary
        if ($documentGenerer === null && $this->documentGenerer !== null) {
            $this->documentGenerer->setDemande(null);
        }

        // set the owning side of the relation if necessary
        if ($documentGenerer !== null && $documentGenerer->getDemande() !== $this) {
            $documentGenerer->setDemande($this);
        }

        $this->documentGenerer = $documentGenerer;

        return $this;
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
            'document' => $this->getDocument(),
            'documentGenerer' => $this->getDocumentGenerer() ? $this->getDocumentGenerer()->toArray() : null,
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

    public function generateDocument(): void
    {
        switch ($this->typeDemande) {
            case self::PERMIS_OCCUPATION:
                $this->generatePermisOccupationDocument();
                break;
            case self::BAIL_COMMUNAL:
                $this->generateBailCommunalDocument();
                break;
            case self::CALCUL_REDEVANCE:
                $this->generateCalculRedevanceDocument();
                break;
            default:
                throw new \Exception('Type de demande inconnu');
        }
    }

    // Méthode pour générer un document de type "Permis Occupation"
    private function generatePermisOccupationDocument(): void
    {
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::PERMIS_OCCUPATION);
        $document->setDateCreation(new \DateTime());
        $document->setDetails('Document pour Permis Occupation');

        // Ajoutez ici d'autres informations spécifiques au Permis d'Occupation

        // Lier le document à la demande
        $this->documentGenerer = $document;
    }

    // Méthode pour générer un document de type "Bail Communal"
    private function generateBailCommunalDocument(): void
    {
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::BAIL_COMMUNAL);
        $document->setDateCreation(new \DateTime());
        $document->setDetails('Document pour Bail Communal');

        // Lier le Bail Communal à ce document
        $bailCommunal = new BailCommunal();
        $bailCommunal->setReferenceBail('REF-123');
        $bailCommunal->setDureeBail('5 ans');
        $bailCommunal->setMontantRedevance(1000);
        $bailCommunal->setModalitePaiement('Mensuel');
        $bailCommunal->setClausseObligation('Respect des délais');

        $document->setBailCommunal($bailCommunal);

        // Lier le document à la demande
        $this->documentGenerer = $document;
    }

    // Méthode pour générer un document de type "Calcul Redevance"
    private function generateCalculRedevanceDocument(): void
    {
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::CALCUL_REDEVANCE);
        $document->setDateCreation(new \DateTime());
        $document->setDetails('Document pour Calcul de Redevance');

        // Lier le calcul de redevance à ce document
        $calculRedevance = new CalculRedevance();
        $calculRedevance->setBaseCalcul('Superficie');
        $calculRedevance->setTaux(5);
        $calculRedevance->setMontantRedevanceCalcule('5000');
        $calculRedevance->setFormuleCalcul('Superficie * Taux');

        $document->setCalculRedevance($calculRedevance);

        // Lier le document à la demande
        $this->documentGenerer = $document;
    }
}
