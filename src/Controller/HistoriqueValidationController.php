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
    public function __construct(private HistoriqueValidationService $svc)
    {
    }

   #[Route('', methods: ['GET'])]
    public function list(Request $r): Response
    {
        $filters = [
            'demandeId'   => $r->query->get('demandeId'),
            'validateurId'=> $r->query->get('validateurId'),
            'action'      => $r->query->get('action'),
            'from'        => $r->query->get('from'),
            'to'          => $r->query->get('to'),
        ];
        $page     = max(1, (int) $r->query->get('page', 1));
        $pageSize = min(200, max(1, (int) $r->query->get('pageSize', 10)));

        $res = $this->svc->searchPaginated($filters, $page, $pageSize);

        return $this->json([
            'success'  => true,
            'items'    => array_map(function(array $r) {
                return [
                    'id'         => $r['id'],
                    'action'     => $r['action'],
                    'motif'      => $r['motif'],
                    'dateAction' => $r['dateAction'],

                    'demande' => [
                        'id'             => $r['demande_id'],
                        'typeDemande'    => $r['demande_typeDemande'],
                        'statut'         => $r['demande_statut'],
                        'dateCreation'   => $r['demande_dateCreation'],
                        'numeroElecteur' => $r['demande_numeroElecteur'],
                        'nom'            => $r['demande_nom'],
                        'prenom'         => $r['demande_prenom'],
                    ],

                    'validateur' => [
                        'id'     => $r['validateur_id'],
                        'prenom' => $r['validateur_prenom'],
                        'nom'    => $r['validateur_nom'],
                        'email'  => $r['validateur_email'],
                    ],
                ];
            }, $res['items']),
            'total'    => $res['total'],
            'page'     => $page,
            'pageSize' => $pageSize,
        ]);
    }


  
#[Route('', name: 'historique_create', methods: ['POST'])]
    public function create(Request $r, HistoriqueValidationService $svc): Response
    {
        $data = json_decode($r->getContent(), true) ?? [];

        try {
            $item = $svc->create($data);
            // Pour la réponse de création, tu peux renvoyer une version light:
            return $this->json(
                ['success' => true, 'item' => [
                    'id'         => $item->getId(),
                    'action'     => $item->getAction(),
                    'motif'      => $item->getMotif(),
                    'dateAction' => $item->getDateAction()?->format('Y-m-d H:i:s'),
                ]],
                Response::HTTP_CREATED
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(['success' => false, 'error' => 'Erreur serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(HistoriqueValidation $h): Response
    {
        $this->svc->delete($h);
        // 204 sans body est OK, mais on met un tiny JSON pour l’app front
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }

    private function toArray(HistoriqueValidation $h): array
    {
        return [
            'id' => $h->getId(),
            'action' => $h->getAction(),
            'motif' => $h->getMotif(),
            'dateAction' => $h->getDateAction()?->format('Y-m-d H:i:s'),
            'demande' => $h->getRequest() ? $h->getRequest()->toArray() : null,
            'validateur' => $h->getValidateur() ? [
                'id' => $h->getValidateur()->getId(),
                'prenom' => method_exists($h->getValidateur(), 'getPrenom') ? $h->getValidateur()->getPrenom() : null,
                'nom' => method_exists($h->getValidateur(), 'getNom') ? $h->getValidateur()->getNom() : null,
                'email' => method_exists($h->getValidateur(), 'getEmail') ? $h->getValidateur()->getEmail() : null,
            ] : null,
        ];
    }
}
