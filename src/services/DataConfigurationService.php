<?php


namespace App\services;

use App\Repository\ConfigurationRepository;

class DataConfigurationService
{
    private $configurationRepository;

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }


    public function getEmail(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'email'])->getValeur();
    }
    public function getLinkedin(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'linkedin'])->getValeur();
    }

    public function getTwitter(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'twitter'])->getValeur();
    }
    public function getInstagram(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'instagram'])->getValeur();
    }
    public function getYoutube(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'youtube'])->getValeur();
    }




    public function getTitre(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'titre'])->getValeur();
    }

    public function getNomMaire(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'nomMaire'])->getValeur();
    }

    public function getTelephone(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'telephone'])->getValeur();
    }

    public function getSiteWeb(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'siteWeb'])->getValeur();
    }

    public function getAdresse(): ?string
    {
        return $this->configurationRepository->findOneBy(['cle' => 'adresse'])->getValeur();
    }
}
