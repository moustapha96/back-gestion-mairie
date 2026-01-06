<?php


namespace App\Controller;

use App\Entity\Lotissement;
use App\Repository\LocaliteRepository;
use App\Repository\LotissementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LotissementController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    // liste des lotissement
    #[Route('/api/lotissement/liste', name: 'api_lotissement_liste', methods: ['GET'])]
    public function listeLotissement(LotissementRepository $lotissementRepository): Response
    {
        $lotissements = $lotissementRepository->findBy([], ['id' => 'DESC']);
        $resultats = [];

        foreach ($lotissements as $lotissement) {
            $resultats[] = $lotissement->toArray();
        }
        return $this->json($resultats);
    }
    #[Route('/api/lotissement/{id}/details', name: 'api_lotissement_details', methods: ['GET'])]
    public function lotissementDetails($id, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);
        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }
        $resultats = [
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
                    'latitude' => $lots->getLatitude(),
                    'longitude' => $lots->getLongitude(),
                ];
            }, $lotissement->getLots()->toArray())
        ];
        return $this->json($resultats);
    }


    // methode pour creer un lotissement
    #[Route('/api/lotissement/create', name: 'api_lotissement_creer', methods: ['POST'])]
    public function createLotissement(
        Request $request,
        LotissementRepository $lotissementRepository,
        LocaliteRepository $localiteRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        $localite = $localiteRepository->find($data['localiteId']);

        if (!$localite) {
            return $this->json(['error' => 'Localite not found'], Response::HTTP_NOT_FOUND);
        }

        if (!isset($data['nom']) || !isset($data['statut'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $lotissement = new Lotissement();
        $lotissement->setNom($data['nom']);
        $lotissement->setLocalisation($data['localisation'] ?? null);
        $lotissement->setDescription($data['description'] ?? "");
        $lotissement->setLongitude($data['longitude'] ?? null);
        $lotissement->setLatitude($data['latitude'] ?? null);
        $lotissement->setStatut($data['statut']);
        $lotissement->setDateCreation(new \DateTime());
        $lotissement->setLocalite($localite);


        $this->em->persist($lotissement);
        $this->em->flush();

        return $this->json($lotissement->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/api/lotissement/{id}/update', name: 'api_lotissement_update', methods: ['PUT'])]
    public function updateLotissement(int $id, Request $request, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);

        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $lotissement->setNom($data['nom']);
        }
        if (isset($data['localisation'])) {
            $lotissement->setLocalisation($data['localisation']);
        }
        if (isset($data['description'])) {
            $lotissement->setDescription($data['description']);
        }
        if (isset($data['statut'])) {
            $lotissement->setStatut($data['statut']);
        }
        if (isset($data['longitude'])) {
            $lotissement->setLongitude($data['longitude']);
        }
        if (isset($data['latitude'])) {
            $lotissement->setLatitude($data['latitude']);
        }
        $this->em->persist($lotissement);
        $this->em->flush();

        return $this->json($lotissement->toArray(), Response::HTTP_OK);
    }


    // get plan lotissement
    #[Route('/api/lotissement/{id}/plans', name: 'api_lotissement_plans', methods: ['GET'])]
    public function getPlanLotissement(int $id, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);
        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($lotissement->getPlanLotissements()->toArray(), Response::HTTP_OK);
    }

    #[Route('/api/lotissement/{id}/lots', name: 'api_lotissement_lots', methods: ['GET'])]
    public function getLotLotissement(int $id, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);
        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($lotissement->getLots()->toArray(), Response::HTTP_OK);
    }


    #[Route('/api/lotissement/{id}/update-statut', name: 'api_lotissement_update_statut', methods: ['PUT'])]
    public function updateLotissementStatut($id, Request $requet, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);
        if (!$lotissement) {
            return $this->json("Lotissement non trouvée", Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($requet->getContent(), true);

        $statut = $data['statut'];
        $lotissement->setStatut($statut);
        $this->em->persist($lotissement);
        $this->em->flush();

        return $this->json("Statut mis à jour", Response::HTTP_OK);
    }

    #[Route('/api/lotissement/{id}/delete', name: 'api_lotissement_delete', methods: ['DELETE'])]
    public function deleteLotissement(int $id, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);
        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }
        if ($lotissement->getLots()->count() > 0) {
            return $this->json('Ce Lotissement contient des ilots', Response::HTTP_CONFLICT);
        }

        $this->em->remove($lotissement);
        $this->em->flush();

        return $this->json("Lotissement supprimée", Response::HTTP_NO_CONTENT);
    }

}
