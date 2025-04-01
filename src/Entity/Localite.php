<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LocaliteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LocaliteRepository::class)]

#[ApiResource(
    normalizationContext: ['groups' => ['localite:item', 'localite:list']],
    denormalizationContext: ['groups' => ['localite:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false
)]

#[ORM\Table(name: '`gs_mairie_localites`')]
class Localite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item', 'demande:list', 'demandeur:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item', 'demande:list', 'demandeur:list'])]
    private ?string $nom = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item', 'demande:list', 'demandeur:list'])]
    private ?float $prix = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item', 'demande:list', 'demandeur:list'])]
    private ?string $description = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item', 'demande:list', 'demandeur:list'])]
    private ?float $latitude = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demande:item', 'demande:list', 'demandeur:list'])]
    private ?float $longitude = null;

    /**
     * @var Collection<int, DemandeTerrain>
     */
    #[ORM\OneToMany(targetEntity: DemandeTerrain::class, mappedBy: 'localite')]
    #[Groups(['localite:list', 'localite:item', 'localite:write'])]
    private Collection $demandes;

    #[ORM\OneToMany(mappedBy: 'localite', targetEntity: Lotissement::class)]
    #[Groups(['localite:list', 'localite:item', 'localite:write', 'demandeur:list'])]
    private Collection $lotissements;

    public function __construct()
    {
        $this->demandes = new ArrayCollection();
        $this->lotissements = new ArrayCollection();
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

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
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
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
        ];
    }

    /**
     * @return Collection<int, Lotissement>
     */
    public function getLotissements(): Collection
    {
        return $this->lotissements;
    }

    public function addLotissement(Lotissement $lotissement): self
    {
        if (!$this->lotissements->contains($lotissement)) {
            $this->lotissements->add($lotissement);
            $lotissement->setLocalite($this);
        }

        return $this;
    }

    public function removeLotissement(Lotissement $lotissement): self
    {
        if ($this->lotissements->removeElement($lotissement)) {
            // set the owning side to null (unless already changed)
            if ($lotissement->getLocalite() === $this) {
                $lotissement->setLocalite(null);
            }
        }

        return $this;
    }
}
