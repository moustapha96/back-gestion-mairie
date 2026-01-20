<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Liste des routes importantes à afficher sur la page d'accueil
        $routes = [
            [
                'method' => 'POST',
                'path'   => '/api/login',
                'label'  => 'Authentification (JWT)',
                'detail' => 'Permet de se connecter et de récupérer un token JWT + refresh token.',
            ],
            [
                'method' => 'POST',
                'path'   => '/api/token/refresh',
                'label'  => 'Renouvellement du token (Refresh Token)',
                'detail' => 'Permet d\'obtenir un nouveau token JWT à partir d\'un refresh token valide.',
            ],
            [
                'method' => 'GET',
                'path'   => '/api/doc',
                'label'  => 'Documentation de l’API (Swagger / API Platform)',
                'detail' => 'Interface graphique pour explorer et tester toutes les routes de l’API.',
            ],
            [
                'method' => 'POST',
                'path'   => '/api/demande/import',
                'label'  => 'Import des demandes de terrain',
                'detail' => 'Permet d\'importer en masse des demandes de terrain via un fichier Excel (.xlsx).',
            ],
        ];

        return $this->render('home/index.html.twig', [
            'routes_importantes' => $routes,
        ]);
    }
}

