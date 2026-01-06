<?php
namespace App\services;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\IOFactory;


use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment as Twig;

final class NotificationAttributionGenerator
{



    public function __construct(
        private string $projectDir,
        private Twig $twig,
    ) {
    }

    /**
     * @return array{docx:string, pdf:?string, publicDocx:string, publicPdf:?string}
     */
    public function generate2(array $data): array
    {
        $template = $this->projectDir . '/templates/NOTIFICATION_ATTRIBUTION.docx';
        if (!is_file($template)) {
            throw new \RuntimeException('Modèle DOCX introuvable');
        }

        $demandeId = (string) ($data['demandeId'] ?? 'unk');
        $code = (string) ($data['demandeCode'] ?? $demandeId);
        $fileBase = "notification_attribution_{$code}";

        $dirFs = $this->projectDir . "/public/uploads/demandes/{$demandeId}";
        $dirWeb = "/uploads/demandes/{$demandeId}";
        @mkdir($dirFs, 0775, true);

        // ---- DOCX
        $docxPath = "{$dirFs}/{$fileBase}.docx";
        $tp = new TemplateProcessor($template);
        $tp->setValues([
            'MAIRIE_NOM' => $data['mairieNom'] ?? 'Commune de Kaolack',
            'DEMANDEUR_NOM_COMPLET' => $data['demandeurNomComplet'] ?? '',
            'DEMANDEUR_ADRESSE' => $data['demandeurAdresse'] ?? '',
            'DEMANDEUR_CNI' => $data['demandeurCni'] ?? '',
            'LOT_NUMERO' => $data['lotNumero'] ?? '',
            'LOTISSEMENT_NOM' => $data['lotissementNom'] ?? '',
            'TITRE_FONCIER' => $data['tf'] ?? '',
            'VILLE' => $data['ville'] ?? 'Kaolack',
            'DATE_REUNION' => $data['dateCommission'] ?? '',
            'DELAI_PAIEMENT_JOURS' => $data['delaiJours'] ?? '30',
            'DATE_DOCUMENT' => $data['dateDocument'] ?? (new \DateTime())->format('d/m/Y'),
            'MAIRE_NOM' => $data['maireNom'] ?? 'Le Maire',
        ]);
        $tp->saveAs($docxPath);

        // ---- PDF (optionnel, recommandé)
        $pdfPath = null;
        try {
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath($this->projectDir . '/vendor/dompdf/dompdf');
            $phpWord = IOFactory::load($docxPath);
            $writer = IOFactory::createWriter($phpWord, 'PDF');
            $pdfPath = "{$dirFs}/{$fileBase}.pdf";
            $writer->save($pdfPath);
        } catch (\Throwable) {
            $pdfPath = null; // pas bloquant si le PDF échoue
        }

        return [
            'docx' => $docxPath,
            'pdf' => $pdfPath,
            'publicDocx' => "{$dirWeb}/{$fileBase}.docx",
            'publicPdf' => $pdfPath ? "{$dirWeb}/{$fileBase}.pdf" : null,
        ];
    }


