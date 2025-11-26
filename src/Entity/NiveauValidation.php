<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\NiveauValidationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: NiveauValidationRepository::class)]
#[ORM\Table(name: '`gs_mairie_niveau_validations`')]
#[ApiResource(
    normalizationContext: [
        'groups' => ['niveau:item', 'niveau:list'],
        'enable_max_depth' => true
    ],
    denormalizationContext: ['groups' => ['niveau:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]
class NiveauValidation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['niveau:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['niveau:read', 'demande:item', 'demande:list'])]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Groups(['niveau:read'])]
    private ?string $roleRequis = null;

    #[ORM\Column]
    #[Groups(['niveau:read'])]
    private ?int $ordre = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getRoleRequis(): ?string
    {
        return $this->roleRequis;
    }

    public function setRoleRequis(?string $roleRequis): static
    {
        $this->roleRequis = $roleRequis;

        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(?int $ordre): static
    {
        $this->ordre = $ordre;

        return $this;
    }

    public function toArray(): array{
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'role'=> $this->getRoleRequis(),
            'ordre' => $this->getOrdre(),
        ];
    }
}
