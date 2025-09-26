<?php



// src/Service/ValidationService.php
namespace App\services;

use App\Entity\DemandeTerrain;
use App\Entity\NiveauValidation;
use App\Entity\User;
use App\Entity\HistoriqueValidation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ValidationService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validerDemande(DemandeTerrain $demande, User $validateur): void
    {
        $niveauActuel = $demande->getNiveauValidationActuel();
        if (!$this->estAutorise($validateur, $niveauActuel)) {
            throw new AccessDeniedException("Vous n'êtes pas autorisé à valider cette demande.");
        }

        // Enregistrer l'historique
        $historique = new HistoriqueValidation();
        $historique->setDemande($demande);
        $historique->setValidateur($validateur);
        $historique->setAction("validé");
        $demande->addHistoriqueValidation($historique);

        // Passer au niveau suivant
        $niveauSuivant = $this->getNiveauSuivant($niveauActuel);
        if ($niveauSuivant !== null) {
            $demande->setNiveauValidationActuel($niveauSuivant);
        } else {
            $demande->setStatut(DemandeTerrain::STATUT_APPROUVE);
        }

        $demande->setDateModification(new \DateTime());
        $this->entityManager->flush();
    }

    public function rejeterDemande(DemandeTerrain $demande, User $validateur, string $motif): void
    {
        $niveauActuel = $demande->getNiveauValidationActuel();
        if (!$this->estAutorise($validateur, $niveauActuel)) {
            throw new AccessDeniedException("Vous n'êtes pas autorisé à rejeter cette demande.");
        }

        // Enregistrer l'historique
        $historique = new HistoriqueValidation();
        $historique->setDemande($demande);
        $historique->setValidateur($validateur);
        $historique->setAction("rejeté");
        $historique->setMotif($motif);
        $demande->addHistoriqueValidation($historique);

        $demande->setStatut(DemandeTerrain::STATUT_REJETE);
        $demande->setMotifRefus($motif);
        $demande->setDateModification(new \DateTime());
        $this->entityManager->flush();
    }

    private function estAutorise(User $user, NiveauValidation $niveau): bool
    {
        return in_array($niveau->getRoleRequis(), $user->getRoles());
    }

    private function getNiveauSuivant(NiveauValidation $niveauActuel): ?NiveauValidation
    {
        return $this->entityManager->getRepository(NiveauValidation::class)
            ->findOneBy(['ordre' => $niveauActuel->getOrdre() + 1]);
    }

   
}
