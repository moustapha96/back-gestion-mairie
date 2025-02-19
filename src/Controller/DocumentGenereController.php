<?php

namespace App\Controller;

use App\Entity\BailCommunal;
use App\Entity\CalculRedevance;
use App\Entity\PermisOccupation;
use App\Entity\PropositionBail;
use App\Entity\DocumentGenere;
use App\Repository\BailCommunalRepository;
use App\Repository\CalculRedevanceRepository;
use App\Repository\PermisOccupationRepository;
use App\Repository\PropositionBailRepository;
use App\services\DocumentGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentGenereController extends AbstractController
{

    private $documentGeneratorService;
    private EntityManagerInterface $entityManager;

    public function __construct(
        DocumentGeneratorService $documentGeneratorService,
        EntityManagerInterface $entityManager
    ) {
        $this->documentGeneratorService = $documentGeneratorService;
        $this->entityManager = $entityManager;
    }

    // Générer un document pour un Bail Communal
    #[Route('/generate/bail-communal/{id}', name: 'generate_bail_communal', methods: ['POST'])]
    public function generateBailCommunal(int $id, BailCommunalRepository $bailCommunalRepository): Response
    {
        $bailCommunal = $bailCommunalRepository->find($id);

        if (!$bailCommunal) {
            return new Response('Bail Communal not found', Response::HTTP_NOT_FOUND);
        }

        // Créer le document généré
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::BAIL_COMMUNAL);
        $document->setDetails($this->generateBailDetails($bailCommunal));
        $document->setDateCreation(new \DateTime());

        $bailCommunal->setDocument($document);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return new Response('Document Bail Communal generated successfully', Response::HTTP_OK);
    }

    // Générer un document pour un Calcul de Redevance
    #[Route('/generate/calcul-redevance/{id}', name: 'generate_calcul_redevance', methods: ['POST'])]
    public function generateCalculRedevance(int $id, CalculRedevanceRepository $calculRedevanceRepository): Response
    {
        $calculRedevance = $calculRedevanceRepository->find($id);

        if (!$calculRedevance) {
            return new Response('Calcul Redevance not found', Response::HTTP_NOT_FOUND);
        }

        // Créer le document généré
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::CALCUL_REDEVANCE);
        $document->setDetails($this->generateCalculRedevanceDetails($calculRedevance));
        $document->setDateCreation(new \DateTime());

        $calculRedevance->setDocument($document);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return new Response('Document Calcul Redevance generated successfully', Response::HTTP_OK);
    }

    // Générer un document pour un Permis d'Occupation
    #[Route('/generate/permis-occupation/{id}', name: 'generate_permis_occupation', methods: ['POST'])]
    public function generatePermisOccupation(int $id, PermisOccupationRepository $permisOccupationRepository): Response
    {
        $permisOccupation = $permisOccupationRepository->find($id);

        if (!$permisOccupation) {
            return new Response('Permis Occupation not found', Response::HTTP_NOT_FOUND);
        }
        // Créer le document généré
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::PERMIS_OCCUPATION);
        $document->setDetails($this->generatePermisOccupationDetails($permisOccupation));
        $document->setDateCreation(new \DateTime());

        $permisOccupation->setDocument($document);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return new Response('Document Permis Occupation generated successfully', Response::HTTP_OK);
    }

    // Générer un document pour une Proposition de Bail
    #[Route('/generate/proposition-bail/{id}', name: 'generate_proposition_bail', methods: ['POST'])]
    public function generatePropositionBail(int $id, PropositionBailRepository $propositionBailRepository): Response
    {
        $propositionBail = $propositionBailRepository->find($id);
        if (!$propositionBail) {
            return new Response('Proposition Bail not found', Response::HTTP_NOT_FOUND);
        }
        // Créer le document généré
        $document = new DocumentGenere();
        $document->setTypeDocument(DocumentGenere::BAIL_COMMUNAL);
        $document->setDetails($this->generatePropositionBailDetails($propositionBail));
        $document->setDateCreation(new \DateTime());

        $propositionBail->setDocument($document);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return new Response('Document Proposition Bail generated successfully', Response::HTTP_OK);
    }

    // Méthodes auxiliaires pour générer les détails des documents
    private function generateBailDetails(BailCommunal $bailCommunal): string
    {
        return "Bail Reference: " . $bailCommunal->getReferenceBail() . "\n" .
            "Duration: " . $bailCommunal->getDureeBail() . "\n" .
            "Amount: " . $bailCommunal->getMontantRedevance();
    }

    private function generateCalculRedevanceDetails(CalculRedevance $calculRedevance): string
    {
        return "Base Calculation: " . $calculRedevance->getBaseCalcul() . "\n" .
            "Rate: " . $calculRedevance->getTaux() . "\n" .
            "Amount: " . $calculRedevance->getMontantRedevanceCalcule();
    }

    private function generatePermisOccupationDetails(PermisOccupation $permisOccupation): string
    {
        return "Permit Number: " . $permisOccupation->getNumeroPermis() . "\n" .
            "Duration: " . $permisOccupation->getDureeValidite() . "\n" .
            "Conditions: " . $permisOccupation->getConditionsOccupation();
    }

    private function generatePropositionBailDetails(PropositionBail $propositionBail): string
    {
        return "Proposition Duration: " . $propositionBail->getDureeProposition() . "\n" .
            "Proposed Amount: " . $propositionBail->getMontantPropose();
    }


    #[Route('/generate-bail-communal', name: 'generate_bail_communal')]
    public function generateBailCommunal2(): Response
    {
        $demandeur = [
            'nom' => 'Jean',
            'prenom' => 'Dupont',
            'date_naissance' => '01/01/1980',
            'lieu_naissance' => 'Paris',
            'adresse' => '123 Rue Exemple',
            'profession' => 'Agriculteur',
            'num_electeur' => '123456',
        ];

        $parcelle = [
            'type_demande' => 'attribution',
            'superficie' => 500,
            'usage' => 'résidentiel',
            'localisation' => 'Dakar',
            'autre_terrain' => 'Non',
        ];

        $modalites = [
            'duree' => '10 ans',
            'montant_redevance' => '5000 FCFA',
            'modalites_paiement' => 'annuel',
        ];

        $infosLegales = [
            'reference' => 'B12345',
            'mentions_legales' => 'Conditions légales selon le contrat',
        ];

        $this->documentGeneratorService->generateBailCommunal($demandeur, $parcelle, $modalites, $infosLegales);

        return new Response('Document généré');
    }
}
