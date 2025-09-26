<?php

namespace App\Controller;

use App\Entity\DocumentGenere;
use App\Repository\DemandeTerrainRepository;
use App\Repository\DocumentGenereRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
class GenerationDocumentController extends AbstractController
{
    private $pdfGenerator;
    private $entityManager;
    private $logger;

    public function __construct(
        Pdf $pdfGenerator,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->pdfGenerator = $pdfGenerator;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/api/document/{id}/generate', name: 'api_document_generate_with_data', methods: ['POST'])]
    public function generateDocument(
        int $id,
        Request $request,
        DemandeTerrainRepository $demandeTerrainRepository,
        DocumentGenereRepository $documentGenereRepository
    ): JsonResponse {
        try {
            // Find the request
            $demande = $demandeTerrainRepository->find($id);
            if (!$demande) {
                return new JsonResponse([
                    'error' => 'Demande non trouvée'
                ], Response::HTTP_NOT_FOUND);
            }

            // Get and validate JSON data
            $data = json_decode($request->getContent(), true);
            if (!$data) {
                return new JsonResponse([
                    'error' => 'Données JSON invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            if (!isset($data['typeDemande'])) {
                return new JsonResponse([
                    'error' => 'Type de demande manquant'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create or get existing DocumentGenere
            $documentGenere = $demande->getDocumentGenerer();
            if (!$documentGenere) {
                $documentGenere = new DocumentGenere();
                $documentGenere->setDemandeTerrain($demande);
                $demande->setDocumentGenerer($documentGenere);
            }

            // Set document type and content
            $documentGenere->setType($data['typeDemande']);
            $documentGenere->setContenu($data);
            $documentGenere->setDateCreation(new \DateTime());

            // Generate filename
            $filename = $this->generateFilename($data);

            $logoMairiePath =  'https://glotissement.kaolackcommune.sn/logo.png';
            $logoSenegalPath =  'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fd/Flag_of_Senegal.svg/2560px-Flag_of_Senegal.svg.png';

            // Prepare template data
            $templateData = [
                'data' => $data,
                'demande' => $demande,
                'date' => new \DateTime(),
                'logoMairie' => $logoMairiePath,
                'logoSenegal' => $logoSenegalPath,
            ];

            // Generate HTML content based on document type
            $templateName = $this->getTemplateName($data['typeDemande']);
            $html = $this->renderView($templateName, [
                'data' => $data,
                'demande' => $demande,
                'date' => new \DateTime(),
                'logoMairie' => 'https://glotissement.kaolackcommune.sn/logo.png',
                'logoSenegal' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fd/Flag_of_Senegal.svg/2560px-Flag_of_Senegal.svg.png',
            ]);

            // ⚡ Utilisation de Dompdf
            $options = new Options();
            $options->set('defaultFont', 'Arial');
            $options->set('isRemoteEnabled', true); // Pour charger des images externes

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $newFilename = $filename . '-' . uniqid() . '.pdf';
            $documentDirectory = $this->getParameter('document_generer_directory');
            if (!file_exists($documentDirectory)) {
                mkdir($documentDirectory, 0777, true);
            }

            $fullPath = $documentDirectory . '/' . $newFilename;
            file_put_contents($fullPath, $dompdf->output());

            $documentGenere->setFichier( $newFilename); 
            $documentGenere->setIsGenerated(true);

            $this->entityManager->persist($documentGenere);
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Document généré avec succès',
                'data' => [
                    'id' => $documentGenere->getId(),
                    'filename' => $newFilename,
                    'type' => $documentGenere->getType(),
                    'dateCreation' => $documentGenere->getDateCreation()->format('Y-m-d H:i:s'),
                    'path' => '/documents/' . $newFilename,
                    'size' => filesize($fullPath),
                    'isGenerated' => true
                ]
            ], Response::HTTP_CREATED);

            // $html = $this->renderView($templateName, $templateData);

            // // Configure PDF options
            // $options = [
            //     'page-size' => 'A4',
            //     'margin-top' => '0.75in',
            //     'margin-right' => '0.75in',
            //     'margin-bottom' => '0.75in',
            //     'margin-left' => '0.75in',
            //     'encoding' => 'UTF-8',
            //     'enable-local-file-access' => true,
            //     'javascript-delay' => 1000,
            //     'no-stop-slow-scripts' => true,
            // ];

            // // Generate PDF content
            // $pdfContent = $this->pdfGenerator->getOutputFromHtml($html, $options);

            // // Create unique filename
            // $newFilename = $filename . '-' . uniqid() . '.pdf';

            // // Ensure document directory exists
            // $documentDirectory = $this->getParameter('document_generer_directory');
            // if (!file_exists($documentDirectory)) {
            //     mkdir($documentDirectory, 0777, true);
            // }

            // // Save PDF file
            // $fullPath = $documentDirectory . '/' . $newFilename;
            // $bytesWritten = file_put_contents($fullPath, $pdfContent);

            // if ($bytesWritten === false) {
            //     throw new \Exception('Impossible d\'écrire le fichier PDF');
            // }
            // // Update document entity
            // $documentGenere->setFichier($fullPath);
            // $documentGenere->setIsGenerated(true);

            // // Save to database
            // $this->entityManager->persist($documentGenere);
            // $this->entityManager->flush();

            // // Log success
            // $this->logger->info('Document généré avec succès', [
            //     'demande_id' => $id,
            //     'document_id' => $documentGenere->getId(),
            //     'filename' => $newFilename,
            //     'type' => $data['typeDemande']
            // ]);

            // return new JsonResponse([
            //     'success' => true,
            //     'message' => 'Document généré avec succès',
            //     'data' => [
            //         'id' => $documentGenere->getId(),
            //         'filename' => $newFilename,
            //         'type' => $documentGenere->getType(),
            //         'dateCreation' => $documentGenere->getDateCreation()->format('Y-m-d H:i:s'),
            //         'path' => '/documents/' . $newFilename,
            //         'size' => filesize($fullPath),
            //         'isGenerated' => true
            //     ]
            // ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            // Log error
            $this->logger->error('Erreur lors de la génération du document', [
                'demande_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'error' => 'Erreur lors de la génération du document: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate filename based on document type and data
     */
    private function generateFilename(array $data): string
    {
        $type = $data['typeDemande'] ?? 'DOCUMENT';
        $date = date('Y-m-d');

        switch ($type) {
            case 'PERMIS_OCCUPATION':
                $prefix = 'PO';
                break;
            case 'BAIL_COMMUNAL':
                $prefix = 'BC';
                break;
            case 'PROPOSITION_BAIL':
                $prefix = 'PB';
                break;
            default:
                $prefix = 'DOC';
        }

        $beneficiaire = '';
        if (isset($data['beneficiaire']['nom']) && isset($data['beneficiaire']['prenom'])) {
            $beneficiaire = '-' . strtoupper($data['beneficiaire']['nom']) . '_' . strtoupper($data['beneficiaire']['prenom']);
            $beneficiaire = preg_replace('/[^A-Z0-9_-]/', '', $beneficiaire);
        }

        return $prefix . '-' . $date . $beneficiaire;
    }

    /**
     * Get template name based on document type
     */
    private function getTemplateName(string $type): string
    {
        switch ($type) {
            case 'PERMIS_OCCUPATION':
                return 'document/permis_occupation.html.twig';
            case 'BAIL_COMMUNAL':
                return 'document/bail_communal.html.twig';
            case 'PROPOSITION_BAIL':
                return 'document/proposition_bail.html.twig';
            default:
                return 'document/template.html.twig';
        }
    }

    /**
     * Download generated document
     */
    #[Route('/api/document/{id}/download', name: 'api_document_download', methods: ['GET'])]
    public function downloadDocument(
        int $id,
        DocumentGenereRepository $documentGenereRepository
    ): Response {
        try {
            $document = $documentGenereRepository->find($id);

            if (!$document) {
                return new JsonResponse([
                    'error' => 'Document non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$document->isGenerated() || !$document->getFichier()) {
                return new JsonResponse([
                    'error' => 'Document non généré'
                ], Response::HTTP_NOT_FOUND);
            }

            $documentDirectory = $this->getParameter('document_generer_directory');
            $filePath = $documentDirectory . '/' . $document->getFichier();

            if (!file_exists($filePath)) {
                return new JsonResponse([
                    'error' => 'Fichier non trouvé sur le serveur'
                ], Response::HTTP_NOT_FOUND);
            }

            $response = new Response(file_get_contents($filePath));
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $document->getFichier() . '"');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du téléchargement du document', [
                'document_id' => $id,
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'error' => 'Erreur lors du téléchargement'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get document details
     */
    #[Route('/api/document/{id}/details', name: 'api_document_details', methods: ['GET'])]
    public function getDocumentDetails(
        int $id,
        DocumentGenereRepository $documentGenereRepository
    ): JsonResponse {
        try {
            $document = $documentGenereRepository->find($id);

            if (!$document) {
                return new JsonResponse([
                    'error' => 'Document non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            $documentDirectory = $this->getParameter('document_generer_directory');
            $filePath = $documentDirectory . '/' . $document->getFichier();
            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $document->getId(),
                    'type' => $document->getType(),
                    'contenu' => $document->getContenu(),
                    'dateCreation' => $document->getDateCreation()->format('Y-m-d H:i:s'),
                    'fichier' => $document->getFichier(),
                    'isGenerated' => $document->isGenerated(),
                    'fileSize' => $fileSize,
                    'downloadUrl' => $this->generateUrl('api_document_download', ['id' => $document->getId()])
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la récupération des détails'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
