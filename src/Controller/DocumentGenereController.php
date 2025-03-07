<?php

namespace App\Controller;

use App\Entity\BailCommunal;
use App\Entity\CalculRedevance;
use App\Entity\DemandeTerrain;
use App\Entity\PermisOccupation;
use App\Entity\PropositionBail;
use App\Entity\DocumentGenere;
use App\Repository\BailCommunalRepository;
use App\Repository\CalculRedevanceRepository;
use App\Repository\PermisOccupationRepository;
use App\Repository\PropositionBailRepository;
use App\services\DocumentGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentGenereController extends AbstractController
{

    private $documentGeneratorService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        DocumentGeneratorService $documentGeneratorService,
        EntityManagerInterface $entityManager
    ) {
        $this->documentGeneratorService = $documentGeneratorService;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/demande-terrain/{id}/document', methods: ['POST'])]
    public function createDocument(
        DemandeTerrain $demandeTerrain,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // try {
        //     $document = $demandeTerrain->createDocument(
        //         $data['type'],
        //         $data['contenu']
        //     );

        //     $em->persist($document);
        //     $em->flush();

        //     return $this->json([
        //         'message' => 'Document créé avec succès',
        //         'id' => $document->getId()
        //     ], 201);
        // } catch (\InvalidArgumentException $e) {
        //     return $this->json(['error' => $e->getMessage()], 400);
        // }
        return new Response();
    }
}

// php bin/console doctrine:migrations:diff
// php bin/console doctrine:migrations:migrate