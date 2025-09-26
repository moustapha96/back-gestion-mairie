<?php


// src/Controller/ValidationController.php
namespace App\Controller;

use App\Entity\DemandeTerrain;
use App\services\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class ValidationController extends AbstractController
{
    #[Route('/api/demandes/{id}/valider', name: 'valider_demande', methods: ['POST'])]
    public function validerDemande(
        DemandeTerrain $demande,
        ValidationService $validationService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié.'], 401);
        }
        try {
            $validationService->validerDemande($demande, $user);
            return $this->json(['message' => 'Demande validée avec succès.']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }

    #[Route('/api/demandes/{id}/rejeter', name: 'rejeter_demande', methods: ['POST'])]
    public function rejeterDemande(
        DemandeTerrain $demande,
        ValidationService $validationService,
        Request $request
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $motif = $data['motif'] ?? null;
        if (!$motif) {
            return $this->json(['error' => 'Le motif de rejet est obligatoire.'], 400);
        }

        try {
            $validationService->rejeterDemande($demande, $user, $motif);
            return $this->json(['message' => 'Demande rejetée avec succès.']);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        }
    }
}
