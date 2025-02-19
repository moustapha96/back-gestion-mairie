<?php

namespace App\Controller;

use App\Entity\Localite;
use App\Repository\LocaliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class LocaliteController extends AbstractController
{


    #[Route('/api/localite/liste', name: 'api_localite_liste', methods: ['GET'])]
    public function listeLocalite(LocaliteRepository $localiteRepository): Response
    {
        $localites = $localiteRepository->findAll();
        $resultats = [];

        foreach ($localites as $value) {
            $resultats[] = $value->toArray();
        }
        return $this->json($resultats, 200);
    }

    #[Route('/api/localite/{id}/details', name: 'api_localite_show', methods: ['GET'])]
    public function details(Localite $localite): Response
    {
        return $this->json($localite->toArray(), 200);
    }

    #[Route('/api/localite/create', name: 'api_localite_create', methods: ['POST'])]
    public function createLocalite(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $data = json_decode($request->getContent(), true);
        $localite = new Localite();
        $localite->setNom($data['nom'] ?? '');
        $localite->setPrix($data['prix'] ?? null);
        $localite->setDescription($data['description'] ?? null);

        $errors = $validator->validate($localite);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $em->persist($localite);
        $em->flush();

        return $this->json($localite->toArray(), 201);
    }

    #[Route('/api/localite/{id}/update', name: 'api_localite_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request,
        Localite $localite,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (isset($data['nom'])) {
            $localite->setNom($data['nom']);
        }
        if (isset($data['prix'])) {
            $localite->setPrix($data['prix']);
        }
        if (isset($data['description'])) {
            $localite->setDescription($data['description']);
        }

        $errors = $validator->validate($localite);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }
        $em->flush();
        return $this->json($localite, 200);
    }

    #[Route('/api/localite/{id}/delete', name: 'api_localite_delete', methods: ['DELETE'])]
    public function delete(Localite $localite, EntityManagerInterface $em): Response
    {
        $em->remove($localite);
        $em->flush();

        return $this->json(['message' => 'Localité supprimée avec succès'], 204);
    }
}