    /**
     * @return array{pdf:string, publicPdf:string}
     */
    public function generate(array $data): array
    {
        // Dossier/nom de sortie
        $demandeId = (string) ($data['demandeId'] ?? 'unk');
        $code = (string) ($data['demandeCode'] ?? $demandeId);
        $fileBase = "notification_attribution_{$code}";

        $dirFs = $this->projectDir . "/public/uploads/demandes/{$demandeId}";
        $dirWeb = "/uploads/demandes/{$demandeId}";
        @mkdir($dirFs, 0775, true);

        $pdfPath = "{$dirFs}/{$fileBase}.pdf";
        $public = "{$dirWeb}/{$fileBase}.pdf";

          $logoMairiePath =  'https://glotissement.kaolackcommune.sn/logo.png';
            $logoSenegalPath =  'https://upload.wikimedia.org/wikipedia/commons/thumb/f/fd/Flag_of_Senegal.svg/2560px-Flag_of_Senegal.svg.png';

        // 1) Render HTML avec Twig (template ci-dessous)
        $html = $this->twig->render('pdf/notification_attribution.html.twig', [
            // clé => valeur (mêmes noms que ton ancien service DOCX)
            'MAIRIE_NOM' => $data['mairieNom'] ?? 'Commune de Kaolack',
            'MAIRE_NOM' => $data['maireNom'] ?? 'Le Maire',
            'DEMANDEUR_NOM_COMPLET' => $data['demandeurNomComplet'] ?? '',
            'DEMANDEUR_ADRESSE' => $data['demandeurAdresse'] ?? '',
            'DEMANDEUR_CNI' => $data['demandeurCni'] ?? '',
            'LOT_NUMERO' => $data['lotNumero'] ?? '',
            'LOTISSEMENT_NOM' => $data['lotissementNom'] ?? '',
            'TITRE_FONCIER' => $data['tf'] ?? '',
            'VILLE' => $data['ville'] ?? 'Kaolack',
            'DATE_REUNION' => $data['dateCommission'] ?? '',
            'DELAI_PAIEMENT_JOURS' => $data['delaiJours'] ?? '30',
            'DATE_DOCUMENT' => $data['dateDocument'] ?? (new \DateTime())->format('d/m/Y'),
            'TYPE_ATTRIBUTION' => $data['typeAttribution'] ?? 'définitive', // “provisoire” / “définitive”
            // Assets (logos) si besoin :
            'public_base' => $this->projectDir . '/public',

            'logoMairie'    => $logoMairiePath   ?? null,
            'logoSenegal'   =>  $logoSenegalPath  ?? null,
        ]);

        // 2) Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);      // autoriser images http(s)
        $options->set('chroot', $this->projectDir);  // sandbox sur le projet
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        // 3) Sauvegarde
        file_put_contents($pdfPath, $dompdf->output());

        return ['pdf' => $pdfPath, 'publicPdf' => $public];
    }


    public function generateLiquidation(array $data): array
    {
        $demandeId = (string)($data['demandeId'] ?? 'unk');
        $code      = (string)($data['demandeCode'] ?? $demandeId);
        $fileBase  = "bulletin_liquidation_{$code}";

        $dirFs  = $this->projectDir."/public/uploads/demandes/{$demandeId}";
        $dirWeb = "/uploads/demandes/{$demandeId}";
        @mkdir($dirFs, 0775, true);

        $pdfPath  = "{$dirFs}/{$fileBase}.pdf";
        $public   = "{$dirWeb}/{$fileBase}.pdf";

        $html = $this->twig->render('pdf/bulletin_liquidation.html.twig', [
            // En-tête
            'COMMUNE_NOM'      => $data['commune']           ?? 'KAOLACK',
            'BULLETIN_NUM'     => $data['bulletinNum']       ?? '',
            'CODE_SERVICE'     => $data['codeService']       ?? 'CKSRAD',
            'BULLETIN_REGISTRE'=> $data['bulletinRegistre']  ?? '',

            // Corps
            'NOM_PRENOMS'      => $data['nomPrenoms']        ?? '',
            'LOTISSEMENT'      => $data['lotissement']       ?? '',
            'PARCELLE_NUM'     => $data['parcelleNum']       ?? '',
            'MONTANT'          => $data['montant']           ?? '',

            // Pied
            'MONTANT_EN_LETTRES'=> $data['montantLettres']   ?? '',
            'VILLE'             => $data['ville']            ?? 'Kaolack',
            'DATE_DOCUMENT'     => $data['dateDocument']     ?? (new \DateTime())->format('d/m/Y'),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', $this->projectDir);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        file_put_contents($pdfPath, $dompdf->output());

        return ['pdf' => $pdfPath, 'publicPdf' => $public];
    }
}
