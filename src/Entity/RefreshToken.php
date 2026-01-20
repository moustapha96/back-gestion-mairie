<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\HasLifecycleCallbacks]
class RefreshToken extends BaseRefreshToken
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTime $createdAt = null;

    public function __construct()
    {
        // Initialiser created_at dès la construction pour éviter l'erreur SQL
        $this->createdAt = new \DateTime();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setCreatedAtValue(): void
    {
        // S'assurer que created_at est toujours défini avant la persistance
        // Si c'est une nouvelle entité, on définit la date de création
        // Si c'est une mise à jour, on ne modifie pas created_at (il reste à sa valeur initiale)
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
