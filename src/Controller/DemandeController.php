<?php

namespace App\Controller;

use App\Entity\DemandeTerrain;
use App\Repository\DemandeTerrainRepository;
use App\Repository\LocaliteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemandeController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    // methode pour creer demande
    // méthode pour créer une demande
    #[Route('/api/demande/create', name: 'api_demande_create', methods: ['POST'])]
    public function createDemande(
        Request $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository
    ): Response {
        $data = json_decode($request->getContent(), true);

        $userId = $request->request->get('userId') ?? $data['userId'] ?? null;
        $superficie = $request->request->get('superficie') ?? $data['superficie'] ?? null;
        $usagePrevu = $request->request->get('usagePrevu') ?? $data['usagePrevu'] ?? null;
        $localiteId = $request->request->get('localiteId') ?? $data['localiteId'] ?? null;
        $typeDemande = $request->request->get('typeDemande') ?? $data['typeDemande'] ?? null;
        $typeDocument = $request->request->get('typeDocument') ?? $data['typeDocument'] ?? null;
        $possedeAutreTerrain = $request->request->get('possedeAutreTerrain') ?? $data['possedeAutreTerrain'] ?? false;

        if (!$typeDemande || !$superficie || !$userId || !$localiteId) {
            return $this->json(['message' => 'Données manquantes ou invalides'], Response::HTTP_BAD_REQUEST);
        }

        $utilisateur = $userRepository->find($userId);
        $localite = $localiteRepository->find($localiteId);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if (!$localite) {
            return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Créer la demande de terrain
        $demande = new DemandeTerrain();
        $demande
            ->setSuperficie($superficie)
            ->setTypeDemande($typeDemande)
            ->setUsagePrevu($usagePrevu)
            ->setUtilisateur($utilisateur)
            ->setTypeDocument($typeDocument)
            ->setDateCreation(new \DateTime())
            ->setStatut(DemandeTerrain::STATUT_EN_COURS)
            ->setPossedeAutreTerrain($possedeAutreTerrain)
            ->setLocalite($localite);

        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');
        if ($file) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                (new \DateTime())->format('Y-m-d'),
                $utilisateur ? str_replace(' ', '-', strtolower($utilisateur->getEmail())) : date('YmdHis'),
                $file->guessExtension()
            );
            $file->move($this->getParameter('document_directory'), $newFilename);
            $url = $this->getParameter('document_directory') . "/" . $newFilename;
            $demande->setDocument($url);
        } else {
            return $this->json(['message' => 'Veuillez uploader un document'], Response::HTTP_BAD_REQUEST);
        }
        $this->em->persist($demande);
        $this->em->flush();

        return $this->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->toArray()
        ], Response::HTTP_CREATED);
    }


    // liste des tout les demandes
    #[Route('/api/demande/liste', name: 'api_demande_liste', methods: ['GET'])]
    public function listeDemande(DemandeTerrainRepository $demandeRepository): Response
    {
        $demandes = $demandeRepository->findAll();
        $resultats = [];

        foreach ($demandes as $demande) {
            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'document' => $demande->getDocument(),
                'demandeur' => $demande->getUtilisateur() ? [
                    'id' => $demande->getUtilisateur()->getId(),
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'email' => $demande->getUtilisateur()->getEmail(),
                    'telephone' => $demande->getUtilisateur()->getTelephone(),
                    'lieuNaissance' => $demande->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance(),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
                ] : null,
                'localite' => $demande->getLocalite() ? [
                    'id' => $demande->getLocalite()->getId(),
                    'nom' => $demande->getLocalite()->getNom(),
                ] : null,
                'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null,
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }

    // liste des demandes d'un demandeur
    #[Route('/api/demandes/demandeur/{id}', name: 'api_demande_demandeur', methods: ['GET'])]
    public function demandeDemandeur(int $id, DemandeTerrainRepository $demandeRepository, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user)
            return new Response(json_encode(['message' => 'Utilisateur non trouvé']), Response::HTTP_NOT_FOUND, ['Content-Type' => 'application/json']);

        $demandes = $demandeRepository->findBy(['utilisateur' => $user]);

        $resultats = [];
        if (count($demandes) == 0)
            return $this->json($resultats, Response::HTTP_OK, ['Content-Type' => 'application/json']);


        foreach ($demandes as $demande) {
            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'document' => $demande->getDocument(),
                'demandeur' => $demande->getUtilisateur() ? [
                    'id' => $demande->getUtilisateur()->getId(),
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'email' => $demande->getUtilisateur()->getEmail(),
                    'telephone' => $demande->getUtilisateur()->getTelephone(),
                    'lieuNaissance' => $demande->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance()?->format('Y-m-d H:i:s'),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
                ] : null,
                'localite' => $demande->getLocalite() ? [
                    'id' => $demande->getLocalite()->getId(),
                    'nom' => $demande->getLocalite()->getNom(),
                ] : null
            ];
        }

        return $this->json($resultats, Response::HTTP_OK);
    }

    // liste des demandes en cours de traitement

    #[Route('/api/demandes/traitement', name: 'api_demande_traitement', methods: ['GET'])]
    public function demandeTraitement(DemandeTerrainRepository $demandeRepository): Response
    {
        $demandes = $demandeRepository->findBy(['statut' => 'En cours']);
        $resultats = [];
        foreach ($demandes as $demande) {
            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'document' => $demande->getDocument(),
                'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null,
                'demandeur' => $demande->getUtilisateur() ? [
                    'id' => $demande->getUtilisateur()->getId(),
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'email' => $demande->getUtilisateur()->getEmail(),
                    'telephone' => $demande->getUtilisateur()->getTelephone(),
                    'lieuNaissance' => $demande->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance()?->format('Y-m-d H:i:s'),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
                ] : null,
                'localite' => $demande->getLocalite() ? $demande->getLocalite()->toArray() : null
            ];
        }

        return $this->json($resultats, Response::HTTP_OK);
    }

    // get demandes by id user
    #[Route('/api/demande/user/{id}/liste', name: 'api_demande_user', methods: ['GET'])]
    public function demandeUser(int $id, DemandeTerrainRepository $demandeRepository, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return new Response(
                json_encode(['message' => 'Utilisateur non trouvé']),
                Response::HTTP_NOT_FOUND,
                ['Content-Type' => 'application/json']
            );

        $demandes = $demandeRepository->findBy(['utilisateur' => $user]);
        $resultats = [];
        foreach ($demandes as $demande) {
            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'document' => $demande->getDocument(),
                'demandeur' => $demande->getUtilisateur() ? [
                    'id' => $demande->getUtilisateur()->getId(),
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'email' => $demande->getUtilisateur()->getEmail(),
                    'telephone' => $demande->getUtilisateur()->getTelephone(),
                    'lieuNaissance' => $demande->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance()?->format('Y-m-d H:i:s'),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
                ] : null,
                'localite' => $demande->getLocalite() ? $demande->getLocalite()->toArray() : null,
                'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null
            ];
        }

        return $this->json($resultats, Response::HTTP_OK);
    }
}
