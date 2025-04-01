<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\services\FonctionsService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\MaxDepth;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'Cet identifiant est déjà utilisé par un autre utilisateur')]
#[UniqueEntity(fields: ['email'], message: 'Cet e-mail est déjà utilisé par un autre utilisateur')]
#[UniqueEntity(fields: ['numeroElecteur'], message: "Numero Electeur est déjà utilisé par un autre utilisateur")]

#[ORM\HasLifecycleCallbacks]

#[ApiResource(
    normalizationContext: [
        'groups' => ['user:item', 'user:list'],
        'enable_max_depth' => true
    ],
    denormalizationContext: ['groups' => ['user:write']],
    order: ["id" => "DESC"],
    paginationEnabled: false,
)]

#[ORM\Table(name: '`gs_mairie_users`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    // Constants for roles
    const ROLE_AGENT = 'ROLE_AGENT';
    const ROLE_DEMANDEUR = 'ROLE_DEMANDEUR';
    const ROLE_ADMIN = "ROLE_ADMIN";
    const ROLE_SUPER_ADMIN = "ROLE_SUPER_ADMIN";


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:list', 'user:item', 'user:write', 'demande:list', 'demande:item', 'localite:item', 'localite:list', 'localite:write'])]
    private ?int $id = null;

    #[Groups(['user:list', 'user:item', 'user:write', 'demande:list', 'demande:item', 'localite:item', 'localite:list', 'localite:write'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[Groups(['user:list', 'user:item', 'user:write', 'demande:list', 'demande:item', 'localite:item', 'localite:list', 'localite:write'])]
    #[ORM\Column]
    private array $roles = [];


    #[Groups(['user:list', 'user:item', 'user:write', 'demande:list', 'demande:item'])]
    #[ORM\Column]
    private ?string $password = null;


    #[Groups(['user:list', 'user:item', 'user:write', 'demande:list', 'demande:item'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:list', 'user:item', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $avatar = null;

    #[Groups(['user:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private $reset_token;

    #[Groups(['user:write'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private $reset_token_expired_at;

    #[ORM\Column(nullable: true)]
    private ?bool $enabled = null;

    #[ORM\Column(nullable: true)]
    private ?bool $activeted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenActiveted = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordClaire = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $profession = null;

    #[ORM\Column(length: 13)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['user:read', 'user:list', 'user:write', 'demande:list', 'demande:item'])]
    private ?string $numeroElecteur = null;

    #[ORM\OneToMany(targetEntity: DemandeTerrain::class, mappedBy: 'utilisateur')]
    #[Groups(['user:item'])]
    #[MaxDepth(1)]
    private Collection $demandes;



    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:item'])]
    private ?Signature $signature = null;



    #[ORM\Column(nullable: true)]
    private ?bool $habitant = null;


    /**
     * @param PreUpdateEventArgs $event
     */
    #[PreUpdate]
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        // Check if numeroElecteur has changed
        if ($event->hasChangedField('numeroElecteur')) {
            $this->updateHabitantStatus();
        }
    }
    /**
     * @param PrePersistEventArgs $event
     */
    #[PrePersist]
    public function prePersist(PrePersistEventArgs $event): void
    {
        // Update habitant status before initial persist
        if ($this->getNumeroElecteur() !== null) {
            $this->updateHabitantStatus();
        }
    }
    private ?FonctionsService $fonctionsService = null;

    public function __construct(?FonctionsService $fonctionsService = null)
    {
        $this->fonctionsService = $fonctionsService;
        $this->demandes = new ArrayCollection();


        if ($fonctionsService !== null && $this->getNumeroElecteur() !== null) {
            $this->updateHabitantStatus();
        }
    }

    /**
     * Updates the habitant status by checking the numeroElecteur
     * This method should be called whenever numeroElecteur changes
     * 
     * @return void
     */
    public function updateHabitantStatus(): void
    {
        if ($this->fonctionsService === null || $this->getNumeroElecteur() === null) {
            return;
        }

        $resultat = $this->fonctionsService->checkNumeroElecteurExist($this->getNumeroElecteur());
        $this->habitant = $resultat ?? false;
    }


    public function generatePassword(int $length = 8): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $allChars = $uppercase . $lowercase . $numbers;
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];

        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        $password = str_shuffle($password);
        return $password;
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getResetTokenExpiredAt(): ?\DateTimeInterface
    {
        return $this->reset_token_expired_at;
    }

    public function setResetTokenExpiredAt(?\DateTimeInterface $reset_token_expired_at): self
    {
        $this->reset_token_expired_at = $reset_token_expired_at;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }


    public function setRoles(string $roles): static
    {

        $this->roles = [];

        if (!in_array($roles, [
            self::ROLE_AGENT,
            self::ROLE_DEMANDEUR,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN
        ])) {
            throw new \InvalidArgumentException("Invalid role: " . $roles);
        }
        $this->roles = [$roles];

        return $this;
    }

    public function addRole(string $role): static
    {
        if (!in_array($role, [self::ROLE_AGENT, self::ROLE_DEMANDEUR, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN])) {
            throw new \InvalidArgumentException("Invalid role: " . $role);
        }
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
        return $this;
    }
    // Method to remove a specific role from the user
    public function removeRole(string $role): static
    {
        if (($key = array_search($role, $this->roles)) !== false) {
            unset($this->roles[$key]);
        }

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getAvatar(): ?string
    {
        if ($this->avatar === null) {
            return null;
        }

        $fullPath = $this->getAvatarFullPath();

        try {
            if (!file_exists($fullPath)) {
                throw new FileNotFoundException($fullPath);
            }

            $file = file_get_contents($fullPath);
            return base64_encode($file);
        } catch (FileNotFoundException $e) {
            // Log the error
            error_log('Avatar file not found: ' . $e->getMessage());

            // Return a default avatar or null
            return null;
        }
    }

    private function getAvatarFullPath(): string
    {
        // Adjust this path to match your project structure
        return __DIR__ . '/../../public/profiles/' . $this->avatar;
    }
    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar !== null ? $avatar : '/avatar/avatar.png';
        return $this;
    }

    public function getImage(): string
    {

        if (str_contains($this->avatar, "avatars")) {
            $data = file_get_contents($this->getAvatar());
            $img_code = "data:image/png;base64,{`base64_encode($data)`}";
            return $img_code;
        } else {
            return $this->avatar;
        }
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function setResetToken(?string $reset_token): self
    {
        $this->reset_token = $reset_token;

        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isActiveted(): ?bool
    {
        return $this->activeted;
    }

    public function setActiveted(?bool $activeted): self
    {
        $this->activeted = $activeted;

        return $this;
    }

    public function getTokenActiveted(): ?string
    {
        return $this->tokenActiveted;
    }

    public function setTokenActiveted(?string $tokenActiveted): self
    {
        $this->tokenActiveted = $tokenActiveted;

        return $this;
    }


    public function getPasswordClaire(): ?string
    {
        return $this->passwordClaire;
    }

    public function setPasswordClaire(?string $passwordClaire): self
    {
        $this->passwordClaire = $passwordClaire;

        return $this;
    }


    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
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

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(string $lieuNaissance): static
    {
        $this->lieuNaissance = $lieuNaissance;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getNumeroElecteur(): ?string
    {
        return $this->numeroElecteur;
    }

    // public function setNumeroElecteur(string $numeroElecteur): static
    // {
    //     $this->numeroElecteur = $numeroElecteur;

    //     return $this;
    // }

    public function setNumeroElecteur(?string $numeroElecteur): static
    {
        $this->numeroElecteur = $numeroElecteur;

        // Update habitant status whenever numeroElecteur is changed
        if ($numeroElecteur !== null && $this->fonctionsService !== null) {
            $this->updateHabitantStatus();
        }

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
            $demande->setUtilisateur($this);
        }

        return $this;
    }

    public function removeDemande(DemandeTerrain $demande): static
    {
        if ($this->demandes->removeElement($demande)) {
            // set the owning side to null (unless already changed)
            if ($demande->getUtilisateur() === $this) {
                $demande->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getSignature(): ?Signature
    {
        return $this->signature;
    }

    public function setSignature(?Signature $signature): static
    {
        // unset the owning side of the relation if necessary
        if (
            $signature === null && $this->signature !== null
        ) {
            $this->signature->setUser(null);
        }

        // set the owning side of the relation if necessary
        if (
            $signature !== null && $signature->getUser() !== $this
        ) {
            $signature->setUser($this);
        }

        $this->signature = $signature;

        return $this;
    }


    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'email' => $this->getEmail(),
            'roles' => $this->getRoles(),
            'enabled' => $this->isEnabled(),
            'username' => $this->getUsername(),
            'telephone' => $this->getTelephone(),
            'dateNaissance' => $this->getDateNaissance(),
            'lieuNaissance' => $this->getLieuNaissance(),
            'adresse' => $this->getAdresse(),
            'numeroElecteur' => $this->getNumeroElecteur() ?? null,
            'profession' => $this->getProfession(),
            'isHabitant' => $this->isHabitant(),
        ];
    }



    /**
     * Check if the user is a habitant.
     *
     * @return bool|null Returns true if the user is a habitant, false if not, or null if the status is unknown.
     */

    public function isHabitant(): ?bool
    {
        if ($this->fonctionsService === null || $this->getNumeroElecteur() === null) {
            return $this->habitant;
        }

        $resultat = $this->fonctionsService->checkNumeroElecteurExist($this->getNumeroElecteur());
        return $resultat ?? $this->habitant;
    }

    public function setHabitant(?bool $habitant): static
    {
        $this->habitant = $habitant;

        return $this;
    }
}
