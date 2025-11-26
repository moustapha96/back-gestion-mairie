<?php

namespace App\EventListener;

use App\Entity\Request as Demande;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs as PersistenceLifecycleEventArgs;

class DemandeListener
{
    /**
     * Méthode exécutée avant la persistance d'une nouvelle demande de terrain.
     */
    public function prePersist(Demande $Demande, LifecycleEventArgs $event): void
    {
        $Demande->setDateCreation(new \DateTime());
    }

    /**
     * Méthode exécutée avant la mise à jour d'une demande de terrain.
     */
    public function preUpdate(Demande $Demande, LifecycleEventArgs $event): void
    {
        $Demande->setDateModification(new \DateTime());
    }
}
