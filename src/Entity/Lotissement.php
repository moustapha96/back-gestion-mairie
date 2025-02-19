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
    operations: [
        new Get(normalizationContext: ['groups' => ['lotissement:item']]),
        new Post(normalizationContext: ['groups' => ['lotissement:write']]),
        new GetCollection(normalizationContext: ['groups' => ['lotissement:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_lotissements`')]
class Lotissement
{



    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $localisation = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    /**
     * @var Collection<int, PlanLotissement>
     */
    #[ORM\OneToMany(targetEntity: PlanLotissement::class, mappedBy: 'lotissement')]
    private Collection $planLotissements;

    /**
     * @var Collection<int, Lots>
     */
    #[ORM\OneToMany(targetEntity: Lots::class, mappedBy: 'lotissement')]

    #[Groups(['lotissement:item', 'lotissement:list'])]
    private Collection $lots;

    public function __construct()
    {
        $this->planLotissements = new ArrayCollection();
        $this->lots = new ArrayCollection();
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
            // 'planLotissements' => array_map(function ($planLotissement) {
            //     return $planLotissement->toArray();
            // }, $this->getPlanLotissements()->toArray()),
            // 'lots' => $this->getLots() ?  $this->getLots()->toArray() : null,
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
}
