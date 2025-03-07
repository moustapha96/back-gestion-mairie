<?php


namespace App\Controller;

use App\Entity\Lotissement;
use App\Entity\Lots;
use App\Repository\LotissementRepository;
use App\Repository\LotsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LotController extends AbstractController
{


    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/api/lot/liste', name: 'api_lot_liste', methods: ['GET'])]
    public function listeLot(LotsRepository $lotsRepository): Response
    {
        $lots = $lotsRepository->findAll();
        $resultats = [];

        foreach ($lots as $lot) {
            $resultats[] = [
                'id' => $lot->getId(),
                'numeroLot' => $lot->getNumeroLot(),
                'superficie' => $lot->getSuperficie(),
                'typeUsage' => $lot->getUsage(),
                'statut' => $lot->getStatut(),
                'prix' => $lot->getPrix(),
                'longitude' => $lot->getLongitude(),
                'latitude' => $lot->getLatitude(),
                'lotissement' => [
                    'id' => $lot->getLotissement()->getId(),
                    'nom' => $lot->getLotissement()->getNom(),
                    'localisation' => $lot->getLotissement()->getLocalisation(),
                    'description' => $lot->getLotissement()->getDescription(),
                    'statut' => $lot->getLotissement()->getStatut(),
                    'dateCreation' => $lot->getLotissement()->getDateCreation()->format('Y-m-d'),
                    'longitude' => $lot->getLotissement()->getLongitude(),
                    'latitude' => $lot->getLotissement()->getLatitude(),
                ]
            ];
        }
        return $this->json($resultats, 200);
    }


    #[Route('/api/lot/{id}/details', name: 'api_lot_detail', methods: ['GET'])]
    public function detailLot($id, LotsRepository $lotsRepository): Response
    {
        $lot = $lotsRepository->find($id);
        if (!$lot) {
            return $this->json('Lot introuvable', 404);
        }
        $resultats = [
            'id' => $lot->getId(),
            'numeroLot' => $lot->getNumeroLot(),
            'superficie' => $lot->getSuperficie(),
            'typeUsage' => $lot->getUsage(),
            'statut' => $lot->getStatut(),
            'prix' => $lot->getPrix(),
            'longitude' => $lot->getLongitude(),
            'latitude' => $lot->getLatitude(),
            'lotissement' => [
                'id' => $lot->getLotissement()->getId(),
                'nom' => $lot->getLotissement()->getNom(),
                'localisation' => $lot->getLotissement()->getLocalisation(),
                'description' => $lot->getLotissement()->getDescription(),
                'statut' => $lot->getLotissement()->getStatut(),
                'dateCreation' => $lot->getLotissement()->getDateCreation()->format('Y-m-d'),
                'longitude' => $lot->getLotissement()->getLongitude(),
                'latitude' => $lot->getLotissement()->getLatitude(),
            ]
        ];
        return $this->json($resultats, 200);
    }


    #[Route('/api/lot/create', name: 'api_lot_create', methods: ['POST'])]
    public function createLot(
        Request $request,
        LotissementRepository $lotissementRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['numeroLot']) || !isset($data['statut']) || !isset($data['prix'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $lotissement = $lotissementRepository->find($data['lotissementId']);
        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }

        // Création du lot
        $lot = new Lots();
        $lot->setNumeroLot($data['numeroLot']);
        $lot->setPrix($data['prix']);
        $lot->setUsage($data['usage'] ?? null);
        $lot->setSuperficie($data['superficie'] ?? null);
        $lot->setStatut($data['statut']);
        $lot->setLongitude($data['longitude'] ?? null);
        $lot->setLatitude($data['latitude'] ?? null);
        $lot->setLotissement($lotissement);

        $this->em->persist($lot);
        $this->em->flush();
        return $this->json($lot->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/api/lot/{id}/update', name: 'api_lot_update', methods: ['PUT'])]
    public function updateLot(int $id, Request $request, LotsRepository $lotsRepository): Response
    {
        $lot = $lotsRepository->find($id);

        if (!$lot) {
            return $this->json(['error' => 'Lot not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        // Mise à jour des informations du lot
        if (isset($data['numeroLot'])) {
            $lot->setNumeroLot($data['numeroLot']);
        }
        if (isset($data['superficie'])) {
            $lot->setSuperficie($data['superficie']);
        }
        if (isset($data['usage'])) {
            $lot->setUsage($data['usage']);
        }
        if (isset($data['statut'])) {
            $lot->setStatut($data['statut']);
        }
        if (isset($data['prix'])) {
            $lot->setPrix($data['prix']);
        }
        if (isset($data['longitude'])) {
            $lot->setLongitude($data['longitude']);
        }
        if (isset($data['latitude'])) {
            $lot->setLatitude($data['latitude']);
        }
        $this->em->persist($lot);
        $this->em->flush();

        return $this->json($lot->toArray(), Response::HTTP_OK);
    }



    #[Route('/api/lot/{id}/update-statut', name: 'api_lot_update_statut', methods: ['PUT'])]
    public function updateLotStatut(int $id, Request $requet, LotsRepository $lotsRepository): Response
    {
        $lot = $lotsRepository->find($id);
        if (!$lot) {
            return $this->json("Lot non trouvée", Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($requet->getContent(), true);

        $statut = $data['statut'];
        $lot->setStatut($statut);
        $this->em->persist($lot);
        $this->em->flush();

        return $this->json("Statut mis à jour", Response::HTTP_OK);
    }
}
