<?php


namespace App\Controller;

use App\Repository\DocumentGenereRepository;
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
}
