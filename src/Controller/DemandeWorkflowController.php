<?php

namespace App\Controller;

use App\Entity\DemandeTerrain;
use App\Entity\HistoriqueValidation;
use App\Repository\NiveauValidationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class DemandeWorkflowController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private NiveauValidationRepository $niveauRepo
    ) {}

    public function setRapport(Request $req, DemandeTerrain $demande): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_AGENT'); // adapte si besoin
        $payload = json_decode($req->getContent(), true) ?? [];
        $rapport = trim((string)($payload['rapport'] ?? ''));

        if ($rapport === '') {
            return $this->json(['error' => 'rapport requis'], 400);
        }

        $demande->setRapport($rapport);
        $this->addHistory($demande, 'RAPPORT_SAISI', commentaire: $rapport);

        // passer en "En cours de traitement" si encore en attente
        if ($demande->getStatut() === 'En attente') {
            $demande->setStatut('En cours de traitement');
        }

        $this->em->flush();
        return $this->json(['ok' => true]);
    }

    public function setRecommandation(Request $req, DemandeTerrain $demande): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PRESIDENT_COMMISSION'); // ou ROLE_CHEF_SERVICE via voter personnalisé
        $payload = json_decode($req->getContent(), true) ?? [];
        $txt = trim((string)($payload['recommandation'] ?? ''));

        if ($txt === '') {
            return $this->json(['error' => 'recommandation requise'], 400);
        }

        $demande->setRecommandation($txt);
        $this->addHistory($demande, 'RECOMMANDATION', commentaire: $txt);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    public function setDecision(Request $req, DemandeTerrain $demande): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COMMISSION');
        $payload = json_decode($req->getContent(), true) ?? [];
        $txt = trim((string)($payload['decision'] ?? ''));

        if ($txt === '') {
            return $this->json(['error' => 'decision requise'], 400);
        }

        $demande->setDecisionCommission($txt);
        $this->addHistory($demande, 'DECISION', commentaire: $txt);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    /** Valide l’étape courante (vérifie roleRequis) et passe au niveau suivant ou termine */
    public function validerEtape(Request $req, DemandeTerrain $demande): JsonResponse
    {
        $user = $this->security->getUser();
        $current = $demande->getNiveauValidationActuel();

        // si pas de niveau courant, on démarre au premier
        if (!$current) {
            $next = $this->niveauRepo->findFirst();
            if (!$next) {
                return $this->json(['error' => 'Aucun niveau de validation configuré'], 400);
            }
            // contrôle rôle pour premier niveau
            if (!$this->userHasRole($user, $next->getRoleRequis())) {
                return $this->json(['error' => 'Rôle requis: '.$next->getRoleRequis()], 403);
            }
            $demande->setNiveauValidationActuel($next);
            $this->addHistory($demande, 'VALIDE', commentaire: 'Initialisation au premier niveau: '.$next->getNom());
            $this->em->flush();
            return $this->json(['ok' => true, 'niveau' => $next->getNom()]);
        }

        // Vérifie que l’utilisateur possède le rôle requis pour le niveau courant
        if (!$this->userHasRole($user, $current->getRoleRequis())) {
            return $this->json(['error' => 'Rôle requis: '.$current->getRoleRequis()], 403);
        }

        // Passe au niveau suivant
        $next = $this->niveauRepo->findNext($current->getOrdre());
        if ($next) {
            $demande->setNiveauValidationActuel($next);
            $this->addHistory($demande, 'VALIDE', commentaire: 'Passage au niveau: '.$next->getNom());
            $this->em->flush();
            return $this->json(['ok' => true, 'niveau' => $next->getNom()]);
        }

        // Pas de niveau suivant → demande approuvée
        $demande->setStatut('Approuvée');
        $this->addHistory($demande, 'VALIDE', commentaire: 'Demande approuvée (dernier niveau atteint)');
        $this->em->flush();

        return $this->json(['ok' => true, 'statut' => 'Approuvée']);
    }

    /** Rejette la demande avec motif (peu importe le niveau) */
    public function rejeter(Request $req, DemandeTerrain $demande): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MAIRE'); // ou ROLE_ADMIN/ROLE_SUPER_ADMIN, adapte
        $payload = json_decode($req->getContent(), true) ?? [];
        $motif = trim((string)($payload['motif'] ?? ''));

        if ($motif === '') {
            return $this->json(['error' => 'motif requis'], 400);
        }

        $demande->setStatut('Rejetée');
        $demande->setMotifRefus($motif);
        $demande->setNiveauValidationActuel(null);

        $this->addHistory($demande, 'REJETE', motif: $motif);
        $this->em->flush();

        return $this->json(['ok' => true, 'statut' => 'Rejetée']);
    }

    private function addHistory(
        DemandeTerrain $demande,
        string $action,
        ?string $motif = null,
        ?string $commentaire = null
    ): void {
        $h = new HistoriqueValidation();
        $h->setDemande($demande);
        $h->setValidateur($this->security->getUser());
        $h->setAction($action);
        $h->setMotif($motif);
        $h->setDateAction(new \DateTime());
        $this->em->persist($h);
        $demande->addHistoriqueValidation($h);
    }

    private function userHasRole(?object $user, ?string $role): bool
    {
        if (!$user || !$role) return false;
        return in_array($role, $user->getRoles(), true);
    }
}
