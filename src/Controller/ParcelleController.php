<?php


namespace App\Controller;

use App\Entity\Lotissement;
use App\Entity\Parcelle;
use App\Entity\Parcelles;
use App\Repository\LotissementRepository;
use App\Repository\ParcelleissementRepository;
use App\Repository\ParcelleRepository;
use App\Repository\ParcellesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParcelleController extends AbstractController
{


    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/api/parcelle/liste', name: 'api_Parcelle_liste', methods: ['GET'])]
    public function listeParcelle(ParcelleRepository $parcellesRepository): Response
    {
        $parcelles = $parcellesRepository->findAll();
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
        $parcelle->setNumero($data['numero']);
        $parcelle->setSurface($data['superface']);
        $parcelle->setStatut($data['statut']);
        $parcelle->setLotissement($lotissement);

        // Ajout des coordonnées géographiques
        if (isset($data['latitude'])) {
            $parcelle->setLatitude($data['latitude']);
        }
        if (isset($data['longitude'])) {
            $parcelle->setLongitude($data['longitude']);
        }

        $this->em->persist($parcelle);
        $this->em->flush();
        return $this->json($parcelle, Response::HTTP_CREATED);
    }

    #[Route('/api/parcelle/{id}/update', name: 'api_parcelle_update', methods: ['PUT'])]
    public function updateParcelle(int $id, Request $request, LotissementRepository $lotissementRepository, ParcelleRepository $parcelleRepository): Response
    {
        $parcelle = $parcelleRepository->find($id);

        if (!$parcelle) {
            return $this->json(['error' => 'Parcelle not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour des informations de la Parcelle
        if (isset($data['numero'])) {
            $parcelle->setNumero($data['numero']);
        }
        if (isset($data['lotissement']['id'])) {
            $lotissement = $lotissementRepository->find($data['lotissement']['id']);
            if (!$lotissement) {
                return $this->json(['error' => 'lotissement not found'], Response::HTTP_NOT_FOUND);
            }
            $parcelle->setLotissement($lotissement);
        }
        if (isset($data['superface'])) {
            $parcelle->setSurface($data['superface']);
        }
        if (isset($data['statut'])) {
            $parcelle->setStatut($data['statut']);
        }
        // Ajout de la mise à jour des coordonnées géographiques
        if (isset($data['latitude'])) {
            $parcelle->setLatitude($data['latitude']);
        }
        if (isset($data['longitude'])) {
            $parcelle->setLongitude($data['longitude']);
        }

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
}
