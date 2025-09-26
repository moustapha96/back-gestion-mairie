<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TitreFoncierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TitreFoncierRepository::class)]
#[ApiResource]

#[ORM\Table(name: '`gs_mairie_titre_fonciers`')]
#[ApiResource(
    normalizationContext: [
        'groups' => ['titre:item', 'titre:list'],
        'enable_max_depth' => true
    ],
    denormalizationContext: ['groups' => ['titre:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]
class TitreFoncier
{


    const TYPE_TF = "Titre foncier";
    const TYPE_BAIL = "Bail";
    const TYPE_PLACE_PUBLIC = "Place publique";
    const TYPE_DOMAINE_ETAT = "Domaine état";
        

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(nullable: true)]
    private ?float $superficie = null;

    #[ORM\Column(nullable: true)]
    private ?array $titreFigure = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $etatDroitReel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroLot = null;

    #[ORM\ManyToOne(inversedBy: 'titreFonciers')]
    private ?Localite $quartier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(?float $superficie): static
    {
        $this->superficie = $superficie;

        return $this;
    }

    public function getTitreFigure(): ?array
    {
        return $this->titreFigure;
    }

    public function setTitreFigure(?array $titreFigure): static
    {
        $this->titreFigure = $titreFigure;

        return $this;
    }

    public function getEtatDroitReel(): ?string
    {
        return $this->etatDroitReel;
    }

    public function setEtatDroitReel(?string $etatDroitReel): static
    {
        $this->etatDroitReel = $etatDroitReel;

        return $this;
    }

    public function getNumeroLot(): ?string
    {
        return $this->numeroLot;
    }

    public function setNumeroLot(?string $numeroLot): static
    {
        $this->numeroLot = $numeroLot;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
         if (!in_array(
            $type,
            [
                self::TYPE_BAIL,
                self::TYPE_DOMAINE_ETAT,
                self::TYPE_PLACE_PUBLIC,
                self::TYPE_TF,
            ]
        )) {
            throw new \InvalidArgumentException("Type de titre invalide: " . $type);
        }
        $this->type = $type;
        return $this;
    }
}
