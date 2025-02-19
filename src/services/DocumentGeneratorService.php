<?php

namespace App\services;



// src/Service/DocumentGeneratorService.php

namespace App\services;

use TCPDF;

class DocumentGeneratorService
{
    public function generateBailCommunal($demandeur, $parcelle, $modalites, $infosLegales)
    {
        $pdf = new TCPDF();
        $pdf->AddPage();

        // Ajouter le titre
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Bail Communal', 0, 1, 'C');

        // Données du Demandeur
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Nom: ' . $demandeur['nom'], 0, 1);
        $pdf->Cell(0, 10, 'Prénom: ' . $demandeur['prenom'], 0, 1);
        $pdf->Cell(0, 10, 'Date et Lieu de naissance: ' . $demandeur['date_naissance'] . ', ' . $demandeur['lieu_naissance'], 0, 1);
        $pdf->Cell(0, 10, 'Adresse: ' . $demandeur['adresse'], 0, 1);
        $pdf->Cell(0, 10, 'Profession: ' . $demandeur['profession'], 0, 1);
        $pdf->Cell(0, 10, 'Numéro électeur: ' . $demandeur['num_electeur'], 0, 1);

        // Informations sur la Parcelle
        $pdf->Cell(0, 10, 'Type de demande: ' . $parcelle['type_demande'], 0, 1);
        $pdf->Cell(0, 10, 'Superficie: ' . $parcelle['superficie'] . ' m²', 0, 1);
        $pdf->Cell(0, 10, 'Usage prévu: ' . $parcelle['usage'], 0, 1);
        $pdf->Cell(0, 10, 'Localisation: ' . $parcelle['localisation'], 0, 1);
        $pdf->Cell(0, 10, 'Possession d\'un autre terrain: ' . $parcelle['autre_terrain'], 0, 1);

        // Modalités du Bail
        $pdf->Cell(0, 10, 'Durée du bail: ' . $modalites['duree'], 0, 1);
        $pdf->Cell(0, 10, 'Montant de la redevance annuelle: ' . $modalites['montant_redevance'], 0, 1);
        $pdf->Cell(0, 10, 'Modalités de paiement: ' . $modalites['modalites_paiement'], 0, 1);

        // Informations Administratives et Légales
        $pdf->Cell(0, 10, 'Référence du bail: ' . $infosLegales['reference'], 0, 1);
        $pdf->Cell(0, 10, 'Mentions légales: ' . $infosLegales['mentions_legales'], 0, 1);

        // Signature
        $pdf->Cell(0, 10, 'Date de signature: ' . date('d/m/Y'), 0, 1);

        // Sauvegarde du document
        $pdf->Output('bail_communal_' . $demandeur['nom'] . '.pdf', 'I');
    }

    public function generatePermisOccupier($demandeur, $terrain, $permis)
    {
        $pdf = new TCPDF();
        $pdf->AddPage();

        // Titre
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Permis d\'Occuper', 0, 1, 'C');

        // Données du Demandeur
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Nom: ' . $demandeur['nom'], 0, 1);
        $pdf->Cell(0, 10, 'Prénom: ' . $demandeur['prenom'], 0, 1);
        $pdf->Cell(0, 10, 'Date et Lieu de naissance: ' . $demandeur['date_naissance'] . ', ' . $demandeur['lieu_naissance'], 0, 1);

        // Description du Terrain
        $pdf->Cell(0, 10, 'Localisation du terrain: ' . $terrain['localisation'], 0, 1);
        $pdf->Cell(0, 10, 'Superficie: ' . $terrain['superficie'], 0, 1);
        $pdf->Cell(0, 10, 'Usage prévu: ' . $terrain['usage'], 0, 1);

        // Informations du Permis
        $pdf->Cell(0, 10, 'Numéro de permis: ' . $permis['numero'], 0, 1);
        $pdf->Cell(0, 10, 'Date de délivrance: ' . $permis['date_delivrance'], 0, 1);
        $pdf->Cell(0, 10, 'Durée de validité: ' . $permis['duree_validite'], 0, 1);

        // Mentions Réglementaires
        $pdf->Cell(0, 10, 'Références légales: ' . $permis['references_legales'], 0, 1);

        // Sauvegarde du document
        $pdf->Output('permis_occupation_' . $demandeur['nom'] . '.pdf', 'I');
    }

    public function generateCalculRedevance($demandeur, $parcelle, $calcul)
    {
        $pdf = new TCPDF();
        $pdf->AddPage();

        // Titre
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Calcul de Redevance Annuelle de Bail Communal', 0, 1, 'C');

        // Identité du Demandeur
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Nom: ' . $demandeur['nom'], 0, 1);
        $pdf->Cell(0, 10, 'Prénom: ' . $demandeur['prenom'], 0, 1);

        // Informations sur la Parcelle
        $pdf->Cell(0, 10, 'Superficie: ' . $parcelle['superficie'] . ' m²', 0, 1);
        $pdf->Cell(0, 10, 'Localisation: ' . $parcelle['localisation'], 0, 1);

        // Détails du Calcul
        $pdf->Cell(0, 10, 'Base de calcul: ' . $calcul['base_calcul'], 0, 1);
        $pdf->Cell(0, 10, 'Coefficient appliqué: ' . $calcul['coefficient'], 0, 1);
        $pdf->Cell(0, 10, 'Montant de la redevance annuelle: ' . $calcul['montant'], 0, 1);

        // Sauvegarde du document
        $pdf->Output('calcul_redevance_' . $demandeur['nom'] . '.pdf', 'I');
    }

    public function generatePropositionBail($demandeur, $proposition)
    {
        $pdf = new TCPDF();
        $pdf->AddPage();

        // Titre
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Proposition de Bail Communal', 0, 1, 'C');

        // Données du Demandeur
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Nom: ' . $demandeur['nom'], 0, 1);
        $pdf->Cell(0, 10, 'Prénom: ' . $demandeur['prenom'], 0, 1);

        // Description de la Parcelle
        $pdf->Cell(0, 10, 'Localisation: ' . $proposition['localisation'], 0, 1);
        $pdf->Cell(0, 10, 'Superficie: ' . $proposition['superficie'], 0, 1);
        $pdf->Cell(0, 10, 'Usage prévu: ' . $proposition['usage'], 0, 1);

        // Proposition des Termes du Bail
        $pdf->Cell(0, 10, 'Durée proposée du bail: ' . $proposition['duree'], 0, 1);
        $pdf->Cell(0, 10, 'Montant de la redevance annuelle: ' . $proposition['montant_redevance'], 0, 1);

        // Sauvegarde du document
        $pdf->Output('proposition_bail_' . $demandeur['nom'] . '.pdf', 'I');
    }
}
