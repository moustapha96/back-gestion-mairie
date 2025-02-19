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
    #[Route('/api/plan-lotissements/liste', name: 'api_plan_lotissement_list', methods: ['GET'])]
    public function listPlanLotissement(PlanLotissementRepository $planLotissementRepository): Response
    {
        $plansLotissements = $planLotissementRepository->findAll();
        $resultats = [];
        foreach ($plansLotissements as $planLotissement) {
            $resultats[] = $planLotissement->toArray();
        }
        return $this->json($resultats);
    }

    #[Route('/api/plan-lotissements/create', name: 'api_plan_lotissement_create', methods: ['POST'])]
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

    #[Route('/api/plan-lotissements/{id}/update', name: 'api_plan_lotissement_update', methods: ['PUT'])]
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
        $version = $request->request->get('version') ?? $data['version'] ?? null;
        $description = $request->request->get('description') ?? $data['description'] ?? null;
        $lotissementId = $request->request->get('lotissementId') ?? $data['lotissementId'] ?? null;

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
}
