<?php

namespace App\Controller;

use App\Entity\Request as Demande;
use App\Entity\Request as EntityRequest; // pour les constantes de statut
use App\Entity\HistoriqueValidation;
use App\Entity\NiveauValidation;
use App\Repository\NiveauValidationRepository;
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
        private NiveauValidationRepository $niveauRepo,
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

        $this->addHistory($demande, action: 'RAPPORT_SAISI', commentaire: $rapport);
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
        $this->addHistory($demande, action: 'RECOMMANDATION', commentaire: $txt);
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
        $this->addHistory($demande, action: 'DECISION', commentaire: $txt);
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

        $ancien = $demande->getStatut();
        $demande->setStatut($statut);

        $this->addHistory(
            $demande,
            action: 'STATUT',
            commentaire: sprintf('Statut: %s → %s', $ancien, $statut),
            statutAvant: $ancien,
            statutApres: $statut
        );
        $this->em->flush();

        return $this->json([
            'message' => 'Statut mis à jour',
            'statut' => $demande->getStatut(),
        ]);
    }

    /* =========================================================
     *  Niveaux : valider / fixer / revenir
     *  (validateEtapeRequest, setNiveauRequest, revenir-niveau)
     * ========================================================= */

    #[Route('/{id}/valider-etape', name: 'demande_valider_etape', methods: ['POST'])]
    public function validerEtape(Demande $demande): JsonResponse
    {
        $user = $this->security->getUser();
        $current = $demande->getNiveauValidationActuel();

        // === DÉMARRAGE : aucun niveau courant -> premier niveau ===
        if (!$current) {
            $first = $this->niveauRepo->findFirst();
            if (!$first) {
                return $this->json(['message' => 'Aucun niveau de validation configuré'], Response::HTTP_BAD_REQUEST);
            }
            if (!$this->userHasRole($user, $first->getRoleRequis())) {
                return $this->json(['message' => 'Rôle requis: ' . $first->getRoleRequis()], Response::HTTP_FORBIDDEN);
            }

            $statutAvant = $demande->getStatut();
            $demande->setNiveauValidationActuel($first);

            if ($statutAvant === EntityRequest::STATUT_EN_ATTENTE) {
                $this->changerStatut($demande, EntityRequest::STATUT_EN_COURS_TRAITEMENT, 'Initialisation validation');
            }

            $this->addHistory(
                $demande,
                action: 'VALIDE',
                commentaire: 'Initialisation au premier niveau: ' . $first->getNom(),
                niveauNom: $first->getNom(),
                niveauOrdre: $first->getOrdre(),
                roleRequis: $first->getRoleRequis(),
                statutAvant: $statutAvant,
                statutApres: $demande->getStatut()
            );

            $this->em->flush();

            return $this->json([
                'ok' => true,
                'message' => 'Premier niveau positionné',
                'niveau' => [
                    'id' => $first->getId(),
                    'nom' => $first->getNom(),
                    'ordre' => $first->getOrdre(),
                    'roleRequis' => $first->getRoleRequis(),
                ],
                'statut' => $demande->getStatut(),
                // Optionnel : renvoyer l’item sérialisé si tu veux éviter un GET
                // 'item' => $this->serializeItem($demande),
            ], Response::HTTP_OK);
        }

        // === CONTRÔLE RÔLE SUR LE NIVEAU COURANT ===
        if (!$this->userHasRole($user, $current->getRoleRequis())) {
            return $this->json(['message' => 'Rôle requis: ' . $current->getRoleRequis()], Response::HTTP_FORBIDDEN);
        }

        // === PASSAGE AU NIVEAU SUIVANT ===
        $next = $this->niveauRepo->findNext($current->getOrdre());
        if ($next) {
            $statutAvant = $demande->getStatut();
            $demande->setNiveauValidationActuel($next);

            // Pas de changement de statut ici, tu peux en ajouter si besoin

            $this->addHistory(
                $demande,
                action: 'VALIDE',
                commentaire: 'Passage au niveau: ' . $next->getNom(),
                niveauNom: $next->getNom(),
                niveauOrdre: $next->getOrdre(),
                roleRequis: $next->getRoleRequis(),
                statutAvant: $statutAvant,
                statutApres: $demande->getStatut()
            );

            $this->em->flush();

            return $this->json([
                'ok' => true,
                'message' => 'Niveau suivant positionné',
                'niveau' => [
                    'id' => $next->getId(),
                    'nom' => $next->getNom(),
                    'ordre' => $next->getOrdre(),
                    'roleRequis' => $next->getRoleRequis(),
                ],
                'statut' => $demande->getStatut(),
                // 'item' => $this->serializeItem($demande),
            ], Response::HTTP_OK);
        }

        // === DERNIER NIVEAU : APPROUVER LA DEMANDE ===
        $statutAvant = $demande->getStatut();
        $this->changerStatut($demande, EntityRequest::STATUT_APPROUVE, 'Dernier niveau atteint');
        $demande->setNiveauValidationActuel(null);

        $this->addHistory(
            $demande,
            action: 'VALIDE',
            commentaire: 'Demande approuvée (dernier niveau atteint)',
            niveauNom: null,
            niveauOrdre: null,
            roleRequis: null,
            statutAvant: $statutAvant,
            statutApres: $demande->getStatut()
        );

        $this->em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Demande approuvée',
            'statut' => $demande->getStatut(), // "approved"
            // 'item' => $this->serializeItem($demande),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/niveau', name: 'demande_set_niveau', methods: ['PATCH'])]
    public function setNiveau(Demande $demande, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true) ?? [];
        // niveauId peut être null (ta page fait un premier appel avec null)
        $niveauId = $data['niveauId'] ?? null;

        if ($niveauId === null) {
            $demande->setNiveauValidationActuel(null);
            $this->addHistory($demande, action: 'NIVEAU_SET', commentaire: 'Niveau effacé manuellement');
        } else {
            $niveau = $this->em->getRepository(NiveauValidation::class)->find((int) $niveauId);
            if (!$niveau) {
                return $this->json(['success' => false, 'message' => 'Niveau introuvable'], Response::HTTP_BAD_REQUEST);
            }
            $demande->setNiveauValidationActuel($niveau);
            $this->addHistory(
                $demande,
                action: 'NIVEAU_SET',
                commentaire: 'Niveau positionné manuellement',
                niveauNom: $niveau->getNom(),
                niveauOrdre: $niveau->getOrdre(),
                roleRequis: $niveau->getRoleRequis()
            );
        }

        $this->em->flush();
        return $this->json(['success' => true]);
    }

    #[Route('/{id}/revenir-niveau', name: 'demande_revenir_niveau', methods: ['POST'])]
    public function revenirNiveau(Demande $demande): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $current = $demande->getNiveauValidationActuel();
        if (!$current) {
            return $this->json(['message' => 'Aucun niveau courant'], Response::HTTP_BAD_REQUEST);
        }

        $prev = $this->niveauRepo->findPrevious($current->getOrdre());
        if (!$prev) {
            return $this->json(['message' => 'Déjà au premier niveau'], Response::HTTP_BAD_REQUEST);
        }

        $demande->setNiveauValidationActuel($prev);
        $this->addHistory(
            $demande,
            action: 'NIVEAU_PREV',
            commentaire: 'Retour au niveau: ' . $prev->getNom(),
            niveauNom: $prev->getNom(),
            niveauOrdre: $prev->getOrdre(),
            roleRequis: $prev->getRoleRequis()
        );
        $this->em->flush();

        return $this->json(['ok' => true, 'niveau' => $prev->getNom()]);
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
        $demande->setNiveauValidationActuel(null);

        $this->addHistory(
            $demande,
            action: 'REJETE',
            motif: $motif,
            statutAvant: $old,
            statutApres: $demande->getStatut()
        );
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
        $ancien = $demande->getStatut();
        $demande->setStatut($nouveau);

        $this->addHistory(
            $demande,
            action: 'STATUT',
            commentaire: sprintf('Statut: %s → %s%s', $ancien, $nouveau, $raison ? ' (' . $raison . ')' : ''),
            statutAvant: $ancien,
            statutApres: $nouveau
        );
    }

    // private function addHistory(
    //     Demande $demande,
    //     string $action,
    //     ?string $motif = null,
    //     ?string $commentaire = null,
    //     ?string $niveauNom = null,
    //     ?int $niveauOrdre = null,
    //     ?string $roleRequis = null,
    //     ?string $statutAvant = null,
    //     ?string $statutApres = null
    // ): void {
    //     $h = new HistoriqueValidation();
    //     $h->setRequest($demande);
    //     $h->setValidateur($this->security->getUser());
    //     $h->setAction($action);

    //     if (method_exists($h, 'setMotif') && $motif !== null) {
    //         $h->setMotif($motif);
    //     }
    //     if (method_exists($h, 'setDateAction')) {
    //         $h->setDateAction(new \DateTime());
    //     }

    //     if ($niveauNom !== null && method_exists($h, 'setNiveauNom')) {
    //         $h->setNiveauNom($niveauNom);
    //     }
    //     if ($niveauOrdre !== null && method_exists($h, 'setNiveauOrdre')) {
    //         $h->setNiveauOrdre($niveauOrdre);
    //     }
    //     if ($roleRequis !== null && method_exists($h, 'setRoleRequis')) {
    //         $h->setRoleRequis($roleRequis);
    //     }
    //     if ($statutAvant !== null && method_exists($h, 'setStatutAvant')) {
    //         $h->setStatutAvant($statutAvant);
    //     }
    //     if ($statutApres !== null && method_exists($h, 'setStatutApres')) {
    //         $h->setStatutApres($statutApres);
    //     }

    //     $this->em->persist($h);
    //     if (method_exists($demande, 'addHistoriqueValidation')) {
    //         $demande->addHistoriqueValidation($h);
    //     }
    // }

    private function addHistory(
        Demande $demande,
        string $action,
        ?string $motif = null,
        ?string $commentaire = null,
        ?string $niveauNom = null,
        ?int $niveauOrdre = null,
        ?string $roleRequis = null,
        ?string $statutAvant = null,
        ?string $statutApres = null
    ): void {
        $h = new HistoriqueValidation();
        $h->setRequest($demande);
        $h->setValidateur($this->security->getUser());
        $h->setAction($action);

        if (method_exists($h, 'setMotif'))
            $h->setMotif($motif);
        if (method_exists($h, 'setNiveauNom'))
            $h->setNiveauNom($niveauNom);
        if (method_exists($h, 'setNiveauOrdre'))
            $h->setNiveauOrdre($niveauOrdre);
        if (method_exists($h, 'setRoleRequis'))
            $h->setRoleRequis($roleRequis);
        if (method_exists($h, 'setStatutAvant'))
            $h->setStatutAvant($statutAvant);
        if (method_exists($h, 'setStatutApres'))
            $h->setStatutApres($statutApres);
        if (method_exists($h, 'setDateAction'))
            $h->setDateAction(new \DateTime());

        $this->em->persist($h);
        if (method_exists($demande, 'addHistoriqueValidation')) {
            $demande->addHistoriqueValidation($h);
        }
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

        $hist = [];
        if (method_exists($d, 'getHistoriqueValidations')) {
            foreach ($d->getHistoriqueValidations() as $h) {
                $hist[] = [
                    'id' => method_exists($h, 'getId') ? $h->getId() : null,
                    'validateur' => $h->getValidateur() ? [
                        'id' => method_exists($h->getValidateur(), 'getId') ? $h->getValidateur()->getId() : null,
                        'nom' => method_exists($h->getValidateur(), 'getNom') ? $h->getValidateur()->getNom() : null,
                        'prenom' => method_exists($h->getValidateur(), 'getPrenom') ? $h->getValidateur()->getPrenom() : null,
                        'email' => method_exists($h->getValidateur(), 'getEmail') ? $h->getValidateur()->getEmail() : null,
                        'roles' => method_exists($h->getValidateur(), 'getRoles') ? $h->getValidateur()->getRoles() : [],
                        'enabled' => method_exists($h->getValidateur(), 'isEnabled') ? $h->getValidateur()->isEnabled() : null,
                        'username' => method_exists($h->getValidateur(), 'getUsername') ? $h->getValidateur()->getUsername() : null,
                        'telephone' => method_exists($h->getValidateur(), 'getTelephone') ? $h->getValidateur()->getTelephone() : null,
                        'dateNaissance' => method_exists($h->getValidateur(), 'getDateNaissance') ? $h->getValidateur()->getDateNaissance()?->format(\DateTimeInterface::ATOM) : null,
                        'lieuNaissance' => method_exists($h->getValidateur(), 'getLieuNaissance') ? $h->getValidateur()->getLieuNaissance() : null,
                        'adresse' => method_exists($h->getValidateur(), 'getAdresse') ? $h->getValidateur()->getAdresse() : null,
                        'numeroElecteur' => method_exists($h->getValidateur(), 'getNumeroElecteur') ? $h->getValidateur()->getNumeroElecteur() : null,
                        'profession' => method_exists($h->getValidateur(), 'getProfession') ? $h->getValidateur()->getProfession() : null,
                        'isHabitant' => method_exists($h->getValidateur(), 'isHabitant') ? $h->getValidateur()->isHabitant() : null,
                        'nombreEnfant' => method_exists($h->getValidateur(), 'getNombreEnfant') ? $h->getValidateur()->getNombreEnfant() : null,
                        'situationMatrimoniale' => method_exists($h->getValidateur(), 'getSituationMatrimoniale') ? $h->getValidateur()->getSituationMatrimoniale() : null,
                        'situationDemandeur' => method_exists($h->getValidateur(), 'getSituationDemandeur') ? $h->getValidateur()->getSituationDemandeur() : null,
                    ] : null,
                    'action' => method_exists($h, 'getAction') ? $h->getAction() : null,
                    'motif' => method_exists($h, 'getMotif') ? $h->getMotif() : null,
                    'dateAction' => method_exists($h, 'getDateAction') ? $h->getDateAction()?->format('Y-m-d H:i:s') : null,
                    'niveauNom' => method_exists($h, 'getNiveauNom') ? $h->getNiveauNom() : null,
                    'niveauOrdre' => method_exists($h, 'getNiveauOrdre') ? $h->getNiveauOrdre() : null,
                    'roleRequis' => method_exists($h, 'getRoleRequis') ? $h->getRoleRequis() : null,
                    'statutAvant' => method_exists($h, 'getStatutAvant') ? $h->getStatutAvant() : null,
                    'statutApres' => method_exists($h, 'getStatutApres') ? $h->getStatutApres() : null,
                ];
            }
        }

        $niveauActuel = null;
        if ($d->getNiveauValidationActuel()) {
            $n = $d->getNiveauValidationActuel();
            $niveauActuel = [
                'id' => $n->getId(),
                'nom' => $n->getNom(),
                'ordre' => $n->getOrdre(),
                'roleRequis' => $n->getRoleRequis(),
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

            'historiqueValidations' => $hist,
            'niveauValidationActuel' => $niveauActuel,

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
