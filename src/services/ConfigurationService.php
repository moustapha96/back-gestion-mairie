<?php


namespace App\services;

use App\Entity\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ConfigurationService
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function get(string $key)
    {
        $config = $this->entityManager
            ->getRepository(Configuration::class)
            ->findOneBy(['cle' => $key]);

        if (!$config) {
            throw new Exception(sprintf('Configuration "%s" not found.', $key));
        }

        return $config->getValeur();
    }
}
