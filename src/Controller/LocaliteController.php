<?php

namespace App\Controller;

use App\Entity\Localite;
use App\Repository\LocaliteRepository;
use App\Repository\RequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class LocaliteController extends AbstractController
{


    #[Route('/api/localite/liste', name: 'api_localite_liste', methods: ['GET'])]
    public function listeLocalite(LocaliteRepository $localiteRepository): Response
    {
        $localites = $localiteRepository->findBy([], ['id' => 'DESC']);
        $resultats = [];

        foreach ($localites as $value) {
            $resultats[] = [
                'id' => $value->getId(),
                'nom' => $value->getNom(),
                'prix' => $value->getPrix(),
                'longitude' => $value->getLongitude(),
                'latitude' => $value->getLatitude(),
                'description' => $value->getDescription(),
                'lotissements' => $value->getLotissements(),
            ];
        }
        return $this->json($resultats, 200);
    }

    // /api/localite/liste-web
    #[Route('/api/localite/liste-web', name: 'api_localite_liste_web', methods: ['GET'])]
    public function listeLocaliteWeb(LocaliteRepository $localiteRepository): Response
    {
        $localites = $localiteRepository->findBy([], ['id' => 'DESC']);
        $resultats = [];

        foreach ($localites as $value) {
            $resultats[] = [
                'id' => $value->getId(),
                'nom' => $value->getNom(),
                'prix' => $value->getPrix(),
                'longitude' => $value->getLongitude(),
                'latitude' => $value->getLatitude(),
                'description' => $value->getDescription(),
                'lotissements' => $value->getLotissements(),
            ];
        }
        return $this->json(
            $resultats,
            200
        );
    }

    #[Route('/api/localite/{id}/details', name: 'api_localite_show', methods: ['GET'])]
    public function details(Localite $localite): Response
    {
        if (!$localite) return $this->json(['message' => 'Localité non trouvée'], 404);
        $resultat = [
            'id' => $localite->getId(),
            'nom' => $localite->getNom(),
            'prix' => $localite->getPrix(),
            'longitude' => $localite->getLongitude(),
            'latitude' => $localite->getLatitude(),
            'description' => $localite->getDescription(),
            'lotissements' => $localite->getLotissements(),
        ];
        return $this->json($resultat, 200);
    }

    #[Route('/api/localite/create', name: 'api_localite_create', methods: ['POST'])]
    public function createLocalite(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);
        $localite = new Localite();
        $localite->setNom($data['nom'] ?? '');
        $localite->setPrix($data['prix'] ?? null);
        $localite->setDescription($data['description'] ?? null);
        $localite->setLongitude($data['longitude'] ?? null);
        $localite->setLatitude($data['latitude'] ?? null);

        $errors = $validator->validate($localite);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $em->persist($localite);
        $em->flush();
        return $this->json($localite->toArray(), 201);
    }

    #[Route('/api/localite/{id}/update', name: 'api_localite_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request,
        Localite $localite,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (isset($data['nom'])) {
            $localite->setNom($data['nom']);
        }
        if (isset($data['prix'])) {
            $localite->setPrix($data['prix']);
        }
        if (isset($data['description'])) {
            $localite->setDescription($data['description']);
        }
        if (isset($data['longitude']) && isset($data['latitude'])) {
            $localite->setLongitude($data['longitude']);
            $localite->setLatitude($data['latitude']);
        }

        $errors = $validator->validate($localite);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }
        $em->flush();
        return $this->json($localite, 200);
    }

    #[Route('/api/localite/{id}/delete', name: 'api_localite_delete', methods: ['DELETE'])]
    public function delete($id, LocaliteRepository $localiteRepository, EntityManagerInterface $em): Response
    {
        $localite = $localiteRepository->find($id);
        if (!$localite) {
            return $this->json(['message' => 'Localité non trouvée'], 404);
        }
        
        if( $localite->getLotissements()->count() > 0){
            return $this->json('Quartié non supprimée car elle contient des lotissements', Response::HTTP_CONFLICT);
        }

        $em->remove($localite);
        $em->flush();

        return $this->json(['message' => 'Localité supprimée avec succès'], 204);
    }

    // lotissement localite
    #[Route('/api/localite/{id}/lotissements', name: 'api_localite_lotissements', methods: ['GET'])]
    public function lotissementLocalite(int $id, LocaliteRepository $localiteRepository): Response
    {
        $localite = $localiteRepository->find($id);
        if (!$localite) {
            return $this->json(['message' => 'Localité non trouvée'], 404);
        }
        $resultats = [];

        foreach ($localite->getLotissements() as $lotissement) {
            $resultats[] = [
                'id' => $lotissement->getId(),
                'nom' => $lotissement->getNom(),
                'localisation' => $lotissement->getLocalisation(),
                'description' => $lotissement->getDescription(),
                'statut' => $lotissement->getStatut(),
                'longitude' => $lotissement->getLongitude(),
                'latitude' => $lotissement->getLatitude(),
                'localite' => $lotissement->getLocalite() ? $lotissement->getLocalite()->toArray() : null,
                'dateCreation' => $lotissement->getDateCreation() ? $lotissement->getDateCreation()->format('Y-m-d H:i:s') : null,
                'planLotissements' => array_map(function ($planLotissement) {
                    return [
                        'id' => $planLotissement->getId(),
                        'url' => $planLotissement->getUrl(),
                        'version' => $planLotissement->getVersion(),
                        'description' => $planLotissement->getDescription(),
                        'dateCreation' => $planLotissement->getDateCreation() ? $planLotissement->getDateCreation()->format('Y-m-d H:i:s') : null,
                    ];
                }, $lotissement->getPlanLotissements()->toArray()),
                'lots' => array_map(function ($lots) {
                    return [
                        'id' => $lots->getId(),
                        'numeroLot' => $lots->getNumeroLot(),
                        'superficie' => $lots->getSuperficie(),
                        'statut' => $lots->getStatut(),
                        'prix' => $lots->getPrix(),
                        'longitude' => $lots->getLongitude(),
                        'latitude' => $lots->getLatitude(),
                    ];
                }, $lotissement->getLots()->toArray()),
                'parcelles' => array_map(function ($parcelle) {
                    return [
                        'id' => $parcelle->getId(),
                        'numero' => $parcelle->getNumero(),
                        'surface' => $parcelle->getSurface(),
                        'statut' => $parcelle->getStatut(),
                        'longitude' => $parcelle->getLongitude(),
                        'latitude' => $parcelle->getLatitude(),
                    ];
                }, $lotissement->getParcelles()->toArray())
            ];
        }

        return $this->json($resultats, 200);
    }


    #[Route('/api/localite/{demandeId}/details-confirmation', name: 'api_localite_show_confirmation_details', methods: ['GET'])]
    public function detailsConfirmationTwo($demandeId, RequestRepository $demandeRepository): Response
    {
        try {
            // 1. Récupération de la demande
            $demande = $demandeRepository->find($demandeId);
            if (!$demande) {
                return $this->json(['message' => 'Demande non trouvée'], 404);
            }

            // 2. Vérification de l'existence de la localité
            $localite = $demande->getQuartier();
            if (!$localite) {
                return $this->json(['message' => 'Quartier non trouvée'], 404);
            }

            // 3. Préparation des données de la localité
            $localiteData = [
                'id' => $localite->getId(),
                'nom' => $localite->getNom(),
                'prix' => $localite->getPrix(),
                'longitude' => $localite->getLongitude(),
                'latitude' => $localite->getLatitude(),
                'description' => $localite->getDescription(),
                'lotissements' => [],
            ];

            // 4. Récupération et formatage des lotissements
            $lotissements = $localite->getLotissements();
            foreach ($lotissements as $lotissement) {
                $lotissementData = [
                    'id' => $lotissement->getId(),
                    'nom' => $lotissement->getNom(),
                    'localisation' => $lotissement->getLocalisation(),
                    'description' => $lotissement->getDescription(),
                    'statut' => $lotissement->getStatut(),
                    'longitude' => $lotissement->getLongitude(),
                    'latitude' => $lotissement->getLatitude(),
                    'dateCreation' => $lotissement->getDateCreation()?->format('Y-m-d H:i:s'),
                    'planLotissements' => array_map(
                        fn($plan) => [
                            'id' => $plan->getId(),
                            'url' => $plan->getUrl(),
                            'version' => $plan->getVersion(),
                            'description' => $plan->getDescription(),
                            'dateCreation' => $plan->getDateCreation()?->format('Y-m-d H:i:s'),
                        ],
                        $lotissement->getPlanLotissements()->toArray()
                    ),
                    'lots' => array_map(
                        fn($lot) => [
                            'id' => $lot->getId(),
                            'numeroLot' => $lot->getNumeroLot(),
                            'superficie' => $lot->getSuperficie(),
                            'statut' => $lot->getStatut(),
                            'prix' => $lot->getPrix(),
                            'longitude' => $lot->getLongitude(),
                            'latitude' => $lot->getLatitude(),
                        ],
                        $lotissement->getLots()->toArray()
                    ),
                    'parcelles' => array_map(
                        fn($parcelle) => [
                            'id' => $parcelle->getId(),
                            'numero' => $parcelle->getNumero(),
                            'surface' => $parcelle->getSurface(),
                            'statut' => $parcelle->getStatut(),
                            'longitude' => $parcelle->getLongitude(),
                            'latitude' => $parcelle->getLatitude(),
                        ],
                        $lotissement->getParcelles()->toArray()
                    ),
                ];

                $localiteData['lotissements'][] = $lotissementData;
            }

            // 5. Retour des données
            return $this->json($localiteData, 200);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            return $this->json(
                ['message' => 'Une erreur est survenue lors de la récupération des données.'],
                500
            );
        }
    }
}
