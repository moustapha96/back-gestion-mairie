<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SignatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: SignatureRepository::class)]


#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['signature:item']]),
        new Post(normalizationContext: ['groups' => ['signature:write']]),
        new GetCollection(normalizationContext: ['groups' => ['signature:list']]),
    ],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_signatures`')]
class Signature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['signature:list', 'signature:item', 'user:item', 'user:list', 'signature:write'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['signature:list', 'signature:item', 'user:item', 'user:list', 'signature:write'])]
    private ?\DateTimeInterface $dateSignature = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['signature:list', 'signature:item', 'user:item', 'user:list', 'signature:write'])]
    private ?string $signature = null;

    #[ORM\Column]
    #[Groups(['signature:list', 'signature:item', 'user:item', 'user:list', 'signature:write'])]
    private ?int $ordre = null;


    #[ORM\OneToOne(inversedBy: 'signature', cascade: ['persist', 'remove'])]
    #[Groups(['signature:list', 'signature:item', 'user:item', 'user:list', 'signature:write'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateSignature(): ?\DateTimeInterface
    {
        return $this->dateSignature;
    }

    public function setDateSignature(?\DateTimeInterface $dateSignature): static
    {
        $this->dateSignature = $dateSignature;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(?string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }


    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'dateSignature' => $this->getDateSignature() ? $this->getDateSignature()->format('Y-m-d H:i:s') : null,
            'signature' => $this->getSignature(),
            'ordre' => $this->getOrdre(),
            'user' => $this->getUser() ? $this->getUser()->getId() : null,
        ];
    }
}
