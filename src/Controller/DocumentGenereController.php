<?php

namespace App\Controller;

use App\Entity\DocumentGenere;
use App\Repository\DemandeTerrainRepository;
use App\Repository\DocumentGenereRepository;
use App\services\PdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentGenereController extends AbstractController
{


    private $pdfGenerator;


    public function __construct(
        PdfGeneratorService $pdfGenerator
    ) {
        $this->pdfGenerator = $pdfGenerator;
    }


    #[Route('/api/document/{id}/generate', name: 'api_document_generate', methods: ['POST'])]
    public function generateDocument(
        Request $request,
        $id,
        DemandeTerrainRepository $demandeTerrainRepository,
        DocumentGenereRepository $documentGenereRepository
    ): JsonResponse {
        $demande = $demandeTerrainRepository->find($id);
        if (!$demande) {
            return new JsonResponse(['error' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }
        // Récupérer les données JSON
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Données JSON invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Générer un nom de fichier unique
        $filename = $this->generateFilename($data);

        $logoMairiePath = $this->getParameter('kernel.project_dir') . '/public/logo/logo_mairie.png';
        $logoSenegalPath = $this->getParameter('kernel.project_dir') . '/public/logo/logo_senegal.png';

        // Générer le contenu HTML pour le PDF
        $html = $this->renderView('document/template.html.twig', [
            'data' => $data,
            'date' => new \DateTime(),
            'logoMairie' => $logoMairiePath,
            'logoSenegal' => $logoSenegalPath,
        ]);

        // Générer le PDF à partir du HTML
        $pdfContent = $this->pdfGenerator->getOutputFromHtml($html);

        // Créer le nom du fichier
        $newFilename = $filename . '-' . uniqid() . '.pdf';

        // Sauvegarder le fichier dans le répertoire configuré
        $documentDirectory = $this->getParameter('document_generer_directory');
        $fullPath = $documentDirectory . '/';

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        // Sauvegarder le fichier
        file_put_contents($fullPath . '/' . $newFilename, $pdfContent);

        $documentGenere = $demande->getDocumentGenere();
        $documentGenere->setFichier($newFilename);
        $documentGenere->setIsGenerated(true);

        $documentGenereRepository->save($documentGenere);

        // Retourner le chemin du fichier
        return new JsonResponse([
            'success' => true,
            'message' => 'Document généré avec succès',
            'filename' => $newFilename,
            'path' => '/document_generer_directory/' . $newFilename
        ]);
    }

    /**
     * Génère un nom de fichier basé sur le type de document et les informations de la demande
     */
    private function generateFilename(array $data): string
    {
        $type = $data['typeDemande'] === 'PERMIS_OCCUPATION' ? 'permis_occupation' : 'bail_communal';
        $nom = $data['beneficiaire']['nom'];
        $prenom = $data['beneficiaire']['prenom'];
        $date = (new \DateTime())->format('Y-m-d');

        return sprintf('%s_%s_%s_%s', $type, $nom, $prenom, $date);
    }
}
