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
    #[Route('/api/configurations/liste', name: 'api_get_configurations_liste', methods: ['GET'])]
    public function getConfigurations(ConfigurationRepository $repository): JsonResponse
    {
        $configurations = $repository->findAll();
        $resultat = [];
        for ($i = 0; $i < count($configurations); $i++) {
            if ($configurations[$i]->getCle() == "title_1") {

                $resultat[] = [
                    'title_1' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "title_2") {

                $resultat[] = [
                    'title_2' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "email") {

                $resultat[] = [
                    'email' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "name") {

                $resultat[] = [
                    'name' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "tel") {

                $resultat[] = [
                    'tel' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "linkedin") {

                $resultat[] = [
                    'linkedin' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "twitter") {

                $resultat[] = [
                    'twitter' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "instagram") {

                $resultat[] = [
                    'instagram' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "prixDemandeur") {

                $resultat[] = [
                    'prixDemandeur' => $configurations[$i]->getValeur(),
                ];
            } elseif ($configurations[$i]->getCle() == "prixInstitut") {

                $resultat[] = [
                    'prixInstitut' => $configurations[$i]->getValeur(),
                ];
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
            if ($value->getCle() == "name") {
                $datas['name'] = $value->getValeur();
            } elseif ($value->getCle() == "email") {
                $datas['email'] = $value->getValeur();
            } elseif ($value->getCle() == "tel") {
                $datas['tel'] = $value->getValeur();
            } elseif ($value->getCle() == "linkedin") {
                $datas['linkedin'] = $value->getValeur();
            } elseif ($value->getCle() == "twitter") {
                $datas['twitter'] = $value->getValeur();
            } elseif ($value->getCle() == "instagram") {
                $datas['instagram'] = $value->getValeur();
            }
        }
        return  new JsonResponse($datas, 200, []);
    }



    // #[Route('/api/configurations/update/{cle}', name: 'update_configuration', methods: ['POST'])]
    // public function updateConfiguration($cle, Request $request, ConfigurationRepository $confRepo, EntityManagerInterface $em): JsonResponse
    // {

    //     $data = $request->request->all();
    //     foreach ($data as $key => $value) {
    //         $a_modifier = $confRepo->findOneBy(['cle' => $key]);
    //         if ($a_modifier) {
    //             $a_modifier->setValeur($value);
    //             $em->persist($a_modifier);
    //         } else {
    //             return $this->json(['error' => "Configuration not found for key: $key"], 404);
    //         }
    //     }

    //     // Enregistrez les modifications
    //     $em->flush();

    //     // Retourner la configuration mise à jour
    //     return $this->json('success', 200, [], ['groups' => 'read']);
    // }


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


    #[Route('/api/configurations/update', name: 'update_configuration', methods: ['POST'])]
    public function updateConfiguration(Request $request, ConfigurationRepository $confRepo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        foreach ($data as $key => $value) {
            $a_modifier = $confRepo->findOneBy(['cle' => $key]);
            if ($a_modifier) {
                $a_modifier->setValeur($value);
                $em->persist($a_modifier);
            } else {
                return $this->json(['error' => "Configuration not found for key: $key"], 404);
            }
        }

        $em->flush();

        return $this->json('success', 200, [], ['groups' => 'read']);
    }
}
