<?php


namespace App\Controller;

use App\EventListener\DemandeTerrainListener;
use App\Repository\DemandeTerrainRepository;
use App\Repository\DocumentGenereRepository;
use App\Repository\LocaliteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{

    #[Route('/document/{documentId}/signature', name: 'app_document_signer', methods: ['GET'])]
    public function signerDocument(int $documentId, Request $request, DocumentGenereRepository $documentGenereRepository): Response
    {
        $document = $documentGenereRepository->find($documentId);

        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        $user = $this->getUser();  // Récupérer l'utilisateur connecté

        $signature = $request->request->get('signature');  // Signature envoyée via une requête

        // Ajouter la signature au document
        $ordre = count($document->getSignatures()) + 1;  // Ordre basé sur le nombre de signatures existantes
        $document->ajouterSignature($user, $signature, $ordre);

        $documentGenereRepository->save($document);

        return new Response('Document signé', Response::HTTP_OK);
    }



    #[Route('/api/document/liste', name: 'api_document_liste', methods: ['GET'])]
    public function documentsUtilisateurs(
        int $id,
        UserRepository $userRepository,
        DemandeTerrainRepository $demandeTerrainRepository,
    ): Response {
        // Vérifier si l'utilisateur existe
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json(
                [
                    'message' => 'Utilisateur non trouvé'
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        // Récupérer les demandes avec les documents générés
        $demandes = $demandeTerrainRepository->findBy(['utilisateur' => $id]);

        $documents = [];
        foreach ($demandes as $demande) {
            $document = $demande->getDocumentGenerer();
            if ($document) {
                $documents[] = [
                    'id' => $document->getId(),
                    'type' => $document->getType(),
                    'dateCreation' => $document->getDateCreation()->format('Y-m-d H:i:s'),
                    'contenu' => $document->getContenu(),
                    'demande' => [
                        'id' => $demande->getId(),
                        'typeDemande' => $demande->getTypeDemande(),
                        'statut' => $demande->getStatut(),
                        'dateCreation' => $demande->getDateCreation()->format('Y-m-d H:i:s'),
                        'superficie' => $demande->getSuperficie(),
                        'usagePrevu' => $demande->getUsagePrevu(),
                        'localisation' => $demande->getLocalisation()
                    ],
                    'utilisateur' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'nom' => $user->getNom(),
                        'prenom' => $user->getPrenom()
                    ]
                ];
            }
        }

        return $this->json([
            'count' => count($documents),
            'documents' => $documents
        ], Response::HTTP_OK);
    }

    // liste des document d'un utilisateur donné
    #[Route('/api/document/user/{id}/liste', name: 'api_document_user_liste', methods: ['GET'])]
    public function documentsUtilisateur(
        int $id,
        UserRepository $userRepository,
        DemandeTerrainRepository $demandeTerrainRepository,
        LocaliteRepository $localiteRepository
    ): Response {

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        // Récupérer les demandes avec les documents générés
        $demandes = $demandeTerrainRepository->findBy(['utilisateur' => $id]);

        $documents = [];
        foreach ($demandes as $demande) {
            $document = $demande->getDocumentGenerer();
            $localite = $localiteRepository->find($demande->getLocalite()->getId());
            if ($document) {
                $documents[] = [
                    'id' => $document->getId(),
                    'type' => $document->getType(),
                    'dateCreation' => $document->getDateCreation()->format('Y-m-d H:i:s'),
                    'contenu' => $document->getContenu(),
                    'demande' => [
                        'id' => $demande->getId(),
                        'typeDemande' => $demande->getTypeDemande(),
                        'statut' => $demande->getStatut(),
                        'dateCreation' => $demande->getDateCreation()->format('Y-m-d H:i:s'),
                        'superficie' => $demande->getSuperficie(),
                        'usagePrevu' => $demande->getUsagePrevu(),
                        'localite' => $localite ? $localite->toArray() : null,
                    ]
                ];
            }
        }

        return $this->json($documents, Response::HTTP_OK);
    }

    // document/liste

    #[Route('/api/document/liste', name: 'api_document_liste', methods: ['GET'])]
    public function documents(
        UserRepository $userRepository,
        DemandeTerrainRepository $demandeTerrainRepository,
        LocaliteRepository $localiteRepository
    ): Response {


        // Récupérer les demandes avec les documents générés
        $demandes = $demandeTerrainRepository->findAll();

        $documents = [];
        foreach ($demandes as $demande) {
            $document = $demande->getDocumentGenerer();
            $localite = $localiteRepository->find($demande->getLocalite()->getId());

            if ($document) {
                $documents[] = [
                    'id' => $document->getId(),
                    'type' => $document->getType(),
                    'dateCreation' => $document->getDateCreation()->format('Y-m-d H:i:s'),
                    'contenu' => $document->getContenu(),
                    'demande' => [
                        'id' => $demande->getId(),
                        'typeDemande' => $demande->getTypeDemande(),
                        'statut' => $demande->getStatut(),
                        'dateCreation' => $demande->getDateCreation()->format('Y-m-d H:i:s'),
                        'superficie' => $demande->getSuperficie(),
                        'usagePrevu' => $demande->getUsagePrevu(),
                        'localite' => $localite ? $localite->toArray() : null,
                    ],
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
                ];
            }
        }

        return $this->json($documents, Response::HTTP_OK);
    }
}
