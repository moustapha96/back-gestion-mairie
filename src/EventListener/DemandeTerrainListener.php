<?php

namespace App\EventListener;

use App\Entity\DemandeTerrain;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs as PersistenceLifecycleEventArgs;

class DemandeTerrainListener
{
    /**
     * Méthode exécutée avant la persistance d'une nouvelle demande de terrain.
     */
    public function prePersist(DemandeTerrain $demandeTerrain, LifecycleEventArgs $event): void
    {
        $demandeTerrain->setDateCreation(new \DateTime());
    }

    /**
     * Méthode exécutée avant la mise à jour d'une demande de terrain.
     */
    public function preUpdate(DemandeTerrain $demandeTerrain, LifecycleEventArgs $event): void
    {
        $demandeTerrain->setDateModification(new \DateTime());
    }
}
