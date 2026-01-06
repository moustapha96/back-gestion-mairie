<?php

namespace App\Controller;

use App\Entity\Request as Demande;
use App\Entity\Request as EntityRequest; // pour les constantes de statut

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/requests', name: "api_requests__")]
class RequestWorkflowController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    /* =========================================================
     *  SHOW (getDetailsRequest) + DOCUMENT (getFileRequest)
     * ========================================================= */

    #[Route('/{id}', name: 'demande_get_details', methods: ['GET'])]
    public function getOne(Demande $demande): JsonResponse
    {
        return $this->json([
            'success' => true,
            'item' => $this->serializeDemande($demande),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/document', name: 'demande_get_document', methods: ['GET'])]
    public function getDocument(Demande $demande): JsonResponse
    {
        // On suppose que $demande->getRecto()/getVerso() stockent soit un chemin, soit déjà un base64.
        // Adapte les 2 helpers ci-dessous à ton stockage réel.
        $recto = $this->toBase64OrNull($demande->getRecto());
        $verso = $this->toBase64OrNull($demande->getVerso());

        return $this->json([
            'success' => true,
            'recto' => $recto,
            'verso' => $verso,
        ]);
    }

    private function toBase64OrNull(?string $value): ?string
    {
        if (!$value)
            return null;

        // Déjà du base64 ? (heuristique simple)
        if (preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $value) && strlen($value) % 4 === 0) {
            return $value;
        }

        // Si c’est un chemin de fichier local lisible
        if (is_string($value) && @is_file($value)) {
            $bin = @file_get_contents($value);
            return $bin ? base64_encode($bin) : null;
        }

        // Sinon renvoie tel quel (ou null si tu préfères)
        return null;
    }

    /* =========================================================
     *  Mises à jour de contenus (rapport, recommandation, décision)
     * ========================================================= */

    #[Route('/{id}/rapport', name: 'demande_update_rapport', methods: ['PUT'])]
    public function updateRapport(Demande $demande, Request $request): JsonResponse
    {
        // $this->denyAccessUnlessGranted('ROLE_AGENT'); // ajuste selon ta politique

        $payload = json_decode($request->getContent(), true) ?? [];
        $rapport = trim((string) ($payload['rapport'] ?? ''));

        if ($rapport === '') {
            return $this->json(['message' => 'Le rapport est requis et ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $demande->setRapport($rapport);

        if ($demande->getStatut() === EntityRequest::STATUT_EN_ATTENTE) {
            $this->changerStatut($demande, EntityRequest::STATUT_EN_COURS_TRAITEMENT, 'Rapport saisi');
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Rapport mis à jour avec succès',
            'rapport' => $demande->getRapport(),
            'statut' => $demande->getStatut(),
        ]);
    }

    #[Route('/{id}/recommandation', name: 'demande_update_recommandation', methods: ['PUT'])]
    public function updateRecommandation(Demande $demande, Request $request): JsonResponse
    {
        // $this->denyAccessUnlessGranted('ROLE_PRESIDENT_COMMISSION'); // ou ROLE_CHEF_SERVICE via Voter

        $payload = json_decode($request->getContent(), true) ?? [];
        $txt = trim((string) ($payload['recommandation'] ?? ''));

        if ($txt === '') {
            return $this->json(['message' => 'La recommandation est requise et ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $demande->setRecommandation($txt);
        $this->em->flush();

        return $this->json([
            'message' => 'Recommandation mise à jour avec succès',
            'recommandation' => $demande->getRecommandation(),
        ]);
    }

    #[Route('/{id}/decision-commission', name: 'demande_update_decision_commission', methods: ['PUT'])]
    public function updateDecisionCommission(Demande $demande, Request $request): JsonResponse
    {
        // $this->denyAccessUnlessGranted('ROLE_COMMISSION');

        $payload = json_decode($request->getContent(), true) ?? [];
        $txt = trim((string) ($payload['decisionCommission'] ?? ''));

        if ($txt === '') {
            return $this->json(['message' => 'La décision de la commission est requise et ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $demande->setDecisionCommission($txt);
        $this->em->flush();

        return $this->json([
            'message' => 'Décision de la commission mise à jour avec succès',
            'decisionCommission' => $demande->getDecisionCommission(),
        ]);
    }

    /* =========================================================
     *  Statut (updateStatutRequest)
     *  - accepte statut FR OU enum interne
     * ========================================================= */

    #[Route('/{id}/statut', name: 'demande_update_statut', methods: ['PATCH'])]
    public function updateStatut(Demande $demande, Request $request): JsonResponse
    {
        // $this->denyAccessUnlessGranted('ROLE_ADMIN'); // ajuste

        $payload = json_decode($request->getContent(), true) ?? [];
        $input = trim((string) ($payload['statut'] ?? ''));

        if ($input === '') {
            return $this->json(['message' => 'Le statut est requis'], Response::HTTP_BAD_REQUEST);
        }

        // mapping FR -> enum interne si besoin
        $map = [
            'En attente' => EntityRequest::STATUT_EN_ATTENTE,
            'En cours de traitement' => EntityRequest::STATUT_EN_COURS_TRAITEMENT,
            'Rejetée' => EntityRequest::STATUT_REJETE,
            'Approuvée' => EntityRequest::STATUT_APPROUVE,
            // accepte aussi si le front envoie déjà l’enum :
            'pending' => EntityRequest::STATUT_EN_ATTENTE,
            'in_progress' => EntityRequest::STATUT_EN_COURS_TRAITEMENT,
            'rejected' => EntityRequest::STATUT_REJETE,
            'approved' => EntityRequest::STATUT_APPROUVE,
        ];

        $statut = $map[$input] ?? $input;
        $demande->setStatut($statut);

        
        $this->em->flush();

        return $this->json([
            'message' => 'Statut mis à jour',
            'statut' => $demande->getStatut(),
        ]);
    }

   

  
  
    /* =========================================================
     *  Rejet avec motif (updateRefusRequest)
     * ========================================================= */

    #[Route('/{id}/rejeter', name: 'demande_rejeter', methods: ['POST'])]
    public function rejeter(Demande $demande, Request $request): JsonResponse
    {
        // $this->denyAccessUnlessGranted('ROLE_MAIRE') 
        // || $this->denyAccessUnlessGranted('ROLE_ADMIN') 
        // || $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');
        

        $payload = json_decode($request->getContent(), true) ?? [];
        $motif = trim((string) ($payload['motif'] ?? ''));

        if ($motif === '') {
            return $this->json(['message' => 'Le motif est requis'], Response::HTTP_BAD_REQUEST);
        }

        $old = $demande->getStatut();
        $demande->setStatut(EntityRequest::STATUT_REJETE);
        if (method_exists($demande, 'setMotifRefus')) {
            $demande->setMotifRefus($motif);
        }
        $this->em->flush();

        return $this->json(['ok' => true, 'statut' => 'Rejetée']);
    }

    /* =========================================================
     *  Helpers
     * ========================================================= */

    private function userHasRole(?object $user, ?string $role): bool
    {
        if (!$user || !$role)
            return false;
        return \in_array($role, $user->getRoles(), true);
    }

    private function changerStatut(Demande $demande, string $nouveau, ?string $raison = null): void
    {
        if (!\in_array($nouveau, Demande::statutsValides(), true)) {
            throw new \InvalidArgumentException('Statut invalide: ' . $nouveau);
        }
        $demande->setStatut($nouveau);
    }

    
  
    private function serializeDemande(Demande $d): array
    {
        // On renvoie un objet compact, ta page a un adapter côté front.
        $demandeurFlat = [
            'prenom' => $d->getPrenom(),
            'nom' => $d->getNom(),
            'email' => $d->getEmail(),
            'telephone' => $d->getTelephone(),
            'adresse' => $d->getAdresse(),
            'profession' => $d->getProfession(),
            'numeroElecteur' => $d->getNumeroElecteur(),
            'dateNaissance' => $d->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $d->getLieuNaissance(),
            'situationMatrimoniale' => $d->getSituationMatrimoniale(),
            'statutLogement' => $d->getStatutLogement(),
            'nombreEnfant' => $d->getNombreEnfant(),
            'isHabitant' => method_exists($d, 'isHabitant') ? $d->isHabitant() : false,
        ];

        $quartier = null;
        if (method_exists($d, 'getQuartier') && $d->getQuartier()) {
            $q = $d->getQuartier();
            $quartier = [
                'id' => method_exists($q, 'getId') ? $q->getId() : null,
                'nom' => method_exists($q, 'getNom') ? $q->getNom() : null,
                'description' => method_exists($q, 'getDescription') ? $q->getDescription() : null,
                'latitude' => method_exists($q, 'getLatitude') ? $q->getLatitude() : null,
                'longitude' => method_exists($q, 'getLongitude') ? $q->getLongitude() : null,
                'prix' => method_exists($q, 'getPrix') ? $q->getPrix() : null,
            ];
        }

        
        return [
            'id' => $d->getId(),
            'typeDemande' => $d->getTypeDemande(),
            'typeDocument' => $d->getTypeDocument(),
            'superficie' => $d->getSuperficie(),
            'usagePrevu' => $d->getUsagePrevu(),
            'possedeAutreTerrain' => $d->isPossedeAutreTerrain(),
            'statut' => $d->getStatut(),
            'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $d->getDateModification()?->format('Y-m-d H:i:s'),
            'motif_refus' => method_exists($d, 'getMotifRefus') ? $d->getMotifRefus() : null,
            'recto' => $d->getRecto(),   // la page appellera /document si nécessaire
            'verso' => $d->getVerso(),
            'rapport' => $d->getRapport(),
            'typeTitre' => $d->getTypeTitre(),
            'terrainAKaolack' => $d->isTerrainAKaolack(),
            'terrainAilleurs' => $d->isTerrainAilleurs(),
            'decisionCommission' => $d->getDecisionCommission(),
            'recommandation' => $d->getRecommandation(),
            'localite' => method_exists($d, 'getLocaliteTexte') ? $d->getLocalite() : null,

            // ces champs à plat sont utilisés par ton adapter front
            'nom' => $d->getNom(),
            'prenom' => $d->getPrenom(),
            'email' => $d->getEmail(),
            'telephone' => $d->getTelephone(),
            'dateNaissance' => $d->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance' => $d->getLieuNaissance(),
            'adresse' => $d->getAdresse(),
            'numeroElecteur' => $d->getNumeroElecteur(),
            'profession' => $d->getProfession(),
            'isHabitant' => method_exists($d, 'isHabitant') ? $d->isHabitant() : null,

            'quartier' => $quartier,
            'demandeur' => $demandeurFlat,
        ];
    }
}
