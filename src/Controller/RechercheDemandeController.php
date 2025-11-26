<?php

namespace App\Controller;

use App\services\FonctionsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class RechercheDemandeController extends AbstractController
{
    private $fonctionService;

    public function __construct(
        FonctionsService $fonctionService
    ) {
        $this->fonctionService = $fonctionService;
    }

    #[Route('/api/electeur/recherche', name: 'api_electeur_recherche', methods: ['POST'])]
    public function rechercheElecteur(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true) ?? [];

            $prenom         = $data['prenom'] ?? null;
            $nom            = $data['nom'] ?? null;
            $email          = $data['email'] ?? null;
            $telephone      = $data['telephone'] ?? null;
            $profession     = $data['profession'] ?? null;
            $adresse        = $data['adresse'] ?? null;
            $lieuNaissance  = $data['lieuNaissance'] ?? null;
            $dateNaissance  = $data['dateNaissance'] ?? null;
            $numeroElecteur = $data['numeroElecteur'] ?? null;

            // Pagination (valeurs par défaut)
            $page     = max(1, (int)($data['page'] ?? 1));
            $pageSize = min(200, max(1, (int)($data['pageSize'] ?? 10))); // borne à 200

            // Appel service paginé
            $result = $this->fonctionService->fetchSearchPaginated(
                $prenom,
                $nom,
                $email,
                $telephone,
                $profession,
                $adresse,
                $lieuNaissance,
                $dateNaissance,
                $numeroElecteur,
                $page,
                $pageSize
            );

            return $this->json(
                [
                    'success'   => true,
                    'items'     => $result['items'],
                    'total'     => $result['total'],
                    'page'      => $page,
                    'pageSize'  => $pageSize,
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $e) {
            return $this->json(
                ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
