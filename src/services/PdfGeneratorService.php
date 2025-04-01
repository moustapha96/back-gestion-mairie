<?php

namespace App\services;

use Knp\Snappy\Pdf;
use Symfony\Component\HttpKernel\KernelInterface;

class PdfGeneratorService
{
    private $pdf;
    private $kernel;

    /**
     * Constructeur du service
     * 
     * @param Pdf $pdf Service Knp\Snappy\Pdf pour la génération de PDF
     * @param KernelInterface $kernel Interface du kernel Symfony
     */
    public function __construct(Pdf $pdf, KernelInterface $kernel)
    {
        $this->pdf = $pdf;
        $this->kernel = $kernel;

        // Assurez-vous que le chemin est correctement formaté pour Windows
        $binary = $this->kernel->getContainer()->getParameter('knp_snappy.pdf.binary');
        $this->pdf->setBinary(str_replace('\\', '/', $binary));
    }

    /**
     * Génère un PDF à partir de HTML
     * 
     * @param string $html Le contenu HTML à convertir en PDF
     * @param array $options Options supplémentaires pour la génération du PDF
     * 
     * @return string Le contenu binaire du PDF généré
     */
    public function getOutputFromHtml(string $html, array $options = []): string
    {
        // Options par défaut pour les documents officiels
        $defaultOptions = [
            'page-size' => 'A4',
            'margin-top' => '10mm',
            'margin-right' => '10mm',
            'margin-bottom' => '10mm',
            'margin-left' => '10mm',
            'encoding' => 'UTF-8',
            'images' => true,
            'enable-local-file-access' => true,
        ];

        // Fusionner les options par défaut avec les options fournies
        $options = array_merge($defaultOptions, $options);

        // Générer le PDF
        return $this->pdf->getOutputFromHtml($html, $options);
    }

    /**
     * Enregistre un PDF généré à partir de HTML dans un fichier
     * 
     * @param string $html Le contenu HTML à convertir en PDF
     * @param string $targetPath Le chemin où enregistrer le fichier PDF
     * @param array $options Options supplémentaires pour la génération du PDF
     * 
     * @return bool True si le fichier a été créé avec succès
     */
    public function saveFromHtml(string $html, string $targetPath, array $options = []): bool
    {
        try {
            $pdfContent = $this->getOutputFromHtml($html, $options);
            file_put_contents($targetPath, $pdfContent);
            return true;
        } catch (\Exception $e) {
            // Gérer l'erreur ou la journaliser
            return false;
        }
    }
}
