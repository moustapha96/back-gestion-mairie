<?php


namespace App\Controller;

use App\Entity\Lotissement;
use App\Entity\Parcelle;
use App\Entity\Parcelles;
use App\Entity\User;
use App\Repository\LocaliteRepository;
use App\Repository\LotissementRepository;
use App\Repository\ParcelleissementRepository;
use App\Repository\ParcelleRepository;
use App\Repository\ParcellesRepository;
use App\Repository\RequestRepository;
use App\services\FonctionsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ParcelleController extends AbstractController
{


    private $em;
    public function __construct(
        EntityManagerInterface $em,
        private FonctionsService $fonctionsService,
    ) {
        $this->em = $em;
    }


    // #[Route('/api/parcelle/disponibles/{idDemande}', name: 'api_parcelle_disponible_simple', methods: ['GET'])]
    // public function parcellesDisponibles(
    //     int $idDemande,
    //     RequestRepository $requestRepository,
    //     EntityManagerInterface $em
    // ): Response {
    //     // 1) Demande
    //     $demande = $requestRepository->find($idDemande);
    //     if (!$demande) {
    //         return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
    //     }

    //     // 2) Localité depuis la demande (selon ton modèle: getQuartier() ou getLocalite())
    //     $localite = null;
    //     if (method_exists($demande, 'getQuartier')) {
    //         $localite = $demande->getQuartier();
    //     } elseif (method_exists($demande, 'getLocalite')) {
    //         $localite = $demande->getLocalite();
    //     }
    //     if (!$localite) {
    //         return $this->json([], Response::HTTP_OK);
    //     }


    //     $qb = $em->getRepository(Parcelle::class)->createQueryBuilder('p')
    //         ->innerJoin('p.lotissement', 'l')
    //         ->innerJoin('l.localite', 'loc')
    //         ->addSelect('l', 'loc')
    //         ->where('p.proprietaire IS NULL')
    //         ->andWhere('loc = :loc')
    //         ->setParameter('loc', $localite)
    //         ->orderBy('l.id', 'DESC')
    //         ->addOrderBy('p.id', 'DESC');

    //     $parcelles = $qb->getQuery()->getResult();

    //     // 4) Mapping JSON simple (on renvoie tout, pas de pagination)
    //     $resultats = array_map(function (Parcelle $p) {
    //         $l = $p->getLotissement();
    //         $loc = $l?->getLocalite();

    //         return [
    //             'id' => $p->getId(),
    //             'numero' => $p->getNumero(),
    //             'surface' => $p->getSurface(),
    //             'statut' => $p->getStatut(),
    //             'longitude' => $p->getLongitude() ?? 0,
    //             'latitude' => $p->getLatitude() ?? 0,
    //             'typeParcelle' => $p->getTypeParcelle(),
    //             'lotissement' => $l ? [
    //                 'id' => $l->getId(),
    //                 'nom' => $l->getNom(),
    //                 'localisation' => $l->getLocalisation(),
    //                 'description' => $l->getDescription(),
    //                 'statut' => $l->getStatut(),
    //                 'dateCreation' => $l->getDateCreation()?->format('Y-m-d'),
    //                 'longitude' => $l->getLongitude(),
    //                 'latitude' => $l->getLatitude(),
    //                 'localite' => $loc ? [
    //                     'id' => $loc->getId(),
    //                     'nom' => $loc->getNom(),
    //                     'prix' => $loc->getPrix(),
    //                     'latitude' => $loc->getLatitude(),
    //                     'longitude' => $loc->getLongitude(),
    //                 ] : null,
    //             ] : null,
    //         ];
    //     }, $parcelles);

    //     return $this->json($resultats, Response::HTTP_OK);
    // }
    #[Route('/api/parcelle/disponibles/{idDemande}', name: 'api_parcelle_disponible_simple', methods: ['GET'])]
    public function parcellesDisponibles(
        int $idDemande,
        RequestRepository $requestRepository,
        EntityManagerInterface $em
    ): Response {
        // 1) Récupération de la demande
        $demande = $requestRepository->find($idDemande);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // 2) Récupération de la localité associée à la demande
        $localite = $demande->getQuartier() ?? $demande->getLocalite();
        if (!$localite) {
            return $this->json([], Response::HTTP_OK);
        }

        // 3) Requête pour récupérer les parcelles disponibles
        $parcelles = $em->createQueryBuilder()
            ->select('p', 'l', 'loc')
            ->from(Parcelle::class, 'p')
            ->innerJoin('p.lotissement', 'l')
            ->innerJoin('l.localite', 'loc')
            ->where('p.proprietaire IS NULL')
            ->andWhere('loc = :localite')
            ->setParameter('localite', $localite)
            ->orderBy('l.id', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        // 4) Mapping des résultats en JSON
        $resultats = array_map(function (Parcelle $parcelle) {
            $lotissement = $parcelle->getLotissement();
            $localite = $lotissement?->getLocalite();

            return [
                'id' => $parcelle->getId(),
                'numero' => $parcelle->getNumero(),
                'surface' => $parcelle->getSurface(),
                'statut' => $parcelle->getStatut(),
                'longitude' => $parcelle->getLongitude() ?? 0,
                'latitude' => $parcelle->getLatitude() ?? 0,
                'typeParcelle' => $parcelle->getTypeParcelle(),
                'lotissement' => $lotissement ? [
                    'id' => $lotissement->getId(),
                    'nom' => $lotissement->getNom(),
                    'localisation' => $lotissement->getLocalisation(),
                    'description' => $lotissement->getDescription(),
                    'statut' => $lotissement->getStatut(),
                    'dateCreation' => $lotissement->getDateCreation()?->format('Y-m-d'),
                    'longitude' => $lotissement->getLongitude(),
                    'latitude' => $lotissement->getLatitude(),
                    'localite' => $localite ? [
                        'id' => $localite->getId(),
                        'nom' => $localite->getNom(),
                        'prix' => $localite->getPrix(),
                        'latitude' => $localite->getLatitude(),
                        'longitude' => $localite->getLongitude(),
                    ] : null,
                ] : null,
            ];
        }, $parcelles);

        return $this->json($resultats, Response::HTTP_OK);
    }


    #[Route('/api/parcelle/liste', name: 'api_Parcelle_liste', methods: ['GET'])]
    public function listeParcelle(ParcelleRepository $parcellesRepository): Response
    {
        $parcelles = $parcellesRepository->findBy([], ['id' => 'DESC']);
        $resultats = [];

        foreach ($parcelles as $parcelle) {
            $resultats[] = [
                'id' => $parcelle->getId(),
                'numero' => $parcelle->getNumero(),
                'superface' => $parcelle->getSurface(),
                'statut' => $parcelle->getStatut(),
                'longitude' => $parcelle->getLongitude() ?? 0,
                'latitude' => $parcelle->getLatitude() ?? 0,
                'lotissement' => [
                    'id' => $parcelle->getLotissement()->getId(),
                    'nom' => $parcelle->getLotissement()->getNom(),
                    'localisation' => $parcelle->getLotissement()->getLocalisation(),
                    'description' => $parcelle->getLotissement()->getDescription(),
                    'statut' => $parcelle->getLotissement()->getStatut(),
                    'dateCreation' => $parcelle->getLotissement()->getDateCreation()->format('Y-m-d'),
                    'longitude' => $parcelle->getLotissement()->getLongitude(),
                    'latitude' => $parcelle->getLotissement()->getLatitude(),
                ]
            ];
        }
        return $this->json($resultats, 200);
    }


    #[Route('/api/parcelle/{id}/details', name: 'api_parcelle_detail', methods: ['GET'])]
    public function detailParcelle($id, ParcelleRepository $parcelleRepository): Response
    {
        $Parcelle = $parcelleRepository->find($id);
        if (!$Parcelle) {
            return $this->json('Parcelle introuvable', 404);
        }
        $resultats = [
            'id' => $Parcelle->getId(),
            'numero' => $Parcelle->getNumero(),
            'superficie' => $Parcelle->getSurface(),
            // 'statut' => $Parcelle->getStatut(),
            'longitude' => $Parcelle->getLongitude(),
            'latitude' => $Parcelle->getLatitude(),
            'lotissement' => [
                'id' => $Parcelle->getLotissement()->getId(),
                'nom' => $Parcelle->getLotissement()->getNom(),
                'localisation' => $Parcelle->getLotissement()->getLocalisation(),
                'description' => $Parcelle->getLotissement()->getDescription(),
                'statut' => $Parcelle->getLotissement()->getStatut(),
                'dateCreation' => $Parcelle->getLotissement()->getDateCreation()->format('Y-m-d'),
                'longitude' => $Parcelle->getLotissement()->getLongitude(),
                'latitude' => $Parcelle->getLotissement()->getLatitude(),
            ]
        ];
        return $this->json($resultats, 200);
    }


    #[Route('/api/parcelle/create', name: 'api_parcelle_create', methods: ['POST'])]
    public function createParcelle(
        Request $request,
        LotissementRepository $lotissementRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['numero']) || !isset($data['statut'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $lotissement = $lotissementRepository->find($data['lotissementId']);
        if (!$lotissement) {
            return $this->json(['error' => 'lotissement not found'], Response::HTTP_NOT_FOUND);
        }

        // Création de la Parcelle
        $parcelle = new Parcelle();
        $parcelle->setNumero($data['numero'] ?? null);
        $parcelle->setSurface($data['surface'] ?? null);
        $parcelle->setStatut($data['statut'] ?? null);
        $parcelle->setLotissement($lotissement);

        // Ajout des coordonnées géographiques
        $parcelle->setLatitude($data['latitude'] ?? null);
        $parcelle->setLongitude($data['longitude'] ?? null);

        $this->em->persist($parcelle);
        $this->em->flush();
        return $this->json($parcelle, Response::HTTP_CREATED);
    }

    #[Route('/api/parcelle/{id}/update', name: 'api_parcelle_update', methods: ['PUT'])]
    public function updateParcelle(
        int $id,
        Request $request,
        LotissementRepository $lotissementRepository,
        ParcelleRepository $parcelleRepository
    ): Response {
        $parcelle = $parcelleRepository->find($id);

        if (!$parcelle) {
            return $this->json(['error' => 'Parcelle not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);


        if (isset($data['lotissementId'])) {
            $lotissement = $lotissementRepository->find($data['lotissementId']);
            if (!$lotissement) {
                return $this->json(['error' => 'lotissement not found'], Response::HTTP_NOT_FOUND);
            }
            $parcelle->setLotissement($lotissement);
        }

        $parcelle->setNumero($data['numero'] ?? null);
        $parcelle->setSurface($data['surface'] ?? 0);
        $parcelle->setLatitude($data['latitude'] != null ? floatval($data['latitude']) : null);
        $parcelle->setLongitude($data['longitude'] != null ? floatval($data['longitude']) : null);
        $parcelle->setStatut($data['statut'] ?? null);


        $this->em->persist($parcelle);
        $this->em->flush();

        return $this->json($parcelle, Response::HTTP_OK);
    }

    #[Route('/api/parcelle/{id}/update-statut', name: 'api_Parcelle_update_statut', methods: ['PUT'])]
    public function updateParcelleStatut(int $id, Request $requet, ParcelleRepository $parcellesRepository): Response
    {
        $Parcelle = $parcellesRepository->find($id);
        if (!$Parcelle) {
            return $this->json("Parcelle non trouvée", Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($requet->getContent(), true);

        $statut = $data['statut'];
        $Parcelle->setStatut($statut);
        $this->em->persist($Parcelle);
        $this->em->flush();

        return $this->json("Statut mis à jour", Response::HTTP_OK);
    }


    #[Route('/api/parcelle/lotissement/{id}/liste', name: 'api_Parcelle_liste_lotissement', methods: ['GET'])]
    public function listeParcelleByLotissement(int $id, ParcelleRepository $parcellesRepository): Response
    {
        $lotissement = $this->em->getRepository(Lotissement::class)->find($id);

        if (!$lotissement) {
            return $this->json(['error' => 'lotissement not found'], Response::HTTP_NOT_FOUND);
        }

        $parcelles = $parcellesRepository->findBy(['lotissement' => $lotissement]);
        $resultats = [];

        foreach ($parcelles as $parcelle) {
            $resultats[] = [
                'id' => $parcelle->getId(),
                'numero' => $parcelle->getNumero(),
                'superface' => $parcelle->getSurface(),
                'statut' => $parcelle->getStatut(),
                'longitude' => $parcelle->getLongitude(),
                'latitude' => $parcelle->getLatitude(),
                'lotissement' => [
                    'id' => $parcelle->getLotissement()->getId(),
                    'nom' => $parcelle->getLotissement()->getNom(),
                    'localisation' => $parcelle->getLotissement()->getLocalisation(),
                    'description' => $parcelle->getLotissement()->getDescription(),
                    'statut' => $parcelle->getLotissement()->getStatut(),
                    'dateCreation' => $parcelle->getLotissement()->getDateCreation()->format('Y-m-d'),
                    'longitude' => $parcelle->getLotissement()->getLongitude(),
                    'latitude' => $parcelle->getLotissement()->getLatitude(),
                ]
            ];
        }
        return $this->json($resultats, 200);
    }



    #[Route('/api/users/{id}/parcelles', name: 'user_parcelles_paginated', methods: ['GET'])]
    public function listByUser(
        int $id,
        Request $request,
        ParcelleRepository $repo,
        NormalizerInterface $normalizer
    ): Response {
        $page = (int) $request->query->get('page', 1);
        $size = (int) $request->query->get('size', 10);
        $sort = $request->query->get('sort', 'id,DESC');
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $typeParcelle = $request->query->get('typeParcelle');
        $lotissementId = $request->query->get('lotissementId');

        $result = $repo->findPaginatedByUser(
            userId: $id,
            page: $page,
            size: $size,
            search: $search ?: null,
            statut: $statut ?: null,
            lotissementId: $lotissementId ? (int) $lotissementId : null,
            typeParcelle: $typeParcelle ?: null,
            sort: $sort ?: null,
        );

        // Normaliser avec les groups définis sur l’entité
        $data = $normalizer->normalize($result['data'], null, ['groups' => ['parcelle:list', 'parcelle:item']]);

        return $this->json([
            'data' => $data,
            'meta' => $result['meta'],
        ]);
    }



    #[Route('/api/parcelles-paginated', name: 'parcelles_paginated', methods: ['GET'])]
    public function listPaginated(
        Request $request,
        ParcelleRepository $repo,
        NormalizerInterface $normalizer
    ): Response {
        $page = (int) $request->query->get('page', 1);
        $size = (int) $request->query->get('size', 10);
        $sort = $request->query->get('sort', 'id,DESC');
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $typeParcelle = $request->query->get('typeParcelle');
        $lotissementId = $request->query->get('lotissementId');

        $result = $repo->findPaginated(
            page: $page,
            size: $size,
            search: $search ?: null,
            statut: $statut ?: null,
            lotissementId: $lotissementId ? (int) $lotissementId : null,
            typeParcelle: $typeParcelle ?: null,
            sort: $sort ?: null,
        );

        // Normalise avec les Groups définis sur l'entité
        $data = $normalizer->normalize($result['data'], null, [
            'groups' => ['parcelle:list', 'parcelle:item', 'lotissement:list', 'lotissement:item']
        ]);

        return $this->json([
            'data' => $data,
            'meta' => $result['meta'],
        ]);
    }

    #[Route('/api/parcelles/{id}/statut', name: 'parcelle_update_statut', methods: ['PATCH'])]
    public function updateStatut(
        int $id,
        Request $request,
        ParcelleRepository $repo,
        NormalizerInterface $normalizer
    ): Response {

        $parcelle = $repo->find($id);
        if (!$parcelle) {
            return $this->json(['message' => 'Parcelle non trouvée'], 404);
        }

        $payload = json_decode($request->getContent(), true) ?: [];
        $statut = $payload['statut'] ?? null;
        if (!$statut) {
            return $this->json(['message' => 'Statut manquant'], 400);
        }

        $parcelle->setStatut($statut);
        $this->em->flush();

        $data = $normalizer->normalize($parcelle, null, ['groups' => ['parcelle:item', 'parcelle:list']]);
        return $this->json($data);
    }

    #[Route('/api/parcelle/{id}/delete', name: 'api_delete_parcelle', methods: ['DELETE'])]
    public function deleteParcelle(int $id): Response
    {
        $parcelle = $this->em->getRepository(Parcelle::class)->find($id);
        if (!$parcelle) {
            return $this->json(['error' => 'Parcelle not found'], Response::HTTP_NOT_FOUND);
        }

        if ($parcelle->getProprietaire()) {
            return $this->json("Parcelle déjà attribuée", Response::HTTP_CONFLICT);
        }
        $this->em->remove($parcelle);
        $this->em->flush();
        return $this->json("Parcelle supprimée avec succès", Response::HTTP_OK);
    }

    // ----------------------------------------------------------------
    // (Optionnel) même logique mais pour un propriétaire par son ID
    // ----------------------------------------------------------------
    #[Route('/api/parcelle/proprietaire/{id}', name: 'by_owner', methods: ['GET'])]
    public function listByOwner(int $id, Request $req): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }
        return $this->listForUser($user, $req);
    }

    private function listForUser(User $user, Request $req): JsonResponse
    {
        // Pagination & tri
        $page = max(1, (int) $req->query->get('page', 1));
        $pageSize = min(200, max(1, (int) $req->query->get('pageSize', 10)));
        $sortField = (string) $req->query->get('sortField', 'id');
        $sortOrder = strtoupper((string) $req->query->get('sortOrder', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Filtres
        $q = $req->query->get('q');               // recherche rapide (numero / tfDe)
        $statut = $req->query->get('statut');          // DISPONIBLE / OCCUPE / etc.
        $lotissementId = $req->query->get('lotissementId');
        $localiteId = $req->query->get('localiteId');
        $typeParcelle = $req->query->get('typeParcelle');
        $surfaceMin = $req->query->get('surfaceMin');
        $surfaceMax = $req->query->get('surfaceMax');

        $qb = $this->em->getRepository(Parcelle::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.lotissement', 'l')->addSelect('l')
            ->leftJoin('l.localite', 'loc')->addSelect('loc')
            ->andWhere('p.proprietaire = :owner')
            ->setParameter('owner', $user);

        if (!empty($q)) {
            $qb->andWhere('(p.numero LIKE :q OR p.tfDe LIKE :q)')
                ->setParameter('q', '%' . $q . '%');
        }
        if (!empty($statut)) {
            $qb->andWhere('p.statut = :st')->setParameter('st', $statut);
        }
        if (!empty($lotissementId)) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', (int) $lotissementId);
        }
        if (!empty($localiteId)) {
            $qb->andWhere('loc.id = :locid')->setParameter('locid', (int) $localiteId);
        }
        if (!empty($typeParcelle)) {
            $qb->andWhere('p.typeParcelle = :tp')->setParameter('tp', $typeParcelle);
        }
        if ($surfaceMin !== null && $surfaceMin !== '') {
            $qb->andWhere('p.surface >= :smin')->setParameter('smin', (float) $surfaceMin);
        }
        if ($surfaceMax !== null && $surfaceMax !== '') {
            $qb->andWhere('p.surface <= :smax')->setParameter('smax', (float) $surfaceMax);
        }

        $allowedSort = ['id', 'numero', 'surface', 'statut'];
        $sf = in_array($sortField, $allowedSort, true) ? $sortField : 'id';
        $qb->orderBy('p.' . $sf, $sortOrder);

        // Total
        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        // Page
        $items = $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize)
            ->getQuery()
            ->getResult();

        $data = array_map(fn(Parcelle $p) => $this->serializeParcelle($p), $items);

        return $this->json([
            'success' => true,
            'items' => $data,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    private function serializeParcelle(Parcelle $p): array
    {
        $l = $p->getLotissement();
        $loc = $l?->getLocalite();

        return [
            'id' => $p->getId(),
            'numero' => $p->getNumero(),
            'surface' => $p->getSurface(),
            'statut' => $p->getStatut(),
            'latitude' => $p->getLatitude(),
            'longitude' => $p->getLongitude(),
            'typeParcelle' => $p->getTypeParcelle(),
            'tfDe' => $p->getTfDe(),
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
        ];
    }

}
