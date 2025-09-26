<?php

namespace App\Controller;

use App\Entity\NiveauValidation;
use App\services\NiveauValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/niveaux')]
class NiveauValidationController extends AbstractController
{
    public function __construct(private NiveauValidationService $svc) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $r): Response
    {
        $filters = [
            'nom'        => $r->query->get('nom'),
            'roleRequis' => $r->query->get('roleRequis'),
            'ordreMin'   => $r->query->get('ordreMin'),
            'ordreMax'   => $r->query->get('ordreMax'),
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

    #[Route('/{id}', methods: ['GET'])]
    public function getOne(NiveauValidation $n): Response
    {
        return $this->json(['success' => true, 'item' => $this->toArray($n)]);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(Request $r, NiveauValidation $n): Response
    {
        $data = json_decode($r->getContent(), true) ?? [];
        $item = $this->svc->update($n, $data);
        return $this->json(['success' => true, 'item' => $this->toArray($item)]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(NiveauValidation $n): Response
    {
        $this->svc->delete($n);
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }

    private function toArray(NiveauValidation $n): array
    {
        return [
            'id'         => $n->getId(),
            'nom'        => $n->getNom(),
            'roleRequis' => $n->getRoleRequis(),
            'ordre'      => $n->getOrdre(),
        ];
    }
}
