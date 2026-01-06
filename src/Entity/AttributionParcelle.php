<?php

namespace App\Entity;

use App\Enum\StatutAttribution;
use App\Repository\AttributionParcelleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AttributionParcelleRepository::class)]
#[ORM\Table(name: '`gs_mairie_attribuation_parcelle`')]
class AttributionParcelle
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    private ?\DateTime $dateEffet = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type(\DateTimeInterface::class)]
    #[Assert\Expression(
        "this.getDateFin() === null or this.getDateEffet() === null or this.getDateFin() >= this.getDateEffet()",
        message: "La date de fin doit être postérieure à la date d'effet."
    )]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: "Le montant ne peut pas être négatif.")]
    private ?float $montant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $frequence = null;

    #[ORM\OneToOne(inversedBy: 'parcelleAttribuer', cascade: ['persist', 'remove'])]
    private ?Request $demande = null;

    #[ORM\Column(nullable: true)]
    private ?bool $etatPaiement = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Parcelle $parcelle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsMiseEnValeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dureeValidation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $decisionConseil = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pvCommision = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pvValidationProvisoire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pvAttributionProvisoire = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pvApprobationPrefet = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pvApprobationConseil = null;

    // ✅ Enum stocké
    #[ORM\Column(enumType: StatutAttribution::class, options: ['default' => 'DRAFT'])]
    private StatutAttribution $statutAttribution = StatutAttribution::VALIDATION_PROVISOIRE;

    // Dates d’étapes (facultatives mais utiles)
    #[ORM\Column(nullable: true)] private ?\DateTime $dateValidationProvisoire = null;
    #[ORM\Column(nullable: true)] private ?\DateTime $dateAttributionProvisoire = null;
    #[ORM\Column(nullable: true)] private ?\DateTime $dateApprobationPrefet = null;
    #[ORM\Column(nullable: true)] private ?\DateTime $dateApprobationConseil = null;
    #[ORM\Column(nullable: true)] private ?\DateTime $dateAttributionDefinitive = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $docNotificationUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfNotificationUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bulletinLiquidationUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numero = null;

    /* =================== Getters/Setters principaux =================== */


     public function __construct()
    {
        $this->numero = $this->generateCode();
    }

    public function generateCode(): string
    {
        $date = new \DateTime();
        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');
        $hours = $date->format('H');
        $minutes =   $date->format('i');
        // return 'AP' . $year . $month . $day . $hours . $minutes;
        return $year . $month . $day . $hours . $minutes;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEffet(): ?\DateTime
    {
        return $this->dateEffet;
    }
    public function setDateEffet(?\DateTime $dateEffet): static
    {
        $this->dateEffet = $dateEffet;
        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }
    public function setDateFin(?\DateTime $dateFin): static
    {
        if ($dateFin && $this->dateEffet && $dateFin < $this->dateEffet) {
            throw new \InvalidArgumentException("La date de fin doit être postérieure à la date d'effet.");
        }
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }
    public function setMontant(?float $montant): static
    {
        if ($montant !== null && $montant < 0) {
            throw new \InvalidArgumentException('Le montant ne peut pas être négatif.');
        }
        $this->montant = $montant;
        return $this;
    }

    public function getFrequence(): ?string
    {
        return $this->frequence;
    }
    public function setFrequence(?string $frequence): static
    {
        $this->frequence = $frequence;
        return $this;
    }

    public function getDemande(): ?Request
    {
        return $this->demande;
    }
    public function setDemande(?Request $demande): static
    {
        $this->demande = $demande;
        return $this;
    }

    public function isEtatPaiement(): ?bool
    {
        return $this->etatPaiement;
    }
    public function setEtatPaiement(?bool $etatPaiement): static
    {
        $this->etatPaiement = $etatPaiement;
        return $this;
    }

    public function getParcelle(): ?Parcelle
    {
        return $this->parcelle;
    }
    public function setParcelle(?Parcelle $parcelle): static
    {
        $this->parcelle = $parcelle;
        return $this;
    }

    public function getConditionsMiseEnValeur(): ?string
    {
        return $this->conditionsMiseEnValeur;
    }
    public function setConditionsMiseEnValeur(?string $conditionsMiseEnValeur): static
    {
        $this->conditionsMiseEnValeur = $conditionsMiseEnValeur;
        return $this;
    }

    public function getDureeValidation(): ?string
    {
        return $this->dureeValidation;
    }
    public function setDureeValidation(?string $dureeValidation): static
    {
        $this->dureeValidation = $dureeValidation;
        return $this;
    }

    public function getDecisionConseil(): ?string
    {
        return $this->decisionConseil;
    }
    public function setDecisionConseil(?string $decisionConseil): static
    {
        $this->decisionConseil = $decisionConseil;
        return $this;
    }

    public function getPvCommision(): ?string
    {
        return $this->pvCommision;
    }
    public function setPvCommision(?string $pvCommision): static
    {
        $this->pvCommision = $pvCommision;
        return $this;
    }

    public function getStatutAttribution(): StatutAttribution
    {
        return $this->statutAttribution;
    }
    public function setStatutAttribution(StatutAttribution $s): static
    {
        $this->statutAttribution = $s;
        return $this;
    }

    public function getDateValidationProvisoire(): ?\DateTime
    {
        return $this->dateValidationProvisoire;
    }
    public function setDateValidationProvisoire(?\DateTime $d): static
    {
        $this->dateValidationProvisoire = $d;
        return $this;
    }

    public function getDateAttributionProvisoire(): ?\DateTime
    {
        return $this->dateAttributionProvisoire;
    }
    public function setDateAttributionProvisoire(?\DateTime $d): static
    {
        $this->dateAttributionProvisoire = $d;
        return $this;
    }

    public function getDateApprobationPrefet(): ?\DateTime
    {
        return $this->dateApprobationPrefet;
    }
    public function setDateApprobationPrefet(?\DateTime $d): static
    {
        $this->dateApprobationPrefet = $d;
        return $this;
    }

    public function getDateApprobationConseil(): ?\DateTime
    {
        return $this->dateApprobationConseil;
    }
    public function setDateApprobationConseil(?\DateTime $d): static
    {
        $this->dateApprobationConseil = $d;
        return $this;
    }

    public function getDateAttributionDefinitive(): ?\DateTime
    {
        return $this->dateAttributionDefinitive;
    }
    public function setDateAttributionDefinitive(?\DateTime $d): static
    {
        $this->dateAttributionDefinitive = $d;
        return $this;
    }

    /* =================== Workflow: transitions & validations =================== */

    public function nextAllowedStatuses(): array
    {
        return match ($this->statutAttribution) {
            StatutAttribution::VALIDATION_PROVISOIRE => [StatutAttribution::ATTRIBUTION_PROVISOIRE, StatutAttribution::REJETEE, StatutAttribution::ANNULEE],
            StatutAttribution::ATTRIBUTION_PROVISOIRE => [StatutAttribution::APPROBATION_PREFET, StatutAttribution::REJETEE, StatutAttribution::ANNULEE],
            StatutAttribution::APPROBATION_PREFET => [StatutAttribution::APPROBATION_CONSEIL, StatutAttribution::REJETEE, StatutAttribution::ANNULEE],
            StatutAttribution::APPROBATION_CONSEIL => [StatutAttribution::ATTRIBUTION_DEFINITIVE, StatutAttribution::REJETEE, StatutAttribution::ANNULEE],
            StatutAttribution::ATTRIBUTION_DEFINITIVE => [],
            StatutAttribution::REJETEE, StatutAttribution::ANNULEE => [],
        };
    }

    public function canTransitionTo(StatutAttribution $target): bool
    {
        if (!in_array($target, $this->nextAllowedStatuses(), true))
            return false;

        return match ($target) {
            StatutAttribution::VALIDATION_PROVISOIRE =>
            !empty($this->pvValidationProvisoire),

            StatutAttribution::ATTRIBUTION_PROVISOIRE =>
            !empty($this->pvAttributionProvisoire),

            StatutAttribution::APPROBATION_PREFET =>
            !empty($this->pvApprobationPrefet),

            StatutAttribution::APPROBATION_CONSEIL =>
            !empty($this->decisionConseil) && !empty($this->pvApprobationConseil),

            StatutAttribution::ATTRIBUTION_DEFINITIVE =>
            $this->dateEffet !== null && $this->parcelle !== null && $this->demande !== null,

            default => true,
        };
    }


    public function transitionTo(StatutAttribution $target): void
    {
        if (!$this->canTransitionTo($target)) {
            throw new \DomainException(sprintf('Transition interdite: %s -> %s', $this->statutAttribution->value, $target->value));
        }

        $this->statutAttribution = $target;
        $now = new \DateTime();

        match ($target) {
            StatutAttribution::VALIDATION_PROVISOIRE => $this->dateValidationProvisoire = $now,
            StatutAttribution::ATTRIBUTION_PROVISOIRE => $this->dateAttributionProvisoire = $now,
            StatutAttribution::APPROBATION_PREFET => $this->dateApprobationPrefet = $now,
            StatutAttribution::APPROBATION_CONSEIL => $this->dateApprobationConseil = $now,
            StatutAttribution::ATTRIBUTION_DEFINITIVE => $this->dateAttributionDefinitive = $now,
            default => null,
        };
    }


    public function getPvValidationProvisoire(): ?string
    {
        return $this->pvValidationProvisoire;
    }
    public function setPvValidationProvisoire(?string $v): static
    {
        $this->pvValidationProvisoire = $v;
        return $this;
    }

    public function getPvAttributionProvisoire(): ?string
    {
        return $this->pvAttributionProvisoire;
    }
    public function setPvAttributionProvisoire(?string $v): static
    {
        $this->pvAttributionProvisoire = $v;
        return $this;
    }

    public function getPvApprobationPrefet(): ?string
    {
        return $this->pvApprobationPrefet;
    }
    public function setPvApprobationPrefet(?string $v): static
    {
        $this->pvApprobationPrefet = $v;
        return $this;
    }

    public function getPvApprobationConseil(): ?string
    {
        return $this->pvApprobationConseil;
    }
    public function setPvApprobationConseil(?string $v): static
    {
        $this->pvApprobationConseil = $v;
        return $this;
    }

    /* =================== Export simple =================== */


    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'numero' => $this->getNumero(),
            'dateEffet' => $this->getDateEffet()?->format('Y-m-d'),
            'dateFin' => $this->getDateFin()?->format('Y-m-d'),
            'montant' => $this->getMontant(),
            'frequence' => $this->getFrequence(),
            'etatPaiement' => $this->isEtatPaiement(),
            'parcelle' => $this->getParcelle()?->toArray(),
            'statut' => $this->getStatutAttribution()->value,
            'decisionConseil' => $this->getDecisionConseil(),

            // compat ancien champ si tu le gardes
            'pvCommision' => $this->getPvCommision(),
            'pvValidationProvisoire' => $this->getPvValidationProvisoire(),
            'pvAttributionProvisoire' => $this->getPvAttributionProvisoire(),
            'pvApprobationPrefet' => $this->getPvApprobationPrefet(),
            'pvApprobationConseil' => $this->getPvApprobationConseil(),
            // ✅ PVs dédiés
            'pvs' => [
                'validationProvisoire' => $this->getPvValidationProvisoire(),
                'attributionProvisoire' => $this->getPvAttributionProvisoire(),
                'approbationPrefet' => $this->getPvApprobationPrefet(),
                'approbationConseil' => $this->getPvApprobationConseil(),
            ],
            'bulletinLiquidationUrl' => $this->getBulletinLiquidationUrl(),
            'pdfNotificationUrl' => $this->getPdfNotificationUrl(),
            // ✅ Dates d’étapes
            'dates' => [
                'validationProvisoire' => $this->getDateValidationProvisoire()?->format(\DateTime::ATOM),
                'attributionProvisoire' => $this->getDateAttributionProvisoire()?->format(\DateTime::ATOM),
                'approbationPrefet' => $this->getDateApprobationPrefet()?->format(\DateTime::ATOM),
                'approbationConseil' => $this->getDateApprobationConseil()?->format(\DateTime::ATOM),
                'attributionDefinitive' => $this->getDateAttributionDefinitive()?->format(\DateTime::ATOM),
            ],
        ];
    }

    // App/Entity/AttributionParcelle.php

    public function canReopen(): bool
    {
        // Ici on autorise si déjà définitif; tu peux étendre à d’autres statuts
        return in_array($this->statutAttribution, [
            StatutAttribution::ATTRIBUTION_DEFINITIVE,
            StatutAttribution::APPROBATION_CONSEIL,
            StatutAttribution::APPROBATION_PREFET,
            // …si tu veux autoriser plus tôt aussi
        ], true);
    }

    public function reopenProcess(array $opts = []): void
    {
        if (!$this->canReopen()) {
            throw new \DomainException('Réouverture interdite pour le statut ' . $this->statutAttribution->value);
        }

        $to = strtoupper((string) ($opts['to'] ?? 'VALIDATION_PROVISOIRE'));
        $target = match ($to) {
            'ATTRIBUTION_PROVISOIRE' => StatutAttribution::ATTRIBUTION_PROVISOIRE,
            default => StatutAttribution::VALIDATION_PROVISOIRE,
        };

        // Purges optionnelles
        if (!empty($opts['resetDates'])) {
            $this->dateValidationProvisoire = null;
            $this->dateAttributionProvisoire = null;
            $this->dateApprobationPrefet = null;
            $this->dateApprobationConseil = null;
            $this->dateAttributionDefinitive = null;
        }
        if (!empty($opts['resetPVs'])) {
            $this->pvCommision = null;
            // Si tu as des champs PV par étape, purge-les ici
            if (method_exists($this, 'setPvValidationProvisoire'))
                $this->setPvValidationProvisoire(null);
            if (method_exists($this, 'setPvAttributionProvisoire'))
                $this->setPvAttributionProvisoire(null);
            if (method_exists($this, 'setPvApprobationPrefet'))
                $this->setPvApprobationPrefet(null);
            if (method_exists($this, 'setPvApprobationConseil'))
                $this->setPvApprobationConseil(null);
        }
        if (!empty($opts['resetDecision'])) {
            $this->decisionConseil = null;
        }
        $this->statutAttribution = $target;
    }

    public function getDocNotificationUrl(): ?string
    {
        return $this->docNotificationUrl;
    }

    public function setDocNotificationUrl(?string $docNotificationUrl): static
    {
        $this->docNotificationUrl = $docNotificationUrl;

        return $this;
    }

    public function getPdfNotificationUrl(): ?string
    {
        return $this->pdfNotificationUrl;
    }

    public function setPdfNotificationUrl(?string $pdfNotificationUrl): static
    {
        $this->pdfNotificationUrl = $pdfNotificationUrl;

        return $this;
    }

    public function getBulletinLiquidationUrl(): ?string
    {
        return $this->bulletinLiquidationUrl;
    }

    public function setBulletinLiquidationUrl(?string $bulletinLiquidationUrl): static
    {
        $this->bulletinLiquidationUrl = $bulletinLiquidationUrl;

        return $this;
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


}
