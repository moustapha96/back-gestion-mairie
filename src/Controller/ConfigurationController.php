<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Repository\ConfigurationRepository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class ConfigurationController extends AbstractController
{

    private function initDefaultConfigurations(EntityManagerInterface $em): void
    {
        $defaultConfigs = [
            'titre' => '',
            'adresse' => '',
            'telephone' => '',
            'siteWeb' => '',
            'email' => '',
            'nomMaire' => ''
        ];

        foreach ($defaultConfigs as $cle => $valeur) {
            $configuration = $em->getRepository(Configuration::class)->findOneBy(['cle' => $cle]);
            if (!$configuration) {
                $configuration = new Configuration();
                $configuration->setCle($cle);
                $configuration->setValeur($valeur);
                $em->persist($configuration);
            }
        }
        $em->flush();
    }

    #[Route('/api/configurations/liste', name: 'api_get_configurations_liste', methods: ['GET'])]
    public function getConfigurations(ConfigurationRepository $repository, EntityManagerInterface $em): JsonResponse
    {
        // Initialiser les configurations par défaut si elles n'existent pas
        $this->initDefaultConfigurations($em);

        $configurations = $repository->findAll();
        $resultat = [];

        foreach ($configurations as $config) {
            switch ($config->getCle()) {
                case 'titre':
                    $resultat['titre'] = $config->getValeur();
                    break;
                case 'adresse':
                    $resultat['adresse'] = $config->getValeur();
                    break;
                case 'telephone':
                    $resultat['telephone'] = $config->getValeur();
                    break;
                case 'siteWeb':
                    $resultat['siteWeb'] = $config->getValeur();
                    break;
                case 'email':
                    $resultat['email'] = $config->getValeur();
                    break;
                case 'nomMaire':
                    $resultat['nomMaire'] = $config->getValeur();
                    break;
            }
        }

        return $this->json($resultat, 200);
    }

    #[Route('/api/configurations/liste-simple', name: 'api_get_configurations_liste_simple', methods: ['GET'])]
    public function getSimpleConfiguration(ConfigurationRepository $configurationRepository): JsonResponse
    {
        $data = $configurationRepository->findAll();
        $datas = [];
        foreach ($data as $key => $value) {
            if ($value->getCle() == "titre") {
                $datas['titre'] = $value->getValeur();
            } elseif ($value->getCle() == "email") {
                $datas['email'] = $value->getValeur();
            } elseif ($value->getCle() == "telephone") {
                $datas['telephone'] = $value->getValeur();
            } elseif ($value->getCle() == "adresse") {
                $datas['adresse'] = $value->getValeur();
            } elseif ($value->getCle() == "siteWeb") {
                $datas['siteWeb'] = $value->getValeur();
            } elseif ($value->getCle() == "nomMaire") {
                $datas['nomMaire'] = $value->getValeur();
            }
        }
        return  new JsonResponse($datas, 200, []);
    }




    #[Route('/api/configurations/update-logo', name: 'logo_update_configuration', methods: ['POST'])]
    public function updateConfigurationLogo(Request $request, ConfigurationRepository $confRepo, EntityManagerInterface $em): JsonResponse
    {
        $files = $request->files->all();

        if ($files) {
            foreach ($files as $key => $file) {
                $configuration = $confRepo->findOneBy(['cle' => $key]);

                if ($configuration) { // Vérifiez si la configuration existe
                    if ($key === "logo1" || $key === "logo2") {
                        $filename = $key === "logo1" ? 'logo_1_authentic_page.' : 'logo_2_authentic_page.';
                        $filename .= $file->guessExtension();

                        // Déplacez le fichier dans le bon répertoire
                        $file->move($this->getParameter('images_directory'), $filename);

                        // Mettez à jour la valeur de la configuration
                        $configuration->setValeur('http://localhost:8000/images/' . $filename);
                        $em->persist($configuration);
                    }
                } else {
                    return $this->json(['error' => "Configuration not found for key: $key"], 404);
                }
            }
            $em->flush();
        }

        return $this->json(['message' => 'Logos updated successfully'], 200);
    }


    #[Route('/api/configurations/update', name: 'update_configuration', methods: ['PUT'])]
    public function updateConfiguration(Request $request, ConfigurationRepository $confRepo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['cle']) || !isset($data['valeur'])) {
            return $this->json(['error' => 'Les champs cle et valeur sont requis'], 400);
        }

        $configuration = $confRepo->findOneBy(['cle' => $data['cle']]);

        if (!$configuration) {
            // Création d'une nouvelle configuration si elle n'existe pas
            $configuration = new Configuration();
            $configuration->setCle($data['cle']);
        }

        $configuration->setValeur($data['valeur']);
        $em->persist($configuration);
        $em->flush();

        return $this->json([
            'message' => 'Configuration updated successfully',
            'isNewConfiguration' => $configuration->getId() === null
        ], 200);
    }
}
