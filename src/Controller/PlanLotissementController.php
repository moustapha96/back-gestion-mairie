<?php


namespace App\Controller;

use App\Entity\Lotissement;
use App\Entity\PlanLotissement;
use App\Repository\LotissementRepository;
use App\Repository\PlanLotissementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanLotissementController extends AbstractController
{

    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    #[Route('/api/plan-lotissement/liste', name: 'api_plan_lotissement_list', methods: ['GET'])]
    public function listPlanLotissement(PlanLotissementRepository $planLotissementRepository): Response
    {
        $plansLotissements = $planLotissementRepository->findAll();
        $resultats = [];
        foreach ($plansLotissements as $planLotissement) {
            $resultats[] = [
                'id' => $planLotissement->getId(),
                'url' => $planLotissement->getUrl(),
                'version' => $planLotissement->getVersion(),
                'description' => $planLotissement->getDescription(),
                'dateCreation' => $planLotissement->getDateCreation()->format('Y-m-d'),
                'lotissement' => [
                    'id' => $planLotissement->getLotissement()->getId(),
                    'nom' => $planLotissement->getLotissement()->getNom(),
                    'localisation' => $planLotissement->getLotissement()->getLocalisation(),
                    'description' => $planLotissement->getLotissement()->getDescription(),
                    'statut' => $planLotissement->getLotissement()->getStatut(),
                    'dateCreation' => $planLotissement->getLotissement()->getDateCreation()->format('Y-m-d'),
                ]
            ];
        }
        return $this->json($resultats);
    }

    #[Route('/api/plan-lotissement/{id}/details', name: 'api_plan_lotissement_details', methods: ['GET'])]
    public function detailPlanLotissement($id, PlanLotissementRepository $planLotissementRepository): Response
    {
        $planLotissement = $planLotissementRepository->find($id);
        if (!$planLotissement) {
            return $this->json(['message' => 'Plan lotissement non trouvé'], 404);
        }
        $resultats = [
            'id' => $planLotissement->getId(),
            'url' => $planLotissement->getUrl(),
            'version' => $planLotissement->getVersion(),
            'description' => $planLotissement->getDescription(),
            'dateCreation' => $planLotissement->getDateCreation()->format('Y-m-d'),
            'lotissement' => [
                'id' => $planLotissement->getLotissement()->getId(),
                'nom' => $planLotissement->getLotissement()->getNom(),
                'localisation' => $planLotissement->getLotissement()->getLocalisation(),
                'description' => $planLotissement->getLotissement()->getDescription(),
                'statut' => $planLotissement->getLotissement()->getStatut(),
                'dateCreation' => $planLotissement->getLotissement()->getDateCreation()->format('Y-m-d'),
            ]
        ];

        return $this->json($resultats);
    }


    #[Route('/api/plan-lotissement/create', name: 'api_plan_lotissement_create', methods: ['POST'])]
    public function createPlanLotissement(Request $request, LotissementRepository $lotissementRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        $version = $request->request->get('version') ?? $data['version'] ?? null;
        $description = $request->request->get('description') ?? $data['description'] ?? null;
        $lotissementId = $request->request->get('lotissementId') ?? $data['lotissementId'] ?? null;


        if (!isset($version) || !isset($description) || !isset($lotissementId)) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $lotissement = $lotissementRepository->find($lotissementId);
        if (!$lotissement) {
            return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
        }

        $planLotissement = new PlanLotissement();
        $planLotissement->setVersion($version);
        $planLotissement->setDescription($description);
        $planLotissement->setDateCreation(new \DateTime());
        $planLotissement->setLotissement($lotissement);

        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');
        if ($file) {
            $newFilename = sprintf(
                '%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($version)),
                $lotissement ? str_replace(' ', '-', strtolower($lotissement->getNom())) : date('YmdHis'),
                (new \DateTime())->format('Y-m-d'),
                $file->guessExtension()
            );
            $file->move($this->getParameter('plan_directory'), $newFilename);
            $url = $this->getParameter('plan_directory') . "/" . $newFilename;
            $planLotissement->setUrl($url);
        } else {
            return $this->json(['message' => 'Veuillez uploader un document'], Response::HTTP_BAD_REQUEST);
        }
        $this->em->persist($planLotissement);
        $this->em->flush();

        return $this->json($planLotissement, Response::HTTP_CREATED);
    }

    #[Route('/api/plan-lotissement/{id}/update', name: 'api_plan_lotissement_update', methods: ['PUT'])]
    public function updatePlanLotissement(
        int $id,
        Request $request,
        LotissementRepository $lotissementRepository,
        PlanLotissementRepository $planLotissementRepository
    ): Response {
        $planLotissement = $planLotissementRepository->find($id);

        if (!$planLotissement) {
            return $this->json(['error' => 'PlanLotissement not found'], Response::HTTP_NOT_FOUND);
        }
        $version = $request->request->get('version');
        $description = $request->request->get('description');
        $lotissementId = $request->request->get('lotissementId');

        if (isset($lotissementId)) {
            $lotissement = $lotissementRepository->find($lotissementId);
            if (!$lotissement) {
                return $this->json(['error' => 'Lotissement not found'], Response::HTTP_NOT_FOUND);
            } else {
                $planLotissement->setLotissement($lotissement);
            }
        }

        if (isset($version)) {
            $planLotissement->setVersion($version);
        }
        if (isset($description)) {
            $planLotissement->setDescription($description);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');
        if ($file) {
            $newFilename = sprintf(
                '%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($planLotissement->getVersion())),
                $lotissement ? str_replace(' ', '-', strtolower($lotissement->getNom())) : date('YmdHis'),
                (new \DateTime())->format('Y-m-d'),
                $file->guessExtension()
            );
            $file->move($this->getParameter('plan_directory'), $newFilename);
            $url = $this->getParameter('plan_directory') . "/" . $newFilename;
            $planLotissement->setUrl($url);
        }

        $this->em->persist($planLotissement);
        $this->em->flush();

        return $this->json($planLotissement->toArray(), Response::HTTP_OK);
    }


    #[Route('/api/plan-lotissement/file/{id}', name: 'api_plan_lotissement_get_document', methods: ['GET'])]
    public function planLotissementDocument(int $id, PlanLotissementRepository $planLotissementRepository): Response
    {

        $planLotissement = $planLotissementRepository->find($id);


        try {
            $mimeType = mime_content_type($planLotissement->getUrl());
            if ($mimeType !== 'application/pdf') {
                return new Response(
                    json_encode(['message' => 'Le fichier doit être un PDF']),
                    Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                    ['Content-Type' => 'application/json']
                );
            }
            try {
                $content = file_get_contents($planLotissement->getUrl());
                if ($content === false) {
                    throw new \Exception("Erreur lors de la lecture du fichier.");
                }
                $base64 = base64_encode($content);
                $response = new Response(
                    json_encode($base64),
                    Response::HTTP_OK,
                    ['Content-Type' => 'application/json']
                );

                return $response;
            } catch (\Exception $e) {
                return new Response(
                    json_encode(['message' => 'Erreur lors de l\'encodage du fichier en base64', 'error' => $e->getMessage()]),
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    ['Content-Type' => 'application/json']
                );
            }
        } catch (\Throwable $th) {
            return new Response(
                "Fichier non trouver",
                Response::HTTP_OK
            );
        }
    }
}
