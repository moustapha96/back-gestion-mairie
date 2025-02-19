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
            new TwigFunction('get_logo1', [$this->dataConfigurationService, 'getLogo1']),
            new TwigFunction('get_logo2', [$this->dataConfigurationService, 'getLogo2']),
            new TwigFunction('get_name', [$this->dataConfigurationService, 'getName']),
            new TwigFunction('get_tel', [$this->dataConfigurationService, 'getTel']),
            new TwigFunction('get_email', [$this->dataConfigurationService, 'getEmail']),
            new TwigFunction('get_title1', [$this->dataConfigurationService, 'getTitle1']),
            new TwigFunction('get_title2', [$this->dataConfigurationService, 'getTitle2']),
            new TwigFunction('get_linkedin', [$this->dataConfigurationService, 'getLinkedin']),
            new TwigFunction('get_twitter', [$this->dataConfigurationService, 'getTwitter']),
            new TwigFunction('get_instagram', [$this->dataConfigurationService, 'getInstagram']),
            new TwigFunction('get_youtube', [$this->dataConfigurationService, 'getYoutube']),
        ];
    }
}
