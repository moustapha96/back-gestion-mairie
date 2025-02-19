<?php


namespace App\Controller;

use App\Entity\Lotissement;
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
        $lotissements = $lotissementRepository->findAll();
        $resultats = [];

        foreach ($lotissements as $lotissement) {
            $resultats[] = $lotissement->toArray();
        }

        return $this->json($resultats);
    }

    // methode pour creer un lotissement
    #[Route('/api/lotissement/create', name: 'api_lotissement_creer', methods: ['POST'])]
    public function createLotissement(Request $request, LotissementRepository $lotissementRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || !isset($data['localisation']) || !isset($data['description']) || !isset($data['statut'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $lotissement = new Lotissement();
        $lotissement->setNom($data['nom']);
        $lotissement->setLocalisation($data['localisation']);
        $lotissement->setDescription($data['description']);
        $lotissement->setStatut($data['statut']);
        $lotissement->setDateCreation(new \DateTime());

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
        $this->em->persist($lotissement);
        $this->em->flush();

        return $this->json($lotissement->toArray(), Response::HTTP_OK);
    }

    #[Route('/api/lotissement/{id}/details', name: 'api_lotissement_details', methods: ['GET'])]
    public function details(int $id, LotissementRepository $lotissementRepository): Response
    {
        $lotissement = $lotissementRepository->find($id);

        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($lotissement->toArray1(), Response::HTTP_OK);
    }
}
