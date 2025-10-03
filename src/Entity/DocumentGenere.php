<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DocumentGenereRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

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
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]

#[ORM\Table(name: '`gs_mairie_documents`')]
class DocumentGenere
{

    const TYPES = [
        'PERMIS_OCCUPATION',
        'BAIL_COMMUNAL',
        'CALCUL_REDEVANCE',
        'PROPOSITION_BAIL'
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]

    private ?string $type = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['demande:list', 'demande:item', 'demande:write', 'user:item', 'user:list', 'localite:item', 'localite:list'])]

    private array $contenu = [];

    #[ORM\Column(type: 'datetime')]
    #[Groups(['document:read'])]
    private ?\DateTimeInterface $dateCreation = null;


    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $fichier = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['document:read'])]
    private bool $isGenerated = false;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
        $this->isGenerated = false;
    }

    public function setFichier(?string $fichier): self
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getContenu(): array
    {
        return $this->contenu;
    }

    public function setContenu(array $contenu): static
    {
        $this->contenu = $contenu;

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

    public function isGenerated(): bool
    {
        return $this->isGenerated;
    }

    public function setIsGenerated(bool $isGenerated): static
    {
        $this->isGenerated = $isGenerated;
        return $this;
    }
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'contenu' => $this->contenu,
            'dateCreation' => $this->dateCreation ? $this->dateCreation->format('Y-m-d H:i:s') : null,
            'isGenerated' => $this->isGenerated,
            'fichier' => $this->fichier
        ];
    }
}
