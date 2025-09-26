<?php

namespace App\Controller;

use App\Entity\TitreFoncier;
use App\services\TitreFoncierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;


#[Route('/api/titres')]
class TitreFoncierController extends AbstractController
{
    public function __construct(private TitreFoncierService $service) {}

    #[Route('', name: 'titres_list', methods: ['GET'])]
    public function list(Request $req): Response
    {
        $page = max(1, (int)$req->query->get('page', 1));
        $pageSize = min(200, max(1, (int)$req->query->get('pageSize', 10)));
        $sortField = $req->query->get('sortField', 'id');
        $sortOrder = $req->query->get('sortOrder', 'DESC');

        $filters = [
            'numero'        => $req->query->get('numero'),
            'numeroLot'     => $req->query->get('numeroLot'),
            'quartierId'    => $req->query->get('quartierId'),
            'superficieMin' => $req->query->get('superficieMin'),
            'superficieMax' => $req->query->get('superficieMax'),
            'type'   => $req->query->get('type'),
        ];

        $res = $this->service->searchPaginated($filters, $page, $pageSize, $sortField, $sortOrder);

        return $this->json([
            'success'  => true,
            'items'    => array_map([$this, 'serializeItem'], $res['items']),
            'total'    => $res['total'],
            'page'     => $page,
            'pageSize' => $pageSize,
        ]);
    }

    #[Route('', name: 'titres_create', methods: ['POST'])]
    public function create(Request $req): Response
    {
        $data = json_decode($req->getContent(), true) ?? [];
        $item = $this->service->create($data);
        return $this->json(['success' => true, 'item' => $this->serializeItem($item)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'titres_get', methods: ['GET'])]
    public function getOne(TitreFoncier $t): Response
    {
        return $this->json(['success' => true, 'item' => $this->serializeItem($t)]);
    }

    #[Route('/{id}', name: 'titres_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $req, TitreFoncier $t): Response
    {
        $data = json_decode($req->getContent(), true) ?? [];
        $item = $this->service->update($t, $data);
        return $this->json(['success' => true, 'item' => $this->serializeItem($item)]);
    }

    #[Route('/{id}', name: 'titres_delete', methods: ['DELETE'])]
    public function delete(TitreFoncier $t): Response
    {
        $this->service->delete($t);
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }

    private function serializeItem(TitreFoncier $t): array
    {
        return [
            'id'            => $t->getId(),
            'numero'        => $t->getNumero(),
            'superficie'    => $t->getSuperficie(),
            'titreFigure'   => $t->getTitreFigure(),
            'etatDroitReel' => $t->getEtatDroitReel(),
            'numeroLot'     => $t->getNumeroLot(),
            'type'             => $t->getType(),
            'quartier'      => $t->getQuartier() ? [
                'id'   => $t->getQuartier()->getId(),
                'nom'  => method_exists($t->getQuartier(), 'getNom') ? $t->getQuartier()->getNom() : null,
            ] : null,
        ];
    }
}
