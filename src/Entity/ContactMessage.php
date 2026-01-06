<?php

namespace App\Entity;

use App\Repository\ContactMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Table(name: 'contact_messages')]
class ContactMessage
{
    public const CAT_DEMANDE_PARCELLE   = 'DEMANDE_PARCELLE';
    public const CAT_SUIVI_DOSSIER      = 'SUIVI_DOSSIER';
    public const CAT_LITIGE_FONCIER     = 'LITIGE_FONCIER';
    public const CAT_PAIEMENT           = 'PAIEMENT';
    public const CAT_ATTESTATION_QUITUS = 'ATTESTATION_QUITUS';
    public const CAT_CORRECTION_DONNEES = 'CORRECTION_DONNEES';
    public const CAT_AUTRE              = 'AUTRE';

    public const ALLOWED_CATEGORIES = [
        self::CAT_DEMANDE_PARCELLE,
        self::CAT_SUIVI_DOSSIER,
        self::CAT_LITIGE_FONCIER,
        self::CAT_PAIEMENT,
        self::CAT_ATTESTATION_QUITUS,
        self::CAT_CORRECTION_DONNEES,
        self::CAT_AUTRE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nom;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 40)]
    private string $telephone;

    #[ORM\Column(length: 40)]
    private string $categorie;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'boolean')]
    private bool $consent = false;

    /** Chemin web relatif (ex: /uploads/contact/xxx.pdf) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pieceJointe = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- Getters / setters ---

    public function getId(): ?int { return $this->id; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getTelephone(): string { return $this->telephone; }
    public function setTelephone(string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getCategorie(): string { return $this->categorie; }
    public function setCategorie(string $categorie): self {
        if (!in_array($categorie, self::ALLOWED_CATEGORIES, true)) {
            throw new \InvalidArgumentException('Catégorie invalide');
        }
        $this->categorie = $categorie;
        return $this;
    }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): self { $this->reference = $reference; return $this; }

    public function getMessage(): string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }

    public function getConsent(): bool { return $this->consent; }
    public function setConsent(bool $consent): self { $this->consent = $consent; return $this; }

    public function getPieceJointe(): ?string { return $this->pieceJointe; }
    public function setPieceJointe(?string $pieceJointe): self { $this->pieceJointe = $pieceJointe; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
