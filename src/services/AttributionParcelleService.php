<?php

namespace App\services;

use App\Entity\AttributionParcelle;
use App\Entity\Parcelle;
use App\Entity\Request;
use App\Enum\StatutAttribution;
use App\Repository\AttributionParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;

final class AttributionParcelleService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AttributionParcelleRepository $repo,
    ) {}

    /* =================== CRUD simples =================== */

    public function get(int $id): ?AttributionParcelle { return $this->repo->find($id); }

    public function create(Request $demande, Parcelle $parcelle, array $data = []): AttributionParcelle
    {
        $a = new AttributionParcelle();
        $a->setDemande($demande);
        $a->setParcelle($parcelle);

        if (isset($data['montant'])) $a->setMontant((float)$data['montant']);
        if (isset($data['frequence'])) $a->setFrequence((string)$data['frequence']);
        if (isset($data['conditionsMiseEnValeur'])) $a->setConditionsMiseEnValeur((string)$data['conditionsMiseEnValeur']);
        if (isset($data['dureeValidation'])) $a->setDureeValidation((string)$data['dureeValidation']);

        if (!empty($data['dateEffet']) && $data['dateEffet'] instanceof \DateTimeInterface) {
            $a->setDateEffet(\DateTime::createFromInterface($data['dateEffet']));
        }
        if (!empty($data['dateFin']) && $data['dateFin'] instanceof \DateTimeInterface) {
            $a->setDateFin(\DateTime::createFromInterface($data['dateFin']));
        }

        if (array_key_exists('etatPaiement', $data)) $a->setEtatPaiement((bool)$data['etatPaiement']);
        if (array_key_exists('decisionConseil', $data)) $a->setDecisionConseil($data['decisionConseil'] ?: null);
        if (array_key_exists('pvCommision', $data)) $a->setPvCommision($data['pvCommision'] ?: null);

        $this->em->persist($a);
        $this->em->flush();

        return $a;
    }

    public function update(AttributionParcelle $a, array $data = []): AttributionParcelle
    {
        if (isset($data['montant'])) $a->setMontant((float)$data['montant']);
        if (isset($data['frequence'])) $a->setFrequence((string)$data['frequence']);
        if (isset($data['conditionsMiseEnValeur'])) $a->setConditionsMiseEnValeur((string)$data['conditionsMiseEnValeur']);
        if (isset($data['dureeValidation'])) $a->setDureeValidation((string)$data['dureeValidation']);

        if (!empty($data['dateEffet']) && $data['dateEffet'] instanceof \DateTimeInterface) $a->setDateEffet(\DateTime::createFromInterface($data['dateEffet']));
        if (!empty($data['dateFin']) && $data['dateFin'] instanceof \DateTimeInterface)   $a->setDateFin(\DateTime::createFromInterface($data['dateFin']));

        if (array_key_exists('etatPaiement', $data)) $a->setEtatPaiement((bool)$data['etatPaiement']);
        if (array_key_exists('decisionConseil', $data)) $a->setDecisionConseil($data['decisionConseil'] ?: null);
        if (array_key_exists('pvCommision', $data)) $a->setPvCommision($data['pvCommision'] ?: null);

        $this->em->flush();
        return $a;
    }

    /* =================== Utilitaires =================== */

    public function computeDateFinFromDuree(AttributionParcelle $a): void
    {
        $effet = $a->getDateEffet();
        $duree = $a->getDureeValidation();
        if (!$effet || !$duree) return;

        $end = (clone $effet);
        if (preg_match('/^P/i', $duree)) {
            $end->add(new \DateInterval($duree));
        } elseif (preg_match('/(\d+)\s*mois/i', $duree, $m)) {
            $end->modify('+' . (int)$m[1] . ' month');
        } elseif (preg_match('/(\d+)\s*an/i', $duree, $m)) {
            $end->modify('+' . (int)$m[1] . ' year');
        } elseif (preg_match('/(\d+)\s*jour/i', $duree, $m)) {
            $end->modify('+' . (int)$m[1] . ' day');
        }

        $a->setDateFin($end);
        $this->em->flush();
    }

    /* =================== Transitions (validation forte) =================== */

    public function validerProvisoire(AttributionParcelle $a): AttributionParcelle
    {
        if (!$a->getPvCommision()) throw new \DomainException("PV de commission requis avant validation provisoire.");
        return $this->transition($a, StatutAttribution::VALIDATION_PROVISOIRE, 'Validation provisoire');
    }

    public function attribuerProvisoire(AttributionParcelle $a): AttributionParcelle
    {
        if (!$a->getPvCommision()) throw new \DomainException("PV de commission requis avant attribution provisoire.");
        return $this->transition($a, StatutAttribution::ATTRIBUTION_PROVISOIRE, 'Attribution provisoire');
    }

    public function approuverPrefet(AttributionParcelle $a): AttributionParcelle
    {
        return $this->transition($a, StatutAttribution::APPROBATION_PREFET, 'Approbation Préfet');
    }

    public function approuverConseil(AttributionParcelle $a, string $decision, string $pv, ?\DateTimeInterface $date = null): AttributionParcelle
    {
        if (trim($decision) === '' || trim($pv) === '') {
            throw new \DomainException("decisionConseil et pv sont requis pour l'approbation Conseil.");
        }
        $a->setDecisionConseil($decision);
        $a->setPvCommision($pv);
        $a->transitionTo(StatutAttribution::APPROBATION_CONSEIL);
      
        $this->em->persist($a);
        $this->em->flush();

        return $a;
    }

    public function attribuerDefinitive(AttributionParcelle $a, ?\DateTimeInterface $dateEffet = null): AttributionParcelle
    {
        if ($dateEffet) $a->setDateEffet(\DateTime::createFromInterface($dateEffet));
        if (!$a->getDateEffet() || !$a->getParcelle() || !$a->getDemande()) {
            throw new \DomainException("dateEffet, parcelle et demande sont requis pour l'attribution définitive.");
        }
        $a->transitionTo(StatutAttribution::ATTRIBUTION_DEFINITIVE);
        $this->computeDateFinFromDuree($a);
        return $a;
    }

    public function rejeter(AttributionParcelle $a, string $motif = ''): AttributionParcelle
    {
        return $this->transition($a, StatutAttribution::REJETEE, 'Rejet: '.$motif);
    }

    public function annuler(AttributionParcelle $a, string $motif = ''): AttributionParcelle
    {
        return $this->transition($a, StatutAttribution::ANNULEE, 'Annulation: '.$motif);
    }

    /* =================== Noyau de transition =================== */

    public function transition(AttributionParcelle $a, StatutAttribution $to, ?string $comment = null): AttributionParcelle
    {
        // Historique (si l’entité existe)
        $from = $a->getStatutAttribution();

        $a->transitionTo($to);
        $this->em->persist($a);

        if (class_exists(\App\Entity\AttributionParcelleStatusHistory::class)) {
            $h = new \App\Entity\AttributionParcelleStatusHistory();
            $h->setAttribution($a);
            $h->setFromStatus($from);
            $h->setToStatus($to);
            $h->setChangedAt(new \DateTime());
            $h->setComment($comment);
            $this->em->persist($h);
        }

        $this->em->flush();
        return $a;
    }

    
}
