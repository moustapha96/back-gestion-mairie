<?php

namespace App\Controller;

use App\Entity\AttributionParcelle;
use App\Entity\Localite;
use App\Entity\Request as Demande;
use App\Entity\User;
use App\Repository\LocaliteRepository;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use App\services\AttributionMailer;
use App\services\FonctionsService;
use App\services\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/nouveau-demandes', name: 'api_nouveau_')]
class RequestController extends AbstractController
{
    public $localiteRepository;
    public $fonctionsService;

    public function __construct(
        private EntityManagerInterface $em,
        private string $fileBaseUrl,
        LocaliteRepository $localiteRepository,
        FonctionsService $fonctionsService,
        private UserRepository $userRepository,
        private MailService $mailService,
        private RequestRepository $repo,
        private UserRepository $userRepo,
        private AttributionMailer $attribMailer,

    ) {
        $this->localiteRepository = $localiteRepository;
        $this->fonctionsService = $fonctionsService;
        $this->fileBaseUrl = rtrim($this->fileBaseUrl ?: ($_ENV['APP_FILE_BASE_URL'] ?? ''), '/');
    }

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

    #[Route('', name: 'demandes_list', methods: ['GET'])]
    public function list(HttpRequest $req): JsonResponse
    {
        $page = max(1, (int) $req->query->get('page', 1));
        $pageSize = min(200, max(1, (int) $req->query->get('pageSize', 10)));
        $sortField = (string) $req->query->get('sortField', 'id');
        $sortOrder = strtoupper((string) $req->query->get('sortOrder', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Filtres
        $typeDemande = $req->query->get('typeDemande');
        $statut = $req->query->get('statut');

        // Relation quartier (id) : compat localiteId -> quartierId
        $quartierId = $req->query->get('quartierId');
        $localiteId = $req->query->get('localiteId');
        if ($quartierId === null && $localiteId !== null) {
            $quartierId = $localiteId;
        }

        // Champ texte "localite"
        $localiteTexte = $req->query->get('localite');

        $numeroElecteur = $req->query->get('numeroElecteur');
        $q = $req->query->get('q');
        $dateMin = $req->query->get('dateMin'); // YYYY-MM-DD
        $dateMax = $req->query->get('dateMax'); // YYYY-MM-DD

        $qb = $this->em->getRepository(Demande::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.quartier', 'q')->addSelect('q');

        if ($typeDemande) {
            $qb->andWhere('d.typeDemande = :td')->setParameter('td', $typeDemande);
        }
        if ($statut) {
            $qb->andWhere('d.statut = :st')->setParameter('st', $statut);
        }
        // Filtre relation quartier
        if ($quartierId) {
            $qb->andWhere('q.id = :qid')->setParameter('qid', (int) $quartierId);
        }
        // Filtre champ texte localite
        if ($localiteTexte) {
            $qb->andWhere('d.localite LIKE :loc')->setParameter('loc', '%' . $localiteTexte . '%');
        }
        if ($numeroElecteur) {
            $qb->andWhere('d.numeroElecteur = :ne')->setParameter('ne', $numeroElecteur);
        }
        if ($q) {
            $qb->andWhere('(
            d.nom LIKE :q OR d.prenom LIKE :q OR d.email LIKE :q OR d.telephone LIKE :q
            OR d.localite LIKE :q OR q.nom LIKE :q
        )')->setParameter('q', '%' . $q . '%');
        }
        if ($dateMin) {
            $qb->andWhere('d.dateCreation >= :dmin')->setParameter('dmin', new \DateTime($dateMin . ' 00:00:00'));
        }
        if ($dateMax) {
            $qb->andWhere('d.dateCreation <= :dmax')->setParameter('dmax', new \DateTime($dateMax . ' 23:59:59'));
        }

        // Tri
        $allowedSort = ['id', 'typeDemande', 'statut', 'dateCreation', 'superficie', 'localite', 'quartierNom'];
        $sf = in_array($sortField, $allowedSort, true) ? $sortField : 'id';

        if ($sf === 'quartierNom') {
            $qb->orderBy('q.nom', $sortOrder)->addOrderBy('d.id', 'DESC');
        } else {
            $qb->orderBy('d.' . $sf, $sortOrder)->addOrderBy('d.id', 'DESC');
        }

        // Total (COUNT propre)
        $qbCount = clone $qb;
        $qbCount->resetDQLPart('orderBy');
        $total = (int) $qbCount->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        // Page
        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();


        $serialized = array_map(fn(Demande $d) => $this->serializeItem($d), $items);

        return $this->json([
            'success' => true,
            'items' => $serialized,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    #[Route('', name: 'demandes_create', methods: ['POST'])]
    public function create(HttpRequest $req): JsonResponse
    {
        // multipart ou json : on unifie
        // $isJson = str_starts_with((string) $req->headers->get('Content-Type'), 'application/json');
        // $data = $isJson ? (json_decode($req->getContent(), true) ?? []) : $req->request->all();

        $contentType = (string) $req->headers->get('Content-Type');
        $isJson = str_starts_with($contentType, 'application/json');

        // Unifie la lecture
        $data = $isJson
            ? (json_decode($req->getContent(), true) ?? [])
            : $req->request->all();


        $demande = new Demande();

        // Hydrate champs demandeur
        $this->hydrateDemandeur($demande, $data);

        // Hydrate champs demande (métier)
        $this->hydrateDemande($demande, $data);

        // Localite (quartier)
        $this->attachLocalite($demande, $data['localiteId'] ?? null);

        $this->attachLocaliteWithName($demande, $data['localite']);

        // Fichiers (recto/verso)
        $uploadedRecto = $req->files->get('recto');
        $uploadedVerso = $req->files->get('verso');
        if ($uploadedRecto) {
            $demande->setRecto($this->storeUploadedFileToTfs($uploadedRecto, 'recto'));
        } elseif (!empty($data['recto'])) {
            $demande->setRecto($this->normalizeToCdnUrl((string) $data['recto']));
        }

        if ($uploadedVerso) {
            $demande->setVerso($this->storeUploadedFileToTfs($uploadedVerso, 'verso'));
        } elseif (!empty($data['verso'])) {
            $demande->setVerso($this->normalizeToCdnUrl((string) $data['verso']));
        }

        $this->em->persist($demande);
        $this->em->flush();

        $this->attribMailer->notifyDemandeCreation($demande);

        $userCreatedOrUpdated = null;
        try {
            $userCreatedOrUpdated = $this->createOrUpdateUserFromDemande($demande, $data);
        } catch (\Throwable $e) {
        }


        return $this->json([
            'success' => true,
            'item' => $this->serializeItem($demande),
            'user' => $userCreatedOrUpdated ? $this->serializeUserMinimal($userCreatedOrUpdated) : null,
        ], Response::HTTP_CREATED);

    }


    #[Route('/{id}/details', name: 'demandes_get_details', methods: ['GET'])]
    public function getOne($id, RequestRepository $demandeRepo): JsonResponse
    {
        $demande = $demandeRepo->findOneById($id);
        if ($demande === null) {
            return $this->json(['success' => false, 'message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $resultat = $this->serializeItem($demande);

        return $this->json(['success' => true, 'item' => $resultat]);
    }

    #[Route('/{id}', name: 'demandes_update', methods: ['PUT', 'PATCH'])]
    public function update(HttpRequest $req, Demande $demande): JsonResponse
    {
        $isJson = str_starts_with((string) $req->headers->get('Content-Type'), 'application/json');
        $data = $isJson ? (json_decode($req->getContent(), true) ?? []) : $req->request->all();

        // Demandeur
        $this->hydrateDemandeur($demande, $data, /*partial*/ true);

        // Demande (métier)
        $this->hydrateDemande($demande, $data, /*partial*/ true);

        // Localite (quartier)
        if (array_key_exists('localiteId', $data)) {
            $this->attachLocalite($demande, $data['localiteId']);
        }

        // Gestion fichiers
        $uploadedRecto = $req->files->get('recto');
        $uploadedVerso = $req->files->get('verso');

        if ($uploadedRecto) {
            $demande->setRecto($this->storeUploadedFileToTfs($uploadedRecto, 'recto'));
        } elseif (array_key_exists('recto', $data)) {
            $demande->setRecto($data['recto'] ? $this->normalizeToCdnUrl((string) $data['recto']) : null);
        }

        if ($uploadedVerso) {
            $demande->setVerso($this->storeUploadedFileToTfs($uploadedVerso, 'verso'));
        } elseif (array_key_exists('verso', $data)) {
            $demande->setVerso($data['verso'] ? $this->normalizeToCdnUrl((string) $data['verso']) : null);
        }


        $this->em->flush();
        return $this->json(['success' => true, 'item' => $this->serializeItem($demande)]);
    }

    #[Route('/{id}', name: 'demandes_delete', methods: ['DELETE'])]
    public function delete(Demande $demande): JsonResponse
    {
        $this->em->remove($demande);
        $this->em->flush();
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }

    /** ---------------- Helpers ---------------- */

    private function hydrateDemandeur(Demande $d, array $data, bool $partial = false): void
    {
        $set = function (string $key, callable $setter) use ($data, $partial) {
            if (!$partial || array_key_exists($key, $data)) {
                $setter($data[$key] ?? null);
            }
        };
        if (isset($data['prenom']))
            $set('prenom', fn($v) => $d->setPrenom($v ?: null));
        if (isset($data['nom']))
            $set('nom', fn($v) => $d->setNom($v ?: null));
        if (isset($data['email']))
            $set('email', fn($v) => $v ? $d->setEmail($v) : null);
        if (isset($data['telephone']))
            $set('telephone', fn($v) => $d->setTelephone($v ?: null));
        if (isset($data['adresse']))
            $set('adresse', fn($v) => $d->setAdresse($v ?: null));
        if (isset($data['profession']))
            $set('profession', fn($v) => $d->setProfession($v ?: null));
        if (isset($data['numeroElecteur']))
            $set('numeroElecteur', fn($v) => $d->setNumeroElecteur($v ?: null));
        if (isset($data['lieuNaissance']))
            $set('lieuNaissance', fn($v) => $d->setLieuNaissance($v ?: null));

        $set('dateNaissance', function ($v) use ($d) {
            if (!$v) {
                $d->setDateNaissance(null);
                return;
            }
            $d->setDateNaissance(\DateTime::createFromFormat('Y-m-d', substr((string) $v, 0, 10)) ?: null);
        });

        // Nouveaux champs utilisateur
        if (isset($data['situationMatrimoniale']))
            $set('situationMatrimoniale', fn($v) => $d->setSituationMatrimoniale($v ?: null));

        if (isset($data['statutLogement']))
            $set('statutLogement', fn($v) => $d->setStatutLogement($v ?: null));

        if (isset($data['nombreEnfant']))
            $set('nombreEnfant', fn($v) => $d->setNombreEnfant($v !== null && $v !== '' ? (int) $v : null));
    }

    private function hydrateDemande(Demande $d, array $data, bool $partial = false): void
    {
        $set = function (string $key, callable $setter) use ($data, $partial) {
            if (!$partial || array_key_exists($key, $data)) {
                $setter($data[$key] ?? null);
            }
        };

        if (isset($data['typeDemande']))
            $set('typeDemande', fn($v) => $v ? $d->setTypeDemande($v) : null);

        if (isset($data['typeDocument']))
            $set('typeDocument', fn($v) => $d->setTypeDocument($v ?: null));

        if (isset($data['typeTitre']))
            $set('typeTitre', fn($v) => $d->setTypeTitre($v ?: null));

        if (isset($data['usagePrevu']))
            $set('usagePrevu', fn($v) => $d->setUsagePrevu($v ?: null));

        if (isset($data['superficie']))
            $set('superficie', fn($v) => $d->setSuperficie($v !== null && $v !== '' ? (float) $v : null));
        if (isset($data['possedeAutreTerrain']))
            $set('possedeAutreTerrain', fn($v) => $d->setPossedeAutreTerrain($v !== null ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : null));
        if (isset($data['terrainAKaolack']))
            $set('terrainAKaolack', fn($v) => $d->setTerrainAKaolack($v !== null ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : null));
        if (isset($data['terrainAilleurs']))
            $set('terrainAilleurs', fn($v) => $d->setTerrainAilleurs($v !== null ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : null));

        // Administration / workflow
        if (isset($data['statut']))
            $set('statut', fn($v) => $d->setStatut($v ?: $d->getStatut()));

        if (isset($data['motif_refus']))
            $set('motif_refus', fn($v) => $d->setMotifRefus($v ?: null));
        if (isset($data['decisionCommission']))
            $set('decisionCommission', fn($v) => $d->setDecisionCommission($v ?: null));
        if (isset($data['rapport']))
            $set('rapport', fn($v) => $d->setRapport($v ?: null));
        if (isset($data['recommandation']))
            $set('recommandation', fn($v) => $d->setRecommandation($v ?: null));

    }

    private function attachLocalite(Demande $d, $localiteId): void
    {
        if (!$localiteId) {
            $d->setQuartier(null);
            return;
        }
        $loc = $this->em->getRepository(Localite::class)->find((int) $localiteId);
        $d->setQuartier($loc); // propriété ManyToOne vers Localite (appelée "quartier" dans l'entité)
    }

    private function attachLocaliteWithName(Demande $d, $localiteName): void
    {
        $d->setLocalite($localiteName);
    }

    /**
     * Enregistre un fichier dans /public/tfs et renvoie l’URL absolue CDN : {ASSET_BASE_URL}/tfs/{filename}
     */
    private function storeUploadedFileToTfs($uploadedFile, string $prefix = 'piece'): string
    {
        $rootDir = $this->getParameter('kernel.project_dir');
        $tfsDir = $rootDir . '/public/tfs';
        if (!is_dir($tfsDir)) {
            @mkdir($tfsDir, 0775, true);
        }

        $original = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');
        $slug = (new AsciiSlugger())->slug($original)->lower();
        $filename = sprintf('%s-%s-%s.%s', $prefix, $slug, bin2hex(random_bytes(6)), $ext);

        $uploadedFile->move($tfsDir, $filename);

        return rtrim($this->fileBaseUrl, '/') . '/tfs/' . $filename;
    }


    private function normalizeToCdnUrl(string $input): string
    {
        $u = trim($input);
        if ($u === '')
            return $u;

        $cdnPrefix = rtrim($this->fileBaseUrl, '/') . '/';

        if (str_starts_with($u, $cdnPrefix)) {
            return $u; // déjà CDN
        }

        if (preg_match('#^https?://#i', $u)) {
            $parts = parse_url($u);
            $path = $parts['path'] ?? '';
            if ($path && str_starts_with($path, '/tfs/')) {
                return rtrim($this->fileBaseUrl, '/') . $path;
            }
            return $u; // autre URL, on laisse tel quel
        }

        if ($u[0] !== '/')
            $u = '/' . ltrim($u, '/');
        if (!str_starts_with($u, '/tfs/'))
            $u = '/tfs' . $u;

        return rtrim($this->fileBaseUrl, '/') . $u;
    }


    #[Route('/electeur/{nin}', name: 'demandes_list_elector', methods: ['GET'])]
    public function listRequestElector(string $nin, HttpRequest $req): JsonResponse
    {
        if (!$nin) {
            return $this->json(['success' => false, 'message' => 'NIN manquant'], 400);
        }

        // 1) Récup électeur par NIN
        $electeur = $this->fonctionsService->fetchDataElecteur($nin);
        if (!$electeur) {
            return $this->json(['success' => false, 'message' => 'Electeur introuvable'], 404);
        }

        // 2) NumeroElecteur provenant DE l’électeur
        $numeroElecteur = isset($electeur['NIN']) ? trim((string) $electeur['NIN']) : null;
        if (!$numeroElecteur) {
            return $this->json(['success' => false, 'message' => 'Numéro électeur introuvable pour ce NIN'], 404);
        }

        // Pagination & tri
        $page = max(1, (int) $req->query->get('page', 1));
        $pageSize = min(200, max(1, (int) $req->query->get('pageSize', 10)));
        $sortField = $req->query->get('sortField', 'id');
        $sortOrder = strtoupper($req->query->get('sortOrder', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Filtres optionnels (on ne prend PAS numeroElecteur depuis la query : on force celui de l’électeur)
        $typeDemande = $req->query->get('typeDemande');
        $statut = $req->query->get('statut');
        $localiteId = $req->query->get('localiteId');
        $q = $req->query->get('q');          // recherche libre nom/prenom/tel/mail
        $dateMin = $req->query->get('dateMin'); // YYYY-MM-DD
        $dateMax = $req->query->get('dateMax'); // YYYY-MM-DD

        // 3) QueryBuilder
        $qb = $this->em->getRepository(Demande::class)->createQueryBuilder('d')
            ->leftJoin('d.quartier', 'l')->addSelect('l');

        // Filtre OBLIGATOIRE: mêmes demandes que l’électeur (par numéro d’électeur)
        $qb->andWhere('d.numeroElecteur = :ne')->setParameter('ne', $numeroElecteur);

        if ($typeDemande) {
            $qb->andWhere('d.typeDemande = :td')->setParameter('td', $typeDemande);
        }
        if ($statut) {
            $qb->andWhere('d.statut = :st')->setParameter('st', $statut);
        }
        if ($localiteId) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', (int) $localiteId);
        }
        if ($q) {
            $qb->andWhere('(d.nom LIKE :q OR d.prenom LIKE :q OR d.email LIKE :q OR d.telephone LIKE :q)')
                ->setParameter('q', '%' . $q . '%');
        }
        if ($dateMin) {
            $qb->andWhere('d.dateCreation >= :dmin')->setParameter('dmin', new \DateTime($dateMin . ' 00:00:00'));
        }
        if ($dateMax) {
            $qb->andWhere('d.dateCreation <= :dmax')->setParameter('dmax', new \DateTime($dateMax . ' 23:59:59'));
        }

        $allowedSort = ['id', 'typeDemande', 'statut', 'dateCreation', 'superficie'];
        $sf = in_array($sortField, $allowedSort, true) ? $sortField : 'id';
        $qb->orderBy('d.' . $sf, $sortOrder);

        // Total
        $total = (int) (clone $qb)->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        // Page
        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        $serialized = array_map(fn(Demande $d) => $this->serializeItem($d), $items);

        // 4) On renvoie aussi l’électeur pour l’affichage front
        return $this->json([
            'success' => true,
            'electeur' => $electeur,
            'items' => $serialized,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }


    private function serializeItem(Demande $d): array
    {
        $user = $d->getUtilisateur();
        $dataUser = $user ? $user->toArray() : null;

        // Prépare attribution (objet ou null)
        $attributionArr = $this->serializeAttributionForDemande(
            method_exists($d, 'getParcelleAttribuer') ? $d->getParcelleAttribuer() : null
        );

        if (method_exists($d, 'toArray')) {
            $arr = $d->toArray();

            // Normalisation des URLs fichiers
            foreach (['recto', 'verso'] as $k) {
                if (!empty($arr[$k]) && !preg_match('#^https?://#i', (string) $arr[$k])) {
                    $v = (string) $arr[$k];
                    if ($v !== '' && $v[0] !== '/')
                        $v = '/' . ltrim($v, '/');
                    if ($v !== '' && !str_starts_with($v, '/tfs/'))
                        $v = '/tfs' . $v;
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

            // Demandeur si manquant
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

            $arr['utilisateur'] = $dataUser;
            // >>> I C I : on ajoute systématiquement l’attribution (ou null)
            $arr['attribution'] = $attributionArr;

            return $arr;
        }

        // Si pas de toArray() sur Demande : construction manuelle
      
        return [
            'id' => $d->getId(),
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
            'localite' => $d->getLocalite(),
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
            'utilisateur' => $dataUser,
            // >>> I C I : on ajoute systématiquement l’attribution (ou null)
            'attribution' => $attributionArr,
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
        ];
    }
    private function serializeItemAttribution(AttributionParcelle $ap): array
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
    private function serializeItemDemande(Demande $d): array
    {
        $dataUser = null;
        if (method_exists($d, 'getUtilisateur') ) {
            $user = $this->userRepo->find($d->getUtilisateur()->getId());
            if ($user) {
                $dataUser = $user->toArray();
            }
        }
        $parcelleAttribuer = null;
        if (method_exists($d, 'getParcelleAttribuer')) {
            $parcelleAttribuer = $d->getParcelleAttribuer() ? $this->serializeItemAttribution($d->getParcelleAttribuer()) : null;
        }

        if (method_exists($d, 'toArray')) {
            $arr = $d->toArray();
            // Normalisation des URLs fichiers
            foreach (['recto', 'verso'] as $k) {
                if (!empty($arr[$k]) && !preg_match('#^https?://#i', (string) $arr[$k])) {
                    $v = (string) $arr[$k];
                    if ($v !== '' && $v[0] !== '/') {
                        $v = '/' . ltrim($v, '/');
                    }
                    if ($v !== '' && !str_starts_with($v, '/tfs/')) {
                        $v = '/tfs' . $v;
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
            $arr['utilisateur'] = $dataUser;
            $arr['parcelleAttribuer'] = $parcelleAttribuer;
            return $arr;
        }


        return [
            'id' => $d->getId(),
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
            'utilisateur' => $dataUser,
            'parcelleAttribuer' => $parcelleAttribuer
        ];
    }


    private function createOrUpdateUserFromDemande(Demande $demande, array $data): ?User
    {
        $email = trim((string) ($data['email'] ?? $demande->getEmail() ?? ''));
        if ($email === '') {
            // Sans email, on ne peut pas créer le compte proprement
            return null;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        $password = $data['password'] ?? 'Password123!';
        $numeroElecteur = $data['numeroElecteur'] ?? $demande->getNumeroElecteur();
        $dateNaissanceStr = $data['dateNaissance'] ?? ($demande->getDateNaissance()?->format('Y-m-d') ?: null);
        $dateNaissance = $dateNaissanceStr ? new \DateTime($dateNaissanceStr) : null;


        if (!$user && $numeroElecteur) {
            $existingByNE = $this->userRepository->findOneBy(['numeroElecteur' => $numeroElecteur]);
            if ($existingByNE && $existingByNE->getEmail() !== $email) {
                // quelqu’un a déjà ce numeroElecteur → on rattache la demande à ce user
                $existingByNE->adddemande_demandeur($demande);
                $this->userRepository->save($existingByNE, true);
                return $existingByNE;
            }
        }

        if (!$user) {
            // Nouveau user
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($email);
            $user->setPrenom($demande->getPrenom());
            $user->setNom($demande->getNom());
            $user->setTelephone($demande->getTelephone());
            $user->setAdresse($demande->getAdresse());
            $user->setProfession($demande->getProfession());
            $user->setNumeroElecteur($numeroElecteur ?: null);
            $user->setDateNaissance($dateNaissance);
            $user->setLieuNaissance($demande->getLieuNaissance());
            $user->setSituationMatrimoniale($demande->getSituationMatrimoniale());
            $user->setSituationDemandeur($demande->getStatutLogement());
            $user->setNombreEnfant($data['nombreEnfant'] ?? $demande->getNombreEnfant() ?? null);

            // activation / statut
            $user->setActiveted(false);
            $user->setEnabled(true);
            $user->setTokenActiveted(bin2hex(random_bytes(32)));

            // rôles
            $roles = 'ROLE_DEMANDEUR';
            $user->setRoles($roles);

            // mot de passe
            $pwd = $password ?: 'Password123!';
            $user->setPassword(password_hash($pwd, PASSWORD_BCRYPT));
            $user->adddemande_demandeur($demande);

            $this->userRepository->save($user, true);

            try {
                $this->mailService->sendAccountCreationMail($user, $pwd);
            } catch (\Throwable) {
            }
        } else {
            // Mise à jour minimale (optionnelle) : ne pas écraser les infos “fortes” si déjà renseignées
            if (!$user->getPrenom() && $demande->getPrenom())
                $user->setPrenom($demande->getPrenom());
            if (!$user->getNom() && $demande->getNom())
                $user->setNom($demande->getNom());
            if (!$user->getTelephone() && $demande->getTelephone())
                $user->setTelephone($demande->getTelephone());
            if (!$user->getAdresse() && $demande->getAdresse())
                $user->setAdresse($demande->getAdresse());
            if (!$user->getProfession() && $demande->getProfession())
                $user->setProfession($demande->getProfession());
            if (!$user->getNumeroElecteur() && $numeroElecteur)
                $user->setNumeroElecteur($numeroElecteur);
            if (!$user->getDateNaissance() && $dateNaissance)
                $user->setDateNaissance($dateNaissance);
            if (!$user->getLieuNaissance() && $demande->getLieuNaissance())
                $user->setLieuNaissance($demande->getLieuNaissance());
            if (!$user->getSituationDemandeur() && $demande->getStatutLogement())
                $user->setSituationDemandeur($demande->getStatutLogement());
            // nombre enfant
            if (!$user->getNombreEnfant() && $data['nombreEnfant'])
                $user->setNombreEnfant($data['nombreEnfant']);
            // situation matrimoniale
            if (!$user->getSituationMatrimoniale() && $demande->getSituationMatrimoniale())
                $user->setSituationMatrimoniale($demande->getSituationMatrimoniale());

            // ne pas modifier le mot de passe ni les rôles ici (sauf si tu le veux)
            $user->adddemande_demandeur($demande);

            $this->userRepository->save($user, true);
        }
        return $user;
    }


    private function serializeUserMinimal(User $u): array
    {
        return [
            'id' => $u->getId(),
            'email' => $u->getEmail(),
            'prenom' => $u->getPrenom(),
            'nom' => $u->getNom(),
            'roles' => $u->getRoles(),
        ];
    }


    #[Route('/demandeur/{id}', name: 'demandes_demandeur_liste', methods: ['GET'])]
    public function listeDemandeUser($id, HttpRequest $req): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if ($user === null) {
            return $this->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }

        // Pagination & tri
        $page = max(1, (int) $req->query->get('page', 1));
        $pageSize = min(200, max(1, (int) $req->query->get('pageSize', 10)));
        $sortField = $req->query->get('sortField', 'id');
        $sortOrder = strtoupper($req->query->get('sortOrder', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Filtres optionnels
        $typeDemande = $req->query->get('typeDemande');
        $statut = $req->query->get('statut');
        $localiteId = $req->query->get('localiteId');
        $q = $req->query->get('q');
        $dateMin = $req->query->get('dateMin'); // YYYY-MM-DD
        $dateMax = $req->query->get('dateMax'); // YYYY-MM-DD

        // QueryBuilder
        // $qb = $this->em->getRepository(Demande::class)
        //     ->createQueryBuilder('d')
        //     ->leftJoin('d.quartier', 'l')->addSelect('l');

        // QueryBuilder
        $qb = $this->em->getRepository(Demande::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.quartier', 'l')->addSelect('l')
            // Joindre l’attribution et la chaîne parcelle -> lotissement -> localité
            ->leftJoin('d.parcelleAttribuer', 'ap')->addSelect('ap')
            ->leftJoin('ap.parcelle', 'p')->addSelect('p')
            ->leftJoin('p.lotissement', 'lot')->addSelect('lot')
            ->leftJoin('lot.localite', 'loc')->addSelect('loc');

        $qb->andWhere('d.utilisateur = :user')->setParameter('user', $user);


        $qb->andWhere('d.utilisateur = :user')->setParameter('user', $user);
        // (Alternative : $qb->andWhere('IDENTITY(d.utilisateur) = :userId')->setParameter('userId', $user->getId());)

        if ($typeDemande) {
            $qb->andWhere('d.typeDemande = :td')->setParameter('td', $typeDemande);
        }
        if ($statut) {
            $qb->andWhere('d.statut = :st')->setParameter('st', $statut);
        }
        if ($localiteId) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', (int) $localiteId);
        }
        if ($q) {
            $qb->andWhere('(d.nom LIKE :q OR d.prenom LIKE :q OR d.email LIKE :q OR d.telephone LIKE :q)')
                ->setParameter('q', '%' . $q . '%');
        }
        if ($dateMin) {
            $qb->andWhere('d.dateCreation >= :dmin')
                ->setParameter('dmin', new \DateTime($dateMin . ' 00:00:00'));
        }
        if ($dateMax) {
            $qb->andWhere('d.dateCreation <= :dmax')
                ->setParameter('dmax', new \DateTime($dateMax . ' 23:59:59'));
        }

        $allowedSort = ['id', 'typeDemande', 'statut', 'dateCreation', 'superficie'];
        $sf = in_array($sortField, $allowedSort, true) ? $sortField : 'id';
        $qb->orderBy('d.' . $sf, $sortOrder);

        // Total
        $total = (int) (clone $qb)->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();

        // Page
        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        $serialized = array_map(fn(Demande $d) => $this->serializeItem($d), $items);

        return $this->json([
            'success' => true,
            'items' => $serialized,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }




    // methode pour attribuer une parcelle un demandeur
    #[Route('/requests/{id}/attribuer-parcelle', name: 'demandes_assign', methods: ['POST'])]
    public function assign(int $id, HttpRequest $req): JsonResponse
    {
        $demande = $this->em->getRepository(Demande::class)->find($id);
        if ($demande === null) {
            return $this->json(['success' => false, 'message' => 'Demande introuvable'], 404);
        }
        $data = json_decode($req->getContent(), true);
        $montant = $data['montant'] ?? null;
        $surface = $data['surface'] ?? null;
        $date = new \DateTime();


        $demande->setUtilisateur($this->getUser());
        $this->em->flush();
        return $this->json(['success' => true, 'message' => 'Demande attribuée']);
    }

    private function serializeAttributionForDemande(?\App\Entity\AttributionParcelle $ap): ?array
    {
        if (!$ap)
            return null;

        $p = $ap->getParcelle();
        $lot = $p?->getLotissement();
        $loc = $lot?->getLocalite();

        return [
            'id' => $ap->getId(),
            'dateEffet' => $ap->getDateEffet()?->format(DATE_ATOM),
            'dateFin' => $ap->getDateFin()?->format(DATE_ATOM),
            'montant' => $ap->getMontant(),
            'frequence' => $ap->getFrequence(),
            'etatPaiement' => $ap->isEtatPaiement(),
            'parcelle' => $p ? [
                'id' => $p->getId(),
                'numero' => $p->getNumero(),
                'surface' => $p->getSurface(),
                'statut' => $p->getStatut(),
                'latitude' => $p->getLatitude(),
                'longitude' => $p->getLongitude(),
                'lotissement' => $lot ? [
                    'id' => $lot->getId(),
                    'nom' => $lot->getNom(),
                    'localisation' => $lot->getLocalisation(),
                    'description' => $lot->getDescription(),
                    'statut' => $lot->getStatut(),
                    'dateCreation' => $lot->getDateCreation()?->format('Y-m-d'),
                    'latitude' => $lot->getLatitude(),
                    'longitude' => $lot->getLongitude(),
                    'localite' => $loc ? [
                        'id' => $loc->getId(),
                        'nom' => $loc->getNom(),
                        'prix' => $loc->getPrix(),
                        'latitude' => $loc->getLatitude(),
                        'longitude' => $loc->getLongitude(),
                    ] : null,
                ] : null,
            ] : null,
        ];
    }



    #[Route('/{id}/documents', name: 'documents', methods: ['GET'])]
    public function documents(int $id, RequestRepository $requestRepository): JsonResponse
    {
        $d = $requestRepository->find($id);
        if (!$d)
            return $this->json('Demande introuvable', 404);
        $recto = method_exists($d, 'getRecto') ? $d->getRecto() : null;
        $verso = method_exists($d, 'getVerso') ? $d->getVerso() : null;
        return $this->ok([
            'recto' => $recto,
            'verso' => $verso
        ]);
    }
    // nouveau-demandes/${id}/retirer-attribution
    #[Route('/{id}/retirer-attribution', name: 'retirer_attribution', methods: ['PUT'])]
    public function retirerAttribution($id, RequestRepository $requestRepository): JsonResponse
    {
        $demande = $requestRepository->find($id);
        if (!$demande) {
            return $this->json('Demande introuvable', 404);
        }
        $attribution = $demande->getParcelleAttribuer();
        if (!$attribution) {
            return $this->json('Attribution introuvable', 404);
        }

        $parcelle = $attribution->getParcelle();
        $parcelle->setStatut('DISPONIBLE');
        $parcelle->setProprietaire(null);
        $attribution->setParcelle(null);
        $demande->setParcelleAttribuer(null);

        $this->em->persist($demande);
        $this->em->persist($parcelle);
        $this->em->persist($attribution);
        $this->em->flush();

        $resultat = $this->serializeItem($demande);
        return $this->json(['success' => true, 'item' => $resultat, 'message' => 'Attribution retirée'], 200);

    }


    #[Route('/{id}/adjacent', name: '_get_adjacent_requests', methods: ['GET'])]
    public function getAdjacentRequests(int $id): JsonResponse
    {
        $qb = $this->em->getRepository(Demande::class)
            ->createQueryBuilder('d')
            ->where('d.id < :id OR d.id > :id')
            ->setParameter('id', $id)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($qb as $demande) {
            $result[] = $demande->getId();
        }
        return $this->json(['success' => true, 'items' => $result], 200);
    }


    #[Route('/create-demande-demandeur', name: 'create_demande_demandeur', methods: ['POST'])]
    public function createDemande(
        HttpRequest $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository
    ): Response {

        $userId = $request->request->get('userId');
        $superficie = $request->request->get('superficie') ?? null;
        $usagePrevu = $request->request->get('usagePrevu') ?? null;
        $localiteId = $request->request->get('localiteId');
        $typeDemande = $request->request->get('typeDemande') ?? null;
        $typeDocument = $request->request->get('typeDocument') ?? null;
        $possedeAutreTerrain = $request->request->get('possedeAutreTerrain') ?? null;
        $typeTitre = $request->request->get('typeTitre') ?? null;
        $terrainAilleurs = $request->request->get('terrainAilleurs') ?? null;
        $terrainAKaolack = $request->request->get('terrainAKaolack') ?? null;

        if (!$typeDemande || !$userId || !$localiteId) {
            return $this->json(['message' => 'Données manquantes ou invalides'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $userRepository->find($userId);
        $localite = $localiteRepository->find($localiteId);


        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if (!$localite) {
            return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
        }


        // Créer la demande de terrain
        $demande = new Demande();
        $demande
            ->setSuperficie($superficie ?? 0)
            ->setTypeDemande($typeDemande ?? null)
            ->setUsagePrevu($usagePrevu ?? null)
            ->setUtilisateur($utilisateur ?? null)
            ->setTypeDocument($typeDocument ?? null)
            ->setTypeTitre($typeTitre ?? null)
            ->setTerrainAKaolack($terrainAKaolack ?? null)
            ->setDateCreation(new \DateTime())
            ->setStatut(Demande::STATUT_EN_ATTENTE)
            ->setPossedeAutreTerrain($possedeAutreTerrain ?? null)
            ->setMotifRefus(null)
            ->setDecisionCommission(null)
            ->setRapport(null)
            ->setTerrainAilleurs($terrainAilleurs ?? null)
            ->setRecommandation(null)
            ->setRapport(null)
            ->setLocalite($localite->getNom() ?? null)
            ->setQuartier($localite);

        /** @var UploadedFile|null $recto  */
        /** @var UploadedFile|null $verso  */

        $recto = $request->files->get('recto');
        $verso = $request->files->get('verso');

        $uploadDir = $this->getParameter('app.upload.documents_dir'); // = .../public/documents

        if ($recto) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                'recto',
                $utilisateur ? str_replace(' ', '-', strtolower($utilisateur->getEmail())) : date('YmdHis'),
                $recto->guessExtension()
            );

            // Déplace le fichier sur le disque
            $recto->move($uploadDir, $newFilename);

            // 1) On stocke en base un chemin WEB relatif (recommandé)
            $webPath = $this->makePublicDocPath($newFilename);               // -> /documents/...
            $demande->setRecto($webPath);

            // 2) Si tu veux renvoyer une URL absolue dans la réponse :
            $rectoUrl = $this->makeAbsoluteUrl($request, $webPath);          // -> http(s)://host/documents/...
        }

        if ($verso) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                'verso',
                $utilisateur ? str_replace(' ', '-', strtolower($utilisateur->getEmail())) : date('YmdHis'),
                $verso->guessExtension()
            );

            $verso->move($uploadDir, $newFilename);

            $webPath = $this->makePublicDocPath($newFilename);
            $demande->setVerso($webPath);

            $versoUrl = $this->makeAbsoluteUrl($request, $webPath);
        }


        $this->em->persist($demande);
        $this->em->flush();


        // $this->mailService->sendDemandeMail($demande);
        $this->attribMailer->notifyDemandeCreation($demande);

        return $this->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->toArray(),
            'quartier' => $demande->getQuartier()->toArray(),
            'rectoUrl' => $rectoUrl,
            'versoUrl' => $versoUrl
        ], Response::HTTP_CREATED);
    }


    private function makePublicDocPath(string $filename): string
    {
        // on stocke un chemin web relatif => /documents/xxx.pdf
        return rtrim($this->getParameter('app.public.documents_prefix'), '/')
            . '/' . rawurlencode($filename);
    }

    private function makeAbsoluteUrl(HttpRequest $request, string $webPath): string
    {
        // pour renvoyer une URL absolue
        return rtrim($request->getSchemeAndHttpHost(), '/') . $webPath;
    }
}
