<?php

namespace App\Controller;

use App\Entity\AttributionParcelle;
use App\Entity\Parcelle;
use App\Entity\Request as Demande;
use App\Entity\User;
use App\Enum\StatutAttribution;
use App\Repository\AttributionParcelleRepository;
use App\Repository\ParcelleRepository;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use App\services\NotificationAttributionGenerator;
use App\services\AttributionMailer;
use App\services\AttributionParcelleService;
use App\services\FonctionsService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/attributions-parcelles', name: 'api_attributions_parcelles_')]
class AttributionParcelleController extends AbstractController
{

    public $fonctionsService;
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AttributionParcelleRepository $attribRepo,
        private readonly RequestRepository $demandeRepo,
        private readonly ParcelleRepository $parcelleRepo,
        private readonly UserRepository $userRepository,
        private readonly AttributionParcelleService $svc,
        private readonly string $fileBaseUrl,
        private AttributionMailer $attribMailer,
        FonctionsService $fonctionsService,
    ) {
        $this->fonctionsService = $fonctionsService;
    }

    /* ---------------- Helpers ---------------- */

    private function ok(mixed $data = null, int $status = 200): JsonResponse
    {
        return $this->json(['success' => $status >= 200 && $status < 300, 'data' => $data], $status);
    }
    private function error(string $message, int $status = 400, mixed $extra = null): JsonResponse
    {
        $p = ['success' => false, 'message' => $message];
        if ($extra !== null)
            $p['extra'] = $extra;
        return $this->json($p, $status);
    }
    private function parseDate(?string $value): ?\DateTimeInterface
    {
        if (!$value)
            return null;
        try {
            return new \DateTime($value);
        } catch (\Throwable) {
        }
        foreach (['Y-m-d', 'd/m/Y', 'Y-m-d H:i:s', 'd/m/Y H:i:s'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $value);
            if ($d instanceof \DateTime)
                return $d;
        }
        return null;
    }
    private function toBoolOrNull(mixed $v): ?bool
    {
        if ($v === null || $v === '')
            return null;
        if (is_bool($v))
            return $v;
        $s = strtolower((string) $v);
        if (in_array($s, ['1', 'true', 'vrai', 'oui', 'yes'], true))
            return true;
        if (in_array($s, ['0', 'false', 'faux', 'non', 'no'], true))
            return false;
        return null;
    }

    private function serializeItem(AttributionParcelle $ap): array
    {
        $p = $ap->getParcelle();
        $l = $p?->getLotissement();
        $loc = $l?->getLocalite();

        $nextAllowed = array_map(fn($s) => $s->value, $ap->nextAllowedStatuses());

        return [
            'id' => $ap->getId(),
            'dateEffet' => $ap->getDateEffet()?->format(DATE_ATOM),
            'dateFin' => $ap->getDateFin()?->format(DATE_ATOM),
            'montant' => $ap->getMontant(),
            'frequence' => $ap->getFrequence(),
            'etatPaiement' => $ap->isEtatPaiement(),
            'statut' => $ap->getStatutAttribution()->value,
            'decisionConseil' => $ap->getDecisionConseil(),
            'pvCommision' => $ap->getPvCommision(),
            'pvValidationProvisoire' => $ap->getPvValidationProvisoire(),
            'pvAttributionProvisoire' => $ap->getPvAttributionProvisoire(),
            'pvApprobationPrefet' => $ap->getPvApprobationPrefet(),
            'pvApprobationConseil' => $ap->getPvApprobationConseil(),
            'nextAllowed' => $nextAllowed,
            'canReopen' => $ap->canReopen(),
            'bulletinLiquidationUrl' => $ap->getBulletinLiquidationUrl(),
            'pdfNotificationUrl' => $ap->getPdfNotificationUrl(),
            'datesEtapes' => [
                'validationProvisoire' => $ap->getDateValidationProvisoire()?->format(DATE_ATOM),
                'attributionProvisoire' => $ap->getDateAttributionProvisoire()?->format(DATE_ATOM),
                'approbationPrefet' => $ap->getDateApprobationPrefet()?->format(DATE_ATOM),
                'approbationConseil' => $ap->getDateApprobationConseil()?->format(DATE_ATOM),
                'attributionDefinitive' => $ap->getDateAttributionDefinitive()?->format(DATE_ATOM),
            ],
            'demande' => $ap->getDemande() ? $this->serializeItemDemande($ap->getDemande()) : null,
            'parcelle' => $p ? [
                'id' => $p->getId(),
                'numero' => $p->getNumero(),
                'surface' => $p->getSurface(),
                'statut' => $p->getStatut(),
                'latitude' => $p->getLatitude(),
                'longitude' => $p->getLongitude(),
                'lotissement' => $l ? [
                    'id' => $l->getId(),
                    'nom' => $l->getNom(),
                    'localisation' => $l->getLocalisation(),
                    'description' => $l->getDescription(),
                    'statut' => $l->getStatut(),
                    'dateCreation' => $l->getDateCreation()?->format('Y-m-d'),
                    'latitude' => $l->getLatitude(),
                    'longitude' => $l->getLongitude(),
                    'localite' => $loc ? [
                        'id' => $loc->getId(),
                        'nom' => $loc->getNom(),
                        'prix' => $loc->getPrix(),
                        'latitude' => $loc->getLatitude(),
                        'longitude' => $loc->getLongitude(),
                    ] : null,
                ] : null,
                'proprietaire' => $p->getProprietaire() ? $this->serializeItemProprietaire($p->getProprietaire()) : null,
            ] : null,

        ];
    }

    private function serializeItemProprietaire(User $p): array
    {
        return [
            'id' => $p->getId(),
            'nom' => $p->getNom(),
            'prenom' => $p->getPrenom(),
            'email' => $p->getEmail(),
            'telephone' => $p->getTelephone(),
            'adresse' => $p->getAdresse(),
            'profession' => $p->getProfession(),
            'numeroElecteur' => $p->getNumeroElecteur(),
            'dateNaissance' => $p->getDateNaissance()?->format('Y-m-d'),
            'nombreEnfant' => $p->getNombreEnfant(),
            'situationMatrimoniale' => $p->getSituationMatrimoniale(),
            'situationDemandeur' => $p->getSituationDemandeur(),
            'isHabitant' => $p->isHabitant(),
          
        ];
    }

    /* ---------------- Listing simple (exemple minimal) ---------------- */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $req): JsonResponse
    {
        $page = max(1, (int) $req->query->get('page', 1));
        $size = max(1, min(200, (int) $req->query->get('pageSize', 10)));
        $qb = $this->attribRepo->createQueryBuilder('ap')->orderBy('ap.id', 'DESC');

        $count = (int) (clone $qb)->select('COUNT(ap.id)')->getQuery()->getSingleScalarResult();
        $items = $qb->setFirstResult(($page - 1) * $size)->setMaxResults($size)->getQuery()->getResult();
        return $this->ok([
            'page' => $page,
            'pageSize' => $size,
            'total' => $count,
            'items' => array_map(fn($ap) => $this->serializeItem($ap), $items),
        ]);
    }

    /* ---------------- CRUD ---------------- */

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $req): JsonResponse
    {
        $p = json_decode($req->getContent(), true) ?? [];
        $demande = $this->demandeRepo->find((int) ($p['demandeId'] ?? 0));
        $parcelle = $this->parcelleRepo->find((int) ($p['parcelleId'] ?? 0));
        if (!$demande || !$parcelle)
            return $this->error("demandeId/parcelleId requis.", 422);

        if ($this->attribRepo->findOneBy(['demande' => $demande]))
            return $this->error("Demande déjà attribuée.", 409);
        if ($this->attribRepo->findOneBy(['parcelle' => $parcelle]))
            return $this->error("Parcelle déjà attribuée.", 409);

        if ($parcelle->getProprietaire() !== null)
            return $this->error("Parcelle non disponible.", 409);

        $data = [
            'montant' => $p['montant'] ?? null,
            'frequence' => $p['frequence'] ?? null,
            'conditionsMiseEnValeur' => $p['conditionsMiseEnValeur'] ?? null,
            'dureeValidation' => $p['dureeValidation'] ?? null,
            'dateEffet' => $this->parseDate($p['dateEffet'] ?? null),
            'dateFin' => $this->parseDate($p['dateFin'] ?? null),
            'etatPaiement' => $this->toBoolOrNull($p['etatPaiement'] ?? null),
            'decisionConseil' => $p['decisionConseil'] ?? null,
            'pvCommision' => $p['pvCommision'] ?? null,
        ];

        $ap = $this->svc->create($demande, $parcelle, $data);

        // marquer la parcelle occupée
        $parcelle->setStatut('OCCUPE');
        if (method_exists($demande, 'getUtilisateur') && $demande->getUtilisateur()) {
            $parcelle->setProprietaire($demande->getUtilisateur());
        }
        $this->em->flush();

        return $this->ok($this->serializeItem($ap), 201);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Introuvable', 404);
        return $this->ok($this->serializeItem($ap));
    }

    #[Route('/by-demande/{demandeId}', name: 'by_demande', methods: ['GET'])]
    public function byDemande(int $demandeId): JsonResponse
    {
        $demande = $this->demandeRepo->find($demandeId);
        if (!$demande)
            return $this->error('Demande introuvable', 404);
        $ap = $this->attribRepo->findOneBy(['demande' => $demande]);
        return $this->ok($ap ? $this->serializeItem($ap) : null);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Introuvable', 404);

        $p = json_decode($req->getContent(), true) ?? [];
        $data = [];
        if (array_key_exists('montant', $p))
            $data['montant'] = $p['montant'];
        if (array_key_exists('frequence', $p))
            $data['frequence'] = $p['frequence'];
        if (array_key_exists('conditionsMiseEnValeur', $p))
            $data['conditionsMiseEnValeur'] = $p['conditionsMiseEnValeur'];
        if (array_key_exists('dureeValidation', $p))
            $data['dureeValidation'] = $p['dureeValidation'];
        if (array_key_exists('dateEffet', $p))
            $data['dateEffet'] = $this->parseDate($p['dateEffet']);
        if (array_key_exists('dateFin', $p))
            $data['dateFin'] = $this->parseDate($p['dateFin']);
        if (array_key_exists('etatPaiement', $p))
            $data['etatPaiement'] = $this->toBoolOrNull($p['etatPaiement']);
        if (array_key_exists('decisionConseil', $p))
            $data['decisionConseil'] = $p['decisionConseil'];
        if (array_key_exists('pvCommision', $p))
            $data['pvCommision'] = $p['pvCommision'];

        $this->svc->update($ap, $data);

        // Option: changement de parcelle
        if (!empty($p['parcelleId'])) {
            $newParcelle = $this->parcelleRepo->find((int) $p['parcelleId']);
            if (!$newParcelle)
                return $this->error('Nouvelle parcelle introuvable', 404);
            $exists = $this->attribRepo->findOneBy(['parcelle' => $newParcelle]);
            if ($exists && $exists->getId() !== $ap->getId())
                return $this->error("Parcelle déjà attribuée.", 409);
            if ($newParcelle->getProprietaire() !== null)
                return $this->error("Parcelle non disponible.", 409);
            $ap->setParcelle($newParcelle);
            $this->em->flush();
        }

        return $this->ok($this->serializeItem($ap));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Introuvable', 404);

        $liberer = (bool) ($req->query->get('liberer') ?? false);
        $p = $ap->getParcelle();

        if ($liberer && $p) {
            $p->setStatut('DISPONIBLE');
            $p->setProprietaire(null);
            $this->em->persist($p);
        }
        $ap->setParcelle(null);
        $demande = $ap->getDemande();
        if ($demande && method_exists($demande, 'setParcelleAttribuer')) {
            $demande->setParcelleAttribuer(null);
            $this->em->persist($demande);
        }

        $this->em->remove($ap);
        $this->em->flush();
        return $this->ok(['deleted' => true]);
    }

    #[Route('/{id}/statut-paiement', name: '_change_statut_paiement', methods: ['PATCH'])]
    public function changeStatutPaiement(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Introuvable', 404);
        $data = json_decode($req->getContent(), true) ?? [];
        $ap->setEtatPaiement($this->toBoolOrNull($data['etatPaiement'] ?? null));
        $this->em->flush();
        return $this->ok($this->serializeItem($ap));
    }

    /* ---------------- Transitions (validation forte) ---------------- */


    #[Route('/{id}/valider-provisoire', name: 'valider_provisoire', methods: ['PATCH'])]
    public function validerProvisoire(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Attribution introuvable', 404);

        $body = json_decode($req->getContent(), true) ?? [];
        $pv = trim((string) ($body['pv'] ?? ''));
        if ($pv === '')
            return $this->error('PV requis', 422);

        $ap->setPvValidationProvisoire($pv);
        $ap->transitionTo(StatutAttribution::VALIDATION_PROVISOIRE);


        $this->em->flush();
        $this->attribMailer->notifyStatusChange($ap, 'VALIDATION_PROVISOIRE', ['pv' => $pv]);
        return $this->ok($ap->toArray());
    }

    #[Route('/{id}/attribuer-provisoire', name: 'attribuer_provisoire', methods: ['PATCH'])]
    public function attribuerProvisoire(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Attribution introuvable', 404);

        $body = json_decode($req->getContent(), true) ?? [];
        $pv = trim((string) ($body['pv'] ?? ''));
        if ($pv === '')
            return $this->error('PV requis', 422);

        $ap->setPvAttributionProvisoire($pv);
        $ap->transitionTo(StatutAttribution::ATTRIBUTION_PROVISOIRE);


        $this->em->flush();
        $this->attribMailer->notifyStatusChange($ap, 'ATTRIBUTION_PROVISOIRE', ['pv' => $pv]);
        return $this->ok($ap->toArray());
    }

    #[Route('/{id}/approuver-prefet', name: 'approuver_prefet', methods: ['PATCH'])]
    public function approuverPrefet(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Attribution introuvable', 404);

        $body = json_decode($req->getContent(), true) ?? [];
        $pv = trim((string) ($body['pv'] ?? ''));
        if ($pv === '')
            return $this->error('PV requis', 422);

        $ap->setPvApprobationPrefet($pv);
        $ap->transitionTo(StatutAttribution::APPROBATION_PREFET);

        $this->em->flush();
        $this->attribMailer->notifyStatusChange($ap, 'APPROBATION_PREFET', ['pv' => $pv]);
        return $this->ok($ap->toArray());
    }

    #[Route('/{id}/approuver-conseil', name: 'approuver_conseil', methods: ['PATCH'])]
    public function approuverConseil(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Attribution introuvable', 404);

        $data = json_decode($req->getContent(), true) ?? [];
        $pv = $data['pv'] ?? null;
        $dec = $data['decisionConseil'] ?? null;
        $dateS = $data['date'] ?? null;
        $date = $dateS ? new \DateTime($dateS) : null;

        $body = json_decode($req->getContent(), true) ?? [];
        $decision = trim((string) ($body['decisionConseil'] ?? ''));
        $pv = trim((string) ($body['pv'] ?? ''));
        $date = $body['date'] ?? null;

        if ($decision === '' || $pv === '') {
            return $this->error('Décision du conseil et PV requis', 422);
        }

        $ap->setDecisionConseil($decision);
        $ap->setPvApprobationConseil($pv);
        if ($date) {
            try {
                $ap->setDateApprobationConseil(new \DateTime($date));
            } catch (\Throwable) {
            }
        }

        $ap->transitionTo(StatutAttribution::APPROBATION_CONSEIL);

        $this->em->flush();
        $this->attribMailer->notifyStatusChange($ap, 'APPROBATION_CONSEIL', [
            'pv' => $pv,
            'decisionConseil' => $dec,
            'date' => $date
        ]);
        return $this->ok($ap->toArray());
    }

    #[Route('/{id}/attribuer-definitive', name: 'attribuer_definitive', methods: ['PATCH'])]
    public function attribuerDefinitive(
        int $id,
        Request $req,
        NotificationAttributionGenerator $generator,
    ): JsonResponse {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Attribution introuvable', 404);

        $data = json_decode($req->getContent(), true) ?? [];
        $dateEffet = !empty($data['dateEffet']) ? new \DateTime($data['dateEffet']) : null;


        $body = json_decode($req->getContent(), true) ?? [];
        $dateEffet = $body['dateEffet'] ?? null;
        if ($dateEffet) {
            try {
                $ap->setDateEffet(new \DateTime($dateEffet));
            } catch (\Throwable) {
            }
        }

        $ap->transitionTo(StatutAttribution::ATTRIBUTION_DEFINITIVE);

        $this->attribMailer->notifyStatusChange($ap, 'ATTRIBUTION_DEFINITIVE', [
            'dateEffet' => $dateEffet
        ]);

        $demande = $ap->getDemande();
        $user = $ap->getDemande()->getUtilisateur();
        $informationDemandeur = null;
        if (!$user) {
            $informationDemandeur = $ap->getDemande()->getInformationDemandeur();
        }
        $parcelle = $ap->getParcelle();
        $lot = $parcelle?->getLotissement();
        $demandeIdForPaths = $demande?->getId() ?? $ap->getId();

        $numero = $ap->getNumero();
        $type_attributtion = $ap->getDemande()->getTypeDemande();

        $paths = $generator->generate([
            'demandeId' => $demandeIdForPaths,
            'demandeCode' => method_exists($demande, 'getCode') ? $demande->getCode() : $demandeIdForPaths,
            'demandeurNomComplet' => $user ? trim($user->getPrenom() . ' ' . $user->getNom()) : '',
            'nomPrenoms' => $user ? trim(($user->getPrenom() . ' ' . $user->getNom())) : trim(($informationDemandeur['prenom'] . ' ' . $informationDemandeur['nom'])),
            'demandeurAdresse' => $user?->getAdresse() ?? '',
            'demandeurCni' => $user?->getNumeroElecteur() ?? '',
            'lotNumero' => $parcelle?->getNumero() ?? '',
            'lotissementNom' => $lot?->getNom() ?? '',
            'tf' => $lot?->getNom() ?? '',
            'ville' => 'Kaolack',
            'dateCommission' => (new \DateTime())->format('d/m/Y'),
            'delaiJours' => '30',
            'dateDocument' => (new \DateTime())->format('d/m/Y'),
            'maireNom' => 'Le Maire',
            'mairieNom' => 'Commune de Kaolack',
            'typeAttribution' => $type_attributtion, // ou 'provisoire' selon le cas
        ]);



        $bulletin = $generator->generateLiquidation([
            'demandeId' => $demande?->getId() ?? $ap->getId(),
            'demandeCode' => method_exists($demande, 'getCode') ? $demande->getCode() : ($demande?->getId() ?? $ap->getId()),
            // En-tête
            'commune' => 'KAOLACK',
            'bulletinNum' => $numero,
            'codeService' => 'CKSRAD',
            'bulletinRegistre' => "Numero registre" ?? '', // si tu as un champ, sinon remove
            // Corps
            'nomPrenoms' => $user ? trim(($user->getPrenom() . ' ' . $user->getNom())) : trim(($informationDemandeur['prenom'] . ' ' . $informationDemandeur['nom'])),
            'lotissement' => $ap->getParcelle()?->getLotissement()?->getNom() ?? '',
            'parcelleNum' => $ap->getParcelle()?->getNumero() ?? '',
            'montant' => number_format((float) ($ap->getMontant() ?? 0), 0, ',', ' '),
            // Pied
            'montantLettres' => $ap->getMontant() ?? '', // si tu as un service, sinon renseigne côté code
            'ville' => 'Kaolack',
            'dateDocument' => (new \DateTime())->format('d/m/Y'),
        ]);

        // Optionnel : stocker l’URL publique sur l’attribution
        if (method_exists($ap, 'setBulletinLiquidationUrl')) {
            $ap->setBulletinLiquidationUrl($bulletin['publicPdf']);
        }

        if (method_exists($ap, 'setPdfNotificationUrl')) {
            $ap->setPdfNotificationUrl($paths['publicPdf']);
        }

        $this->em->flush();


        return $this->ok($ap->toArray());
    }




    #[Route('/{id}/rejeter', name: 'rejeter', methods: ['PATCH'])]
    public function rejeter(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Introuvable', 404);
        $d = json_decode($req->getContent(), true) ?? [];
        $motif = (string) ($d['motif'] ?? '');
        $this->svc->rejeter($ap, $motif);
        return $this->ok($this->serializeItem($ap));
    }

    #[Route('/{id}/annuler', name: 'annuler', methods: ['PATCH'])]
    public function annuler(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Introuvable', 404);
        $d = json_decode($req->getContent(), true) ?? [];
        $motif = (string) ($d['motif'] ?? '');
        $this->svc->annuler($ap, $motif);
        return $this->ok($this->serializeItem($ap));
    }

    /* ---------------- Demande (exemple minimal) ---------------- */
    private function serializeItemDemande(Demande $d): array
    {
        if (method_exists($d, 'toArray')) {
            $arr = $d->toArray();

            // Normalisation des URLs fichiers
            foreach (['recto', 'verso'] as $k) {
                if (!empty($arr[$k]) && !preg_match('#^https?://#i', (string) $arr[$k])) {
                    $v = (string) $arr[$k];
                    if ($v !== '' && $v[0] !== '/') {
                        $v = '/' . ltrim($v, '/');
                    }
                    if ($v !== '' && !str_starts_with($v, '/documents/')) {
                        $v = '/documents' . $v;
                    }
                    $arr[$k] = rtrim($this->fileBaseUrl, '/') . $v;
                }
            }

            // localite = STRING (champ texte)
            $arr['localite'] = $d->getLocalite();

            // quartier = OBJET (relation)
            $quartier = $d->getQuartier();
            $arr['quartier'] = $quartier ? [
                'id' => $quartier->getId(),
                'nom' => method_exists($quartier, 'getNom') ? $quartier->getNom() : null,
                'prix' => method_exists($quartier, 'getPrix') ? $quartier->getPrix() : null,
                'longitude' => method_exists($quartier, 'getLongitude') ? $quartier->getLongitude() : null,
                'latitude' => method_exists($quartier, 'getLatitude') ? $quartier->getLatitude() : null,
                'description' => method_exists($quartier, 'getDescription') ? $quartier->getDescription() : null,
            ] : null;

            $user = null;
            if ($d->getUtilisateur() !== null) {
                $user = $d->getUtilisateur();
                $arr['utilisateur'] = $this->serializeItemProprietaire($user);
            }
            // (Optionnel) enveloppe "demandeur" si toArray ne le fait pas
            if (!isset($arr['demandeur'])) {
                $arr['demandeur'] = [
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
                    'isHabitant' => $this->fonctionsService->checkNumeroElecteurExist($d->getNumeroElecteur()),
                ];
            }

            return $arr;
        }


        return [
            'id' => $d->getId(),
            'numero' => $d->getNumero(),
            'typeDemande' => $d->getTypeDemande(),
            'typeDocument' => $d->getTypeDocument(),
            'typeTitre' => $d->getTypeTitre(),
            'superficie' => $d->getSuperficie(),
            'usagePrevu' => $d->getUsagePrevu(),
            'possedeAutreTerrain' => $d->isPossedeAutreTerrain(),
            'statut' => $d->getStatut(),
            'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $d->getDateModification()?->format('Y-m-d H:i:s'),
            'motif_refus' => $d->getMotifRefus(),
            'recto' => $d->getRecto(),
            'verso' => $d->getVerso(),
            'terrainAKaolack' => $d->isTerrainAKaolack(),
            'terrainAilleurs' => $d->isTerrainAilleurs(),
            'decisionCommission' => $d->getDecisionCommission(),
            'rapport' => $d->getRapport(),
            'localite' => $d->getLocalite(), // <- déjà présent ici
            'recommandation' => $d->getRecommandation(),
            'demandeur' => [
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
                'isHabitant' => $this->fonctionsService->checkNumeroElecteurExist($d->getNumeroElecteur()),
            ],
            'quartier' => $d->getQuartier() ? [
                'id' => $d->getQuartier()->getId(),
                'nom' => $d->getQuartier()->getNom(),
                'prix' => $d->getQuartier()->getPrix(),
                'longitude' => $d->getQuartier()->getLongitude(),
                'latitude' => $d->getQuartier()->getLatitude(),
                'description' => $d->getQuartier()->getDescription(),
            ] : null,
        ];
    }


    #[Route('/{id}/reouvrir', name: 'reopen', methods: ['PATCH'])]
    public function reopen(int $id, Request $req): JsonResponse
    {
        $ap = $this->attribRepo->find($id);
        if (!$ap)
            return $this->error('Attribution introuvable', 404);

        $payload = json_decode($req->getContent(), true) ?? [];

        try {
            $ap->reopenProcess([
                'to' => $payload['to'] ?? 'VALIDATION_PROVISOIRE',
                'resetDates' => (bool) ($payload['resetDates'] ?? true),
                'resetPVs' => (bool) ($payload['resetPVs'] ?? false),
                'resetDecision' => (bool) ($payload['resetDecision'] ?? false),
            ]);

            $ap->setBulletinLiquidationUrl(null);
            $ap->setPdfNotificationUrl(null);

            $this->em->flush();
            $this->attribMailer->notifyStatusChange($ap, 'REOUVERTURE');
            return $this->ok($this->serializeItem($ap));
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

}
