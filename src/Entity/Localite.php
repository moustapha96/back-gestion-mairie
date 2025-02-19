<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LocaliteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LocaliteRepository::class)]

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['localite:item']]),
        new Post(normalizationContext: ['groups' => ['localite:write']]),
        new GetCollection(normalizationContext: ['groups' => ['localite:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false
)]

#[ORM\Table(name: '`gs_mairie_localites`')]
class Localite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item'])]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item'])]
    private ?float $prix = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item'])]
    private ?string $description = null;


    /**
     * @var Collection<int, DemandeTerrain>
     */
    #[ORM\OneToMany(targetEntity: DemandeTerrain::class, mappedBy: 'localite')]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item'])]
    private Collection $demandes;

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, DemandeTerrain>
     */
    public function getDemandes(): Collection
    {
        return $this->demandes;
    }

    public function addDemande(DemandeTerrain $demande): static
    {
        if (!$this->demandes->contains($demande)) {
            $this->demandes->add($demande);
            $demande->setLocalite($this);
        }

        return $this;
    }

    public function removeDemande(DemandeTerrain $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            // set the owning side to null (unless already changed)
            if ($demande->getLocalite() === $this) {
                $demande->setLocalite(null);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'prix' => $this->getPrix(),
            'description' => $this->getDescription(),
        ];
    }
}
