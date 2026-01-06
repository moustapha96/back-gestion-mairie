<?php

namespace App\Entity;

use App\Enum\StatutAttribution;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`gs_mairie_attribuation_historiques`')]

class AttributionParcelleStatusHistory
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AttributionParcelle::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AttributionParcelle $attribution = null;

    #[ORM\Column(enumType: StatutAttribution::class)]
    private StatutAttribution $fromStatus;

    #[ORM\Column(enumType: StatutAttribution::class)]
    private StatutAttribution $toStatus;

    #[ORM\Column(type: 'datetime')] 
    private \DateTimeInterface $changedAt;

    #[ORM\Column(type: 'text', nullable: true)] 
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setAttribution(AttributionParcelle $a): self
    {
        $this->attribution = $a;
        return $this;
    }
    public function setFromStatus(StatutAttribution $s): self
    {
        $this->fromStatus = $s;
        return $this;
    }
    public function setToStatus(StatutAttribution $s): self
    {
        $this->toStatus = $s;
        return $this;
    }
    public function setChangedAt(\DateTimeInterface $d): self
    {
        $this->changedAt = $d;
        return $this;
    }
    public function setComment(?string $c): self
    {
        $this->comment = $c;
        return $this;
    }
}
