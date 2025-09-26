<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\LotissementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LotissementRepository::class)]

#[ApiResource(
    normalizationContext: ['groups' => ['lotissement:item', 'lotissement:list']],
    denormalizationContext: ['groups' => ['lotissement:write']],

    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_lotissements`')]
class Lotissement
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?string $localisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?string $description = null;

    #[ORM\Column(length: 255 ,  nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?float $latitude = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Groups(['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item'])]
    private ?float $longitude = null;

    /**
     * @var Collection<int, PlanLotissement>
     */
    #[ORM\OneToMany(targetEntity: PlanLotissement::class, mappedBy: 'lotissement')]
    private Collection $planLotissements;

    /**
     * @var Collection<int, Lots>
     */
    #[ORM\OneToMany(targetEntity: Lots::class, mappedBy: 'lotissement')]

    #[Groups(['lotissement:item'])]
    private Collection $lots;

    #[ORM\OneToMany(mappedBy: 'lotissement', targetEntity: Parcelle::class)]
    private Collection $parcelles;


    #[ORM\ManyToOne(inversedBy: 'lotissements', fetch: 'EAGER', targetEntity: Localite::class, cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["lotissement:write", "lotissement:list", "lotissement:item"])]
    private ?Localite $localite = null;


    public function __construct()
    {
        $this->planLotissements = new ArrayCollection();
        $this->lots = new ArrayCollection();
        $this->parcelles = new ArrayCollection();
    }

    public function getLocalite(): ?Localite
    {
        return $this->localite;
    }

    public function setLocalite(?Localite $localite): self
    {
        $this->localite = $localite;

        return $this;
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

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(string $localisation): static
    {
        $this->localisation = $localisation;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

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
     * @return Collection<int, PlanLotissement>
     */
    public function getPlanLotissements(): Collection
    {
        return $this->planLotissements;
    }

    public function addPlanLotissement(PlanLotissement $planLotissement): static
    {
        if (!$this->planLotissements->contains(element: $planLotissement)) {
            $this->planLotissements->add(element: $planLotissement);
            $planLotissement->setLotissement($this);
        }

        return $this;
    }

    public function removePlanLotissement(PlanLotissement $planLotissement): static
    {
        if ($this->planLotissements->removeElement($planLotissement)) {
            // set the owning side to null (unless already changed)
            if ($planLotissement->getLotissement() === $this) {
                $planLotissement->setLotissement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Lots>
     */
    public function getLots(): Collection
    {
        return $this->lots;
    }

    public function addLot(Lots $lot): static
    {
        if (!$this->lots->contains($lot)) {
            $this->lots->add($lot);
            $lot->setLotissement($this);
        }

        return $this;
    }

    public function removeLot(Lots $lot): static
    {
        if ($this->lots->removeElement($lot)) {
            // set the owning side to null (unless already changed)
            if ($lot->getLotissement() === $this) {
                $lot->setLotissement(null);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'localisation' => $this->getLocalisation(),
            'description' => $this->getDescription(),
            'statut' => $this->getStatut(),
            'dateCreation' => $this->getDateCreation() ? $this->getDateCreation()->format('Y-m-d H:i:s') : null,
            'planLotissements' => array_map(function ($planLotissement) {
                return $planLotissement->toArray();
            }, $this->getPlanLotissements()->toArray()),
            'lots' => $this->getLots() ? $this->getLots()->toArray() : null,
            'localite' => $this->getLocalite() ? $this->getLocalite()->toArray() : null,
            'latitude' => $this->getLatitude(),
            'longitude' => $this->getLongitude(),
        ];
    }
    public function toArray1(): array
    {
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'localisation' => $this->getLocalisation(),
            'description' => $this->getDescription(),
            'statut' => $this->getStatut(),
            'dateCreation' => $this->getDateCreation() ? $this->getDateCreation()->format('Y-m-d H:i:s') : null,

            'lots' => array_map(function ($lots) {
                return $lots->toArray();
            }, $this->getLots()->toArray()),

            'planLotissements' => array_map(function ($planLotissement) {
                return $planLotissement->toArray();
            }, $this->getPlanLotissements()->toArray()),
        ];
    }

    /**
     * @return Collection<int, Parcelle>
     */
    public function getParcelles(): Collection
    {
        return $this->parcelles;
    }

    public function addParcelle(Parcelle $parcelle): static
    {
        if (!$this->parcelles->contains($parcelle)) {
            $this->parcelles->add($parcelle);
            $parcelle->setLotissement($this);
        }

        return $this;
    }

    public function removeParcelle(Parcelle $parcelle): static
    {
        if ($this->parcelles->removeElement($parcelle)) {
            // set the owning side to null (unless already changed)
            if ($parcelle->getLotissement() === $this) {
                $parcelle->setLotissement(null);
            }
        }

        return $this;
    }
}
