<?php

namespace App\Twig;

use App\services\DataConfigurationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DataConfigurationExtension extends AbstractExtension
{
    private $dataConfigurationService;

    public function __construct(DataConfigurationService $dataConfigurationService)
    {
        $this->dataConfigurationService = $dataConfigurationService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_titre', [$this->dataConfigurationService, 'getTitre']),
            new TwigFunction('get_nom_maire', [$this->dataConfigurationService, 'getNomMaire']),
            new TwigFunction('get_telephone', [$this->dataConfigurationService, 'getTelephone']),
            new TwigFunction('get_email', [$this->dataConfigurationService, 'getEmail']),
            new TwigFunction('get_site_web', [$this->dataConfigurationService, 'getSiteWeb']),
            new TwigFunction('get_adresse', [$this->dataConfigurationService, 'getAdresse']),
        ];
    }
}
