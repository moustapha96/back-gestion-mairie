<?php

// src/DataFixtures/NiveauFixtures.php
namespace App\DataFixtures;

use App\Entity\NiveauValidation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class NiveauFixtures extends Fixture
{
    public function load(ObjectManager $om): void
    {
        $n1 = (new NiveauValidation())->setNom('Contrôle Agent')->setRoleRequis('ROLE_AGENT')->setOrdre(1);
        $n2 = (new NiveauValidation())->setNom('Chef de service')->setRoleRequis('ROLE_CHEF_SERVICE')->setOrdre(2);
        $n3 = (new NiveauValidation())->setNom('Commission')->setRoleRequis('ROLE_COMMISSION')->setOrdre(3);
        $n4 = (new NiveauValidation())->setNom('Maire')->setRoleRequis('ROLE_MAIRE')->setOrdre(4);

        $om->persist($n1); $om->persist($n2); $om->persist($n3); $om->persist($n4);
        $om->flush();
    }
}
