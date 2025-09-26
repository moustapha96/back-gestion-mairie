<?php

namespace App\Controller;

use App\Entity\HistoriqueValidation;
use App\services\HistoriqueValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/historiques')]
class HistoriqueValidationController extends AbstractController
{
    public function __construct(private HistoriqueValidationService $svc) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $r): Response
    {
        $filters = [
            'demandeId'   => $r->query->get('demandeId'),
            'validateurId' => $r->query->get('validateurId'),
            'action'      => $r->query->get('action'),
            'from'        => $r->query->get('from'),
            'to'          => $r->query->get('to'),
        ];
        $page     = max(1, (int)$r->query->get('page', 1));
        $pageSize = min(200, max(1, (int)$r->query->get('pageSize', 10)));

        $res = $this->svc->searchPaginated($filters, $page, $pageSize);

        return $this->json([
            'success' => true,
            'items'  => array_map([$this, 'toArray'], $res['items']),
            'total'  => $res['total'],
            'page'   => $page,
            'pageSize' => $pageSize,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $r): Response
    {
        $data = json_decode($r->getContent(), true) ?? [];
        $item = $this->svc->create($data);
        return $this->json(['success' => true, 'item' => $this->toArray($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(HistoriqueValidation $h): Response
    {
        $this->svc->delete($h);
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }

    private function toArray(HistoriqueValidation $h): array
    {
        return [
            'id'        => $h->getId(),
            'action'    => $h->getAction(),
            'motif'     => $h->getMotif(),
            'dateAction' => $h->getDateAction()?->format('Y-m-d H:i:s'),
            'demande'   => $h->getDemande() ? [
                'id' => $h->getDemande()->getId(),
                // Ajoute ici 'reference' si ta DemandeTerrain l’expose
            ] : null,
            'validateur' => $h->getValidateur() ? [
                'id' => $h->getValidateur()->getId(),
                'fullName' => method_exists($h->getValidateur(), 'getFullName') ? $h->getValidateur()->getRoles() : null,
                'email' => method_exists($h->getValidateur(), 'getEmail') ? $h->getValidateur()->getEmail() : null,
            ] : null,
        ];
    }
}
