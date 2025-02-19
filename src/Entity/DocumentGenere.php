<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DocumentGenereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ORM\Entity(repositoryClass: DocumentGenereRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['document-generer:item']]),
        new Post(normalizationContext: ['groups' => ['document-generer:write']]),
        new GetCollection(normalizationContext: ['groups' => ['document-generer:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_documents_generes`')]
#[ORM\Index(columns: ['type_document'], flags: ['fulltext'])]
class DocumentGenere
{

    const PERMIS_OCCUPATION = 'PERMIS_OCCUPATION';
    const BAIL_COMMUNAL = 'BAIL_COMMUNAL';
    const CALCUL_REDEVANCE = 'CALCUL_REDEVANCE';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $typeDocument = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;


    #[ORM\OneToOne(inversedBy: 'documentGenerer', cascade: ['persist', 'remove'])]
    private ?DemandeTerrain $demande = null;

    #[ORM\OneToOne(mappedBy: 'document', cascade: ['persist', 'remove'])]
    private ?BailCommunal $bailCommunal = null;

    #[ORM\OneToOne(mappedBy: 'document', cascade: ['persist', 'remove'])]
    private ?PermisOccupation $permisOccupation = null;

    #[ORM\OneToOne(mappedBy: 'document', cascade: ['persist', 'remove'])]
    private ?CalculRedevance $calculRedevance = null;

    #[ORM\OneToOne(mappedBy: 'document', cascade: ['persist', 'remove'])]
    private ?PropositionBail $propositionBail = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeDocument(): ?string
    {
        return $this->typeDocument;
    }

    public function setTypeDocument(string $typeDocument): static
    {
        $this->typeDocument = $typeDocument;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
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

    public function getBailCommunal(): ?BailCommunal
    {
        return $this->bailCommunal;
    }

    public function setBailCommunal(?BailCommunal $bailCommunal): static
    {
        // unset the owning side of the relation if necessary
        if ($bailCommunal === null && $this->bailCommunal !== null) {
            $this->bailCommunal->setDocument(null);
        }

        // set the owning side of the relation if necessary
        if ($bailCommunal !== null && $bailCommunal->getDocument() !== $this) {
            $bailCommunal->setDocument($this);
        }

        $this->bailCommunal = $bailCommunal;

        return $this;
    }

    public function getPermisOccupation(): ?PermisOccupation
    {
        return $this->permisOccupation;
    }

    public function setPermisOccupation(?PermisOccupation $permisOccupation): static
    {
        // unset the owning side of the relation if necessary
        if ($permisOccupation === null && $this->permisOccupation !== null) {
            $this->permisOccupation->setDocument(null);
        }

        // set the owning side of the relation if necessary
        if ($permisOccupation !== null && $permisOccupation->getDocument() !== $this) {
            $permisOccupation->setDocument($this);
        }

        $this->permisOccupation = $permisOccupation;

        return $this;
    }

    public function getCalculRedevance(): ?CalculRedevance
    {
        return $this->calculRedevance;
    }

    public function setCalculRedevance(?CalculRedevance $calculRedevance): static
    {
        // unset the owning side of the relation if necessary
        if ($calculRedevance === null && $this->calculRedevance !== null) {
            $this->calculRedevance->setDocument(null);
        }

        // set the owning side of the relation if necessary
        if ($calculRedevance !== null && $calculRedevance->getDocument() !== $this) {
            $calculRedevance->setDocument($this);
        }

        $this->calculRedevance = $calculRedevance;

        return $this;
    }

    public function getPropositionBail(): ?PropositionBail
    {
        return $this->propositionBail;
    }

    public function setPropositionBail(?PropositionBail $propositionBail): static
    {
        // unset the owning side of the relation if necessary
        if ($propositionBail === null && $this->propositionBail !== null) {
            $this->propositionBail->setDocument(null);
        }

        // set the owning side of the relation if necessary
        if ($propositionBail !== null && $propositionBail->getDocument() !== $this) {
            $propositionBail->setDocument($this);
        }

        $this->propositionBail = $propositionBail;

        return $this;
    }





    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'typeDocument' => $this->typeDocument,
            'url' => $this->url,
            'dateCreation' => $this->dateCreation ? $this->dateCreation->format('Y-m-d H:i:s') : null,
            'details' => $this->details,
            'demande' => $this->demande ? $this->demande->toArray() : null,
            'bailCommunal' => $this->bailCommunal ? $this->bailCommunal->toArray() : null,
            'permisOccupation' => $this->permisOccupation ? $this->permisOccupation->toArray() : null,
            'calculRedevance' => $this->calculRedevance ? $this->calculRedevance->toArray() : null,
            'propositionBail' => $this->propositionBail ? $this->propositionBail->toArray() : null,

        ];
    }
}
