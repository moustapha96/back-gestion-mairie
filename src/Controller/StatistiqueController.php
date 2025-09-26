<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;

class StatistiqueController extends AbstractController
{


    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    #[Route('/api/statistiques', name: 'api_get_statistiques', methods: ['GET'])]
    public function getStatistiques(): Response
    {
        $metadataList = $this->em->getMetadataFactory()->getAllMetadata();
        $byTable = [];
        $grandTotal = 0;

        foreach ($metadataList as $metadata) {
            $entityClass = $metadata->getName();
            $tableName = $metadata->getTableName();

            $count = $this->getCountForEntity($entityClass);

            $byEntity[$entityClass] = $count;
            $byTable[$tableName] = ($byTable[$tableName] ?? 0) + $count;
            $grandTotal += $count;
        }

        // Helper: prend la première table existante dans la liste, sinon 0
        $pick = function (array $candidates) use ($byTable): int {
            foreach ($candidates as $name) {
                if (isset($byTable[$name])) {
                    return (int) $byTable[$name];
                }
            }
            return 0;
        };

        // Résumé selon tes clés cibles (avec alias/fallbacks sûrs)
        $resulstat = [
            "users" => $pick(["gs_mairie_users"]),
            "parcelles" => $pick(["gs_mairie_parcelles", "gs_mairie_parcelle"]), // singulier ↔ pluriel
            "lots" => $pick(["gs_mairie_lots"]),
            "lotissements" => $pick(["gs_mairie_lotissements"]),
            "plan_lotissements" => $pick(["gs_mairie_plan_lotissements"]),
            "plan_lots" => $pick(["gs_mairie_plan_lots"]), 
            "demande_terrains" => $pick(["gs_mairie_demande_terrains"]),
            "quartiers"        => $pick(["gs_mairie_localites"]),
            "documents"        => $pick(["gs_mairie_documents"]),
            "niveau_validations" => $pick(["gs_mairie_niveau_validations"]),
            "historique_validations" => $pick(["gs_mairie_historique_validations"]),
            "categories_terrains" => $pick(["gs_mairie_categories_terrains"]),
            "titre_fonciers"   => $pick(["gs_mairie_titre_fonciers"]),
        ];

        return new JsonResponse($resulstat);
    }

    private function getCountForEntity(string $entityClass): int
    {
        try {
            return (int) $this->em->getRepository($entityClass)->count([]);
        } catch (\Throwable $e) {
            // En cas d'entité sans repo standard / vue non comptable, on renvoie 0 pour ne pas casser l’endpoint.
            return 0;
        }
    }

}












