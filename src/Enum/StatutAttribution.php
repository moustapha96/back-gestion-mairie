<?php
namespace App\Enum;

enum StatutAttribution: string
{
    // Phase initiale
    // Phase provisoire (après passage commission)
    case VALIDATION_PROVISOIRE = 'VALIDATION_PROVISOIRE';  // Validation provisoire (commission OK)
    case ATTRIBUTION_PROVISOIRE= 'ATTRIBUTION_PROVISOIRE'; // Arrêté provisoire (signé si applicable)

    // Tutelles / autorités
    case APPROBATION_PREFET    = 'APPROBATION_PREFET';     // Approbation du Préfet
    case APPROBATION_CONSEIL   = 'APPROBATION_CONSEIL';    // Approbation du Conseil

    // Attribution finale
    case ATTRIBUTION_DEFINITIVE= 'ATTRIBUTION_DEFINITIVE'; // Attribution définitive (titre/ordre signé)
    case REJETEE               = 'REJETEE';                // Refusée
    case ANNULEE               = 'ANNULEE';                // Annulée
}
