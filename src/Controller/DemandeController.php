<?php

namespace App\Controller;

use App\Entity\DemandeTerrain;
use App\Entity\User;
use App\Entity\DocumentGenere;
use App\Entity\HistoriqueValidation;
use App\Repository\AuditLogRepository;
use App\Repository\DemandeTerrainRepository;
use App\Repository\HistoriqueValidationRepository;
use App\Repository\LocaliteRepository;
use App\Repository\NiveauValidationRepository;
use App\Repository\UserRepository;
use App\services\FonctionsService;
use App\services\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class DemandeController extends AbstractController
{
    private $em;
    private $mailService;
    public function __construct(EntityManagerInterface $em, MailService $mailService)
    {
        $this->em = $em;
        $this->mailService = $mailService;
    }

    private function genererDocument(DemandeTerrain $demande): DocumentGenere
    {
        $document = new DocumentGenere();
        $document->setDateCreation(new \DateTime());

        // Définir le type et le contenu en fonction du type de demande
        switch ($demande->getTypeDemande()) {
            case DemandeTerrain::PERMIS_OCCUPATION:
                $document->setType('PERMIS_OCCUPATION');
                $document->setContenu([
                    'numeroPermis' => 'PO-' . date('Y') . '-' . uniqid(),
                    'dateDelivrance' => (new \DateTime())->format('Y-m-d'),
                    'dureeValidite' => '1 an',
                    'superficie' => $demande->getSuperficie(),
                    'usagePrevu' => $demande->getUsagePrevu(),
                    'localite' => $demande->getLocalite()->getNom()
                ]);
                break;

            case DemandeTerrain::BAIL_COMMUNAL:
                $document->setType('BAIL_COMMUNAL');
                $document->setContenu([
                    'numeroBail' => 'BC-' . date('Y') . '-' . uniqid(),
                    'dateDebut' => (new \DateTime())->format('Y-m-d'),
                    'duree' => '5 ans',
                    'superficie' => $demande->getSuperficie(),
                    'usagePrevu' => $demande->getUsagePrevu(),
                    'localite' => $demande->getLocalite()->getNom()
                ]);
                break;

            case DemandeTerrain::CALCUL_REDEVANCE:
                $document->setType('CALCUL_REDEVANCE');
                // Calcul exemple de redevance basé sur la superficie
                $montantRedevance = $demande->getSuperficie() * 1000;
                $document->setContenu([
                    'numeroCalcul' => 'CR-' . date('Y') . '-' . uniqid(),
                    'dateCalcul' => (new \DateTime())->format('Y-m-d'),
                    'superficie' => $demande->getSuperficie(),
                    'montantRedevance' => $montantRedevance,
                    'baseCalcul' => 'Superficie * 1000'
                ]);
                break;

            case DemandeTerrain::PROPOSITION_BAIL:
                $document->setType('PROPOSITION_BAIL');
                $document->setContenu([
                    'numeroProposition' => 'PB-' . date('Y') . '-' . uniqid(),
                    'dateProposition' => (new \DateTime())->format('Y-m-d'),
                    'dureeProposee' => '3 ans',
                    'superficie' => $demande->getSuperficie(),
                    'usagePrevu' => $demande->getUsagePrevu(),
                    'localite' => $demande->getLocalite()->getNom()
                ]);
                break;

            default:
                throw new \InvalidArgumentException('Type de demande non reconnu');
        }

        return $document;
    }

    #[Route('/api/demande/nouvelle-demande', name: 'api_demande_nouvelle_demande', methods: ['POST'])]
    public function nouveauDemande(
        Request $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository,
        NiveauValidationRepository $niveauRepo
    ): Response {


        $superficie = $request->request->get('superficie');
        $usagePrevu = $request->request->get('usagePrevu');
        $localiteId = $request->request->get('localiteId');
        $typeDemande = $request->request->get('typeDemande');
        $typeDocument = $request->request->get('typeDocument');
        $possedeAutreTerrain = $request->request->get('possedeAutreTerrain');

        $prenom = $request->request->get('prenom');
        $nom = $request->request->get('nom');
        $email = $request->request->get('email');
        $telephone = $request->request->get('telephone');
        $profession = $request->request->get('profession');
        $adresse = $request->request->get('adresse');
        $lieuNaissance = $request->request->get('lieuNaissance');
        $dateNaissance = $request->request->get('dateNaissance');
        $numeroElecteur = $request->request->get('numeroElecteur');





        if (!$prenom || !$nom || !$telephone || !$profession || !$adresse || !$lieuNaissance || !$numeroElecteur || !$dateNaissance) {
            return $this->json([
                'prenom' => $prenom,
                'nom' => $nom,
                'telephone' => $telephone,
                'profession' => $profession,
                'adresse' => $adresse,
                'lieuNaissance' => $lieuNaissance,
                'dateNaissance' => $dateNaissance
            ], Response::HTTP_NO_CONTENT);
        }

        if (!$typeDemande || !$superficie || !$email || !$localiteId || !$usagePrevu || !$possedeAutreTerrain || !$typeDocument) {
            return $this->json('Données manquantes ou invalides sur la demande', Response::HTTP_NO_CONTENT);
        }


        $userExiste = false;
        $user = $userRepository->findOneBy(["email" => $email]);
        if ($user) {
            $userExiste = true;
        }
        if (!$user) {
            $userExiste = false;
            $user = new User();

            $user->setPrenom($prenom);
            $user->setNom($nom);
            $user->setEmail($email);
            $user->setUsername($email);
            $user->setTelephone($telephone);
            $user->setRoles(User::ROLE_DEMANDEUR);
            $user->setProfession($profession);
            $user->setAdresse($adresse);
            $user->setLieuNaissance($lieuNaissance);
            $user->setDateNaissance(new \DateTime($dateNaissance));
            $user->setNumeroElecteur($numeroElecteur);
            $user->setPassword(\password_hash('password', PASSWORD_BCRYPT));
            $user->setPasswordClaire('password');
            $user->setTokenActiveted(bin2hex(random_bytes(32)));
            $user->setActiveted(false);
            $user->setEnabled(false);

            $this->em->persist($user);
        }

        $localite = $localiteRepository->find($localiteId);


        if (!$localite) {
            return $this->json('Localité non trouvée', Response::HTTP_NOT_FOUND);
        }

        $demande = new DemandeTerrain();
        $demande
            ->setSuperficie($superficie)
            ->setTypeDemande($typeDemande)
            ->setUsagePrevu($usagePrevu)
            ->setUtilisateur($user)
            ->setTypeDocument($typeDocument)
            ->setDateCreation(new \DateTime())
            ->setStatut(DemandeTerrain::STATUT_EN_ATTENTE)
            ->setPossedeAutreTerrain($possedeAutreTerrain)
            ->setMotifRefus(null)
            ->setLocalite($localite);

        /** @var UploadedFile|null $file */
        // $file = $request->files->get('document');
        $recto = $request->files->get('recto');
        $verso = $request->files->get('verso');

        if ($recto) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                'recto',
                $user ? str_replace(' ', '-', strtolower($user->getEmail())) : date('YmdHis'),
                $recto->guessExtension()
            );
            $recto->move($this->getParameter('document_directory'), $newFilename);
            $url = $this->getParameter('document_directory') . "/" . $newFilename;
            $demande->setRecto($url);
        } else {
            return $this->json('Veuillez uploader le recto', Response::HTTP_BAD_REQUEST);
        }

        if ($verso) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                'verso',
                $user ? str_replace(' ', '-', strtolower($user->getEmail())) : date('YmdHis'),
                $verso->guessExtension()
            );
            $verso->move($this->getParameter('document_directory'), $newFilename);
            $url = $this->getParameter('document_directory') . "/" . $newFilename;
            $demande->setVerso($url);
        } else {
            return $this->json('Veuillez uploader le verso', Response::HTTP_BAD_REQUEST);
        }


        $this->em->persist($demande);
        $niveauParDefaut = $niveauRepo->findOneBy(['ordre' => 1]); // Ou un autre critère
        $demande->setNiveauValidationActuel($niveauParDefaut);
        $this->em->persist($demande);

        // $documentGenere = $this->genererDocument($demande);
        // $documentGenere->setDemandeTerrain($demande);
        // $this->em->persist($documentGenere);
        // $demande->setDocumentGenerer($documentGenere);

        if ($userExiste) {
            $this->mailService->sendDemandeMail($demande);
        } else {
            $this->mailService->sendConfirmationDemande($demande);
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->toArray(),
            'localite' => $localite->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/demande/create', name: 'api_demande_create', methods: ['POST'])]
    public function createDemande(
        Request $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository
    ): Response {

        $userId = $request->request->get('userId');
        $superficie = $request->request->get('superficie');
        $usagePrevu = $request->request->get('usagePrevu');
        $localiteId = $request->request->get('localiteId');
        $typeDemande = $request->request->get('typeDemande');
        $typeDocument = $request->request->get('typeDocument');
        $possedeAutreTerrain = $request->request->get('possedeAutreTerrain');
        $typeTitre = $request->request->get('typeTitre');
        $terrainAilleurs = $request->request->get('terrainAilleurs');
        $terrainAKaolack = $request->request->get('terrainAKaolack');

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
            ->setTypeTitre($typeTitre)
            ->setTerrainAKaolack($terrainAKaolack)
            ->setDateCreation(new \DateTime())
            ->setStatut(DemandeTerrain::STATUT_EN_ATTENTE)
            ->setPossedeAutreTerrain($possedeAutreTerrain)
            ->setMotifRefus(null)
            ->setDecisionCommission(null)
            ->setRapport(null)
            ->setTerrainAilleurs($terrainAilleurs)
            ->setRecommandation(null)
            ->setRapport(null)
            ->setLocalite($localite);

        /** @var UploadedFile|null $recto  */
        /** @var UploadedFile|null $verso  */

        $recto = $request->files->get('recto');
        $verso = $request->files->get('verso');

        if ($recto) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                'recto',
                $utilisateur ? str_replace(' ', '-', strtolower($utilisateur->getEmail())) : date('YmdHis'),
                $recto->guessExtension()
            );
            $recto->move($this->getParameter('document_directory'), $newFilename);
            $url = $this->getParameter('document_directory') . "/" . $newFilename;
            $demande->setRecto($url);
        } else {
            return $this->json('Veuillez uploader le recto', Response::HTTP_BAD_REQUEST);
        }

        if ($verso) {
            $newFilename = sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($typeDocument)),
                $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
                'verso',
                $utilisateur ? str_replace(' ', '-', strtolower($utilisateur->getEmail())) : date('YmdHis'),
                $verso->guessExtension()
            );
            $verso->move($this->getParameter('document_directory'), $newFilename);
            $url = $this->getParameter('document_directory') . "/" . $newFilename;
            $demande->setVerso($url);
        } else {
            return $this->json('Veuillez uploader le verso', Response::HTTP_BAD_REQUEST);
        }


        // $documentGenere = $this->genererDocument($demande);
        // $documentGenere->setDemandeTerrain($demande);
        // $this->em->persist($documentGenere);
        // $demande->setDocumentGenerer($documentGenere);

        $this->em->persist($demande);
        $this->em->flush();

        $this->mailService->sendDemandeMail($demande);


        return $this->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->toArray(),
            'localite' => $demande->getLocalite()->toArray()
        ], Response::HTTP_CREATED);
    }


    #[Route('/api/demandeur/{id}/demandes', name: 'api_user_demande', methods: ['GET'])]
    public function getDemandeurDemandes($id, UserRepository $userRepository, DemandeTerrainRepository $demandeRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvée'], Response::HTTP_NOT_FOUND);

        $demandes = $demandeRepository->findBy(['utilisateur' => $user]);

        foreach ($demandes as $demande) {
            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'motif_refus' => $demande->getMotifRefus(),
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
                    'isHabitant' => $demande->getUtilisateur()->isHabitant() ? true : false,
                    'situationMatrimoniale' => $demande->getUtilisateur()->getSituationMatrimoniale(),
                    'nombreEnfant' => $demande->getUtilisateur()->getNombreEnfant(),
                ] : null,
                'localite' => $demande->getLocalite() ? [
                    'id' => $demande->getLocalite()->getId(),
                    'nom' => $demande->getLocalite()->getNom(),
                    'description' => $demande->getLocalite()->getDescription(),
                ] : null,
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }

    // liste des demandes d'un demandeur
    #[Route('/api/demandes/demandeur/{id}', name: 'api_demande_demandeur', methods: ['GET'])]
    public function demandeDemandeur(
        int $id,
        LocaliteRepository $localiteRepository,
        DemandeTerrainRepository $demandeRepository,
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->find($id);

        if (!$user)
            return new Response(json_encode(['message' => 'Utilisateur non trouvé']), Response::HTTP_NOT_FOUND, ['Content-Type' => 'application/json']);

        $demandes = $demandeRepository->findBy(['utilisateur' => $user]);

        $resultats = [];
        if (count($demandes) == 0)
            return $this->json($resultats, Response::HTTP_OK, ['Content-Type' => 'application/json']);


        foreach ($demandes as $demande) {

            $localite = $localiteRepository->find($demande->getLocalite()->getId());
            if ($localite) {
                $this->em->initializeObject($localite);
            }

            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'motif_refus' => $demande->getMotifRefus(),
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
                    'isHabitant' => $demande->getUtilisateur()->isHabitant() ? true : false,
                    'situationMatrimoniale' => $demande->getUtilisateur()->getSituationMatrimoniale(),
                    'nombreEnfant' => $demande->getUtilisateur()->getNombreEnfant(),
                ] : null,

                'localite' => $localite ? $localite->toArray() : null,
                // 'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null,
                'documentGenerer' => $demande->getDocumentGenerer() ? true : false
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
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'motif_refus' => $demande->getMotifRefus(),
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
    #[Route('/api/demande/user/{id}/liste', name: 'api_demande_user_liste', methods: ['GET'])]
    public function demandeUser(
        int $id,
        DemandeTerrainRepository $demandeRepository,
        LocaliteRepository $localiteRepository,
        UserRepository $userRepository
    ): Response {
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
            $localite = $localiteRepository->find($demande->getLocalite()->getId());
            if ($localite) {
                $this->em->initializeObject($localite);
            }

            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'motif_refus' => $demande->getMotifRefus(),
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
                    'isHabitant' => $demande->getUtilisateur()->isHabitant() ? true : false,
                ] : null,
                'localite' => $localite ? $localite->toArray() : null,
                'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null
            ];
        }

        return $this->json($resultats, Response::HTTP_OK, [], ['groups' => ['demande:item']]);
    }

    #[Route('/api/demande/details/{demandeId}', name: 'api_demande_user_detail', methods: ['GET'])]
    public function demandeUserDetail(
        int $demandeId,
        DemandeTerrainRepository $demandeRepository,
        LocaliteRepository $localiteRepository,
        FonctionsService $fonctionsService
    ): Response {

        $demande = $demandeRepository->findOneBy(['id' => $demandeId]);
        if (!$demande)
            return new Response(
                json_encode(['message' => 'Demande non trouvé']),
                Response::HTTP_NOT_FOUND,
                ['Content-Type' => 'application/json']
            );
        $localite = $localiteRepository->find($demande->getLocalite()->getId());
        $lotissement = $localite->getLotissements();
        $lotissements = [];
        foreach ($lotissement as $l) {
            $lotissements[] = $l->toArray();
        }

        if ($localite) {
            $this->em->initializeObject($localite);
        }

        $historiques = [];
        foreach ($demande->getHistoriqueValidations() as $historique) {
            $historiques[] = $historique->toArray();
        }

        $resultats = [
            'id' => $demande->getId(),
            'typeDemande' => $demande->getTypeDemande(),
            'typeDocument' => $demande->getTypeDocument(),
            'superficie' => $demande->getSuperficie(),
            'usagePrevu' => $demande->getUsagePrevu(),
            'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
            'statut' => $demande->getStatut(),
            'recto' => $demande->getRecto(),
            'verso' => $demande->getVerso(),
            'motif_refus' => $demande->getMotifRefus(),
            'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
            'document' => $demande->getDocument(),
            'rapport'=> $demande->getRapport(),
            'typeTitre'=> $demande->getTypeTitre(),
            'niveauValidationActuel' => $demande->getNiveauValidationActuel() ? $demande->getNiveauValidationActuel()->toArray() :  null,
            'historiqueValidations'=> $historiques,
            'terrainAKaolack'=> $demande->isTerrainAKaolack(),
            'terrainAilleurs' => $demande->isTerrainAilleurs(),
            'decisionCommission' => $demande->getDecisionCommission(),
            'recommandation' => $demande->getRecommandation(),

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
                'isHabitant' => $fonctionsService->checkNumeroElecteurExist($demande->getUtilisateur()->getNumeroElecteur()),

                'situationMatrimoniale' => $demande->getUtilisateur()->getSituationMatrimoniale(),
                'nombreEnfant' => $demande->getUtilisateur()->getNombreEnfant(),
            ] : null,

            'localite' => $localite ? [
                'id' => $localite->getId(),
                'nom' => $localite->getNom(),
                'prix' => $localite->getPrix(),
                'longitude' => $localite->getLongitude(),
                'latitude' => $localite->getLatitude(),
                'description' => $localite->getDescription(),
                'lotissement' => $lotissement ? $lotissements : null
            ] : null,

            'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null
        ];
        // return $this->json($resultats, Response::HTTP_OK);
        return $this->json($resultats, Response::HTTP_OK, [], ['groups' => ['demande:item']]);
    }


    #[Route('/api/demande/file/{demandeId}', name: 'api_demande_user_get_file', methods: ['GET'])]
    public function demandeUserDocument(int $demandeId, DemandeTerrainRepository $demandeRepository): Response
    {
        $demande = $demandeRepository->findOneBy(['id' => $demandeId]);
        if (!$demande) {
            return new Response(
                json_encode(['message' => 'Demande non trouvée']),
                Response::HTTP_NOT_FOUND,
                ['Content-Type' => 'application/json']
            );
        }
        $recto = $demande->getRecto();
        $verso = $demande->getVerso();
        if (!$recto || !$verso) {
            return new Response(
                json_encode(['message' => 'Fichier non trouvée']),
                Response::HTTP_NOT_FOUND,
                ['Content-Type' => 'application/json']
            );
        }



        $mimeRecto = mime_content_type($recto);
        $mimeVerso = mime_content_type($verso);


        $originalContent = file_get_contents($recto);

        if (!$originalContent) {
            return new Response(
                json_encode(['message' => 'Impossible de récupérer le contenu du fichier original']),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'application/json']
            );
        }


        if ($mimeRecto !== 'application/pdf' && $mimeVerso !== 'application/pdf') {
            return new Response(
                json_encode(['message' => 'Le fichier doit être un PDF']),
                Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
                ['Content-Type' => 'application/json']
            );
        }


        try {

            $contentRecto = file_get_contents($recto);
            $contenterso = file_get_contents($verso);


            if ($contentRecto === false || $contenterso === false) {
                throw new \Exception("Erreur lors de la lecture du fichier.");
            }

            $base64Recto = base64_encode($contentRecto);
            $base64Verso = base64_encode($contenterso);

            $response = new Response(
                json_encode([
                    'recto' => $base64Recto,
                    'verso' => $base64Verso
                ]),
                Response::HTTP_OK,
                ['Content-Type' => 'application/json']
            );

            return $response;
        } catch (\Exception $e) {
            return new Response(
                json_encode(['message' => 'Erreur lors de l\'encodage du fichier en base64', 'error' => $e->getMessage()]),
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['Content-Type' => 'application/json']
            );
        }
    }


    #[Route('/api/demande/liste', name: 'api_demande_liste', methods: ['GET'])]
    public function demandes(
        DemandeTerrainRepository $demandeRepository,
        LocaliteRepository $localiteRepository
    ): Response {

        $demandes = $demandeRepository->findBy([], ['dateCreation' => 'DESC']);

        $resultats = [];
        // dd("arrie");
        foreach ($demandes as $demande) {
            $localite = $localiteRepository->find($demande->getLocalite()->getId());
            if ($localite) {
                $this->em->initializeObject($localite);
            }
            $user = $demande->getUtilisateur();

            $resultats[] = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'motif_refus' => $demande->getMotifRefus(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'document' => $demande->getDocument(),
                'demandeur' => $user ? $user->toArray() : null,
                'localite' => $localite ? $localite->toArray() : null,
                // 'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null,
                'documentGenerer' => $demande->getDocumentGenerer() ? true : false
            ];
        }

        // return $this->json($resultats, Response::HTTP_OK);
        return $this->json($resultats, Response::HTTP_OK);
    }


    #[Route('/api/demande/{id}/update-statut', name: 'api_demande_update_statut', methods: ['PUT'])]
    public function updateDemandeStatut($id, Request $requet, DemandeTerrainRepository $demandeRepository): Response
    {
        $demande = $demandeRepository->find($id);
        if (!$demande) {
            return $this->json("Demande non trouvée", Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($requet->getContent(), true);

        $statut = $data['statut'];
        $demande->setStatut($statut);
        $demande->setDateModification(new \DateTime());
        $demande->setDocumentGenerer(null);
        $this->em->persist($demande);
        $this->mailService->sendStatusChangeMail($demande);
        $this->em->flush();

        return $this->json("Statut mis à jour", Response::HTTP_OK);
    }


    function cleanUtf8($text)
    {
        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]/', '', $text);
    }


    #[Route('/api/demande/demandeur/{id}/liste', name: 'api_demande_demandeur_liste', methods: ['GET'])]
    public function listeDemandeDemandeur(
        $id,
        UserRepository $userRepository,
        DemandeTerrainRepository $demandeRepository
    ): Response {
        $demandeur = $userRepository->find($id);
        if (!$demandeur) {
            return $this->json("Demandeur non trouvée", Response::HTTP_NOT_FOUND);
        }

        $demandes = $demandeRepository->findBy(['utilisateur' => $demandeur]);
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
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'motif_refus' => $demande->getMotifRefus(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'document' => $demande->getDocument(),
                'demandeur' => $demande->getUtilisateur() ? [
                    'id' => $demande->getUtilisateur()->getId(),
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'email' => $demande->getUtilisateur()->getEmail(),
                    'telephone' => $demande->getUtilisateur()->getTelephone(),
                    'isHabitant' => $demande->getUtilisateur()->isHabitant() ? true : false,
                    'situationMatrimoniale' => $demande->getUtilisateur()->getSituationMatrimoniale(),
                    'nombreEnfant' => $demande->getUtilisateur()->getNombreEnfant(),
                    'lieuNaissance' => $demande->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance() ?
                        $demande->getUtilisateur()->getDateNaissance()->format('Y-m-d') : null,
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                ] : null,
                'localite' => $demande->getLocalite() ? $demande->getLocalite()->toArray() : null,
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }


    #[Route('/api/demande/{id}/update-refus', name: 'api_demande_update_refus', methods: ['PUT'])]
    public function updateRefusDemande($id, Request $request, DemandeTerrainRepository $demandeRepository): Response
    {
        $demande = $demandeRepository->find($id);
        if (!$demande) {
            return $this->json("Demande non trouvée", Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $motifRefus = $data['message'] ?? null;

        if ($motifRefus) {
            $demande->setMotifRefus($motifRefus);
            $this->em->persist($demande);
            $this->em->flush();
            return $this->json("Motif de refus mis à jour", Response::HTTP_OK);
        }

        return $this->json("Aucun motif de refus fourni", Response::HTTP_BAD_REQUEST);
    }


    #[Route('/api/demande/{id}/update', name: 'api_demande_update', methods: ['PUT'])]
    public function updateDemande(
        int $id,
        Request $request,
        DemandeTerrainRepository $demandeRepository,
        LocaliteRepository $localiteRepository,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $demande = $demandeRepository->find($id);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Roles
        $canUpdateStatus = $this->isGranted('ROLE_MAIRE') || $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SUPER_ADMIN');
        $canSetRecommandation = $this->isGranted('ROLE_PRESIDENT_COMMISSION') || $this->isGranted('ROLE_CHEF_SERVICE');
        $canSetRapport = $this->isGranted('ROLE_AGENT');
        $canSetDecisionCommission = $this->isGranted('ROLE_PRESIDENT_COMMISSION') || $this->isGranted('ROLE_MEMBRE_COMMISSION');

        // Récupère les valeurs (multipart/form-data **ou** JSON)
        $isMultipart = 0 < count($request->files);
        $data = $isMultipart ? $request->request : $request->toArray();

        // --- Mises à jour "génériques" accessibles au staff ---
        // typeDemande (Attribution / Régularisation / Authentification)
        if (isset($data['typeDemande']) && $data['typeDemande'] !== '') {
            $demande->setTypeDemande($data['typeDemande']); // l’entité valide déjà la valeur
        }

        // typeTitre (Permis d'occuper / Bail communal / Proposition de bail / Transfert définitif)
        if (isset($data['typeTitre']) && $data['typeTitre'] !== '') {
            $demande->setTypeTitre($data['typeTitre']);
        }

        if (isset($data['superficie']) && $data['superficie'] !== '') {
            $demande->setSuperficie((float) $data['superficie']);
        }

        if (isset($data['usagePrevu'])) {
            $demande->setUsagePrevu($data['usagePrevu']);
        }

        if (isset($data['typeDocument']) && $data['typeDocument'] !== '') {
            $demande->setTypeDocument($data['typeDocument']);
        }

        if (isset($data['possedeAutreTerrain'])) {
            $demande->setPossedeAutreTerrain(filter_var($data['possedeAutreTerrain'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($data['terrainAKaolack'])) {
            $demande->setTerrainAKaolack(filter_var($data['terrainAKaolack'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($data['terrainAilleurs'])) {
            $demande->setTerrainAilleurs(filter_var($data['terrainAilleurs'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($data['motif_refus'])) {
            $demande->setMotifRefus($data['motif_refus']);
        }

        // localite
        if (isset($data['localiteId']) && $data['localiteId'] !== '') {
            $localite = $localiteRepository->find((int) $data['localiteId']);
            if (!$localite) {
                return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $demande->setLocalite($localite);
        }

        // --- Champs avec restrictions de rôles ---
        if (isset($data['statut'])) {
            if (!$canUpdateStatus) {
                return $this->json(['message' => 'Vous n’avez pas les droits pour modifier le statut'], Response::HTTP_FORBIDDEN);
            }
            $demande->setStatut($data['statut']); // l’entité valide déjà la valeur
        }

        if (isset($data['recommandation'])) {
            if (!$canSetRecommandation) {
                return $this->json(['message' => 'Vous n’avez pas les droits pour modifier la recommandation'], Response::HTTP_FORBIDDEN);
            }
            $demande->setRecommandation($data['recommandation']);
        }

        if (isset($data['rapport'])) {
            if (!$canSetRapport) {
                return $this->json(['message' => 'Vous n’avez pas les droits pour modifier le rapport'], Response::HTTP_FORBIDDEN);
            }
            $demande->setRapport($data['rapport']);
        }

        if (isset($data['decisionCommission'])) {
            if (!$canSetDecisionCommission) {
                return $this->json(['message' => 'Vous n’avez pas les droits pour modifier la décision de commission'], Response::HTTP_FORBIDDEN);
            }
            $demande->setDecisionCommission($data['decisionCommission']);
        }

        // --- Fichiers (recto/verso) ---
        /** @var UploadedFile|null $recto */
        $recto = $request->files->get('recto');
        /** @var UploadedFile|null $verso */
        $verso = $request->files->get('verso');

        $documentDir = $this->getParameter('document_directory');

        if ($recto instanceof UploadedFile) {
            $newFilename = sprintf(
                '%s-%s-recto-%s.%s',
                str_replace(' ', '-', strtolower($demande->getTypeDocument() ?? 'doc')),
                $demande->getTypeDemande() ? str_replace(' ', '-', strtolower($demande->getTypeDemande())) : date('YmdHis'),
                $demande->getUtilisateur() ? str_replace(' ', '-', strtolower($demande->getUtilisateur()->getEmail())) : date('YmdHis'),
                $recto->guessExtension() ?: 'pdf'
            );
            $recto->move($documentDir, $newFilename);
            $demande->setRecto($documentDir . "/" . $newFilename);
        }

        if ($verso instanceof UploadedFile) {
            $newFilename = sprintf(
                '%s-%s-verso-%s.%s',
                str_replace(' ', '-', strtolower($demande->getTypeDocument() ?? 'doc')),
                $demande->getTypeDemande() ? str_replace(' ', '-', strtolower($demande->getTypeDemande())) : date('YmdHis'),
                $demande->getUtilisateur() ? str_replace(' ', '-', strtolower($demande->getUtilisateur()->getEmail())) : date('YmdHis'),
                $verso->guessExtension() ?: 'pdf'
            );
            $verso->move($documentDir, $newFilename);
            $demande->setVerso($documentDir . "/" . $newFilename);
        }

        // horodatage et flush
        $demande->setDateModification(new \DateTime());
        $em->persist($demande);
        $em->flush();

        return $this->json([
            'message' => 'Demande mise à jour avec succès',
            'demande' => [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeTitre' => $demande->getTypeTitre(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'terrainAKaolack' => $demande->isTerrainAKaolack(),
                'terrainAilleurs' => $demande->isTerrainAilleurs(),
                'statut' => $demande->getStatut(),
                'motif_refus' => $demande->getMotifRefus(),
                'rapport' => $demande->getRapport(),
                'recommandation' => $demande->getRecommandation(),
                'decisionCommission' => $demande->getDecisionCommission(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'dateModification' => $demande->getDateModification()?->format('Y-m-d H:i:s'),
                'localite' => $demande->getLocalite()?->toArray(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/api/demande/{id}/delete', name: 'api_demande_delete', methods: ['delete'])]
    public function delete(
        $id,
        DemandeTerrainRepository $demandeTerrainRepository,
        EntityManagerInterface $em
    ): Response {
        $demande = $demandeTerrainRepository->find($id);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $document = $demande->getDocumentGenerer();
        if ($document) {
            $em->remove($document);
        }

        $em->remove($demande);
        $em->flush();

        return $this->json(['message' => 'Demande supprimée avec succès'], Response::HTTP_OK);
    }

    // demande/document-generer/${demandeId}/delete
    #[Route('/api/demande/document-generer/{demandeId}/delete', name: 'api_demande_document_generer_delete', methods: ['delete'])]
    public function deleteDocumentGenerer(
        $demandeId,
        DemandeTerrainRepository
        $demandeTerrainRepository,
        EntityManagerInterface $em
    ): Response {
        $demande = $demandeTerrainRepository->find($demandeId);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $document = $demande->getDocumentGenerer();
        if ($document) {
            $demande->setDocumentGenerer(null);
            $demandeTerrainRepository->save($demande, true);
        }

        return $this->json(['message' => 'Document supplémentaire supprimé avec succès'], Response::HTTP_OK);
    }




    #[Route('/api/demandes', name: 'api_demandes_index', methods: ['GET'])]
    public function listPaginated(
        Request $request,
        DemandeTerrainRepository $repo,
        AuditLogRepository $auditRepo // pour retrouver l'agent/acteur qui a créé
    ): Response {
        $page = (int) $request->query->get('page', 1);
        $size = (int) $request->query->get('size', 10);
        $sort = (string) $request->query->get('sort', 'id,DESC');
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');
        $typeDemande = $request->query->get('typeDemande');
        $typeDocument = $request->query->get('typeDocument');
        $userId = $request->query->get('userId');
        $localiteId = $request->query->get('localiteId');
        $fromStr = $request->query->get('from');
        $toStr = $request->query->get('to');
        $includeActor = filter_var($request->query->get('includeActor', 'false'), FILTER_VALIDATE_BOOLEAN);

        $from = !empty($fromStr) ? new \DateTimeImmutable($fromStr) : null;
        $to = !empty($toStr) ? new \DateTimeImmutable($toStr) : null;

        $result = $repo->searchPaginated([
            'page' => $page,
            'size' => $size,
            'sort' => $sort,
            'search' => $search ?: null,
            'statut' => $statut ?: null,
            'typeDemande' => $typeDemande ?: null,
            'typeDocument' => $typeDocument ?: null,
            'userId' => $userId ? (int) $userId : null,
            'localiteId' => $localiteId ? (int) $localiteId : null,
            'from' => $from,
            'to' => $to,
        ]);

        $data = [];

        foreach ($result['items'] as $d) {
            /** @var \App\Entity\DemandeTerrain $d */
            $row = [
                'id' => $d->getId(),
                'typeDemande' => $d->getTypeDemande(),
                'typeDocument' => $d->getTypeDocument(),
                'superficie' => $d->getSuperficie(),
                'usagePrevu' => $d->getUsagePrevu(),
                'possedeAutreTerrain' => $d->isPossedeAutreTerrain(),
                'statut' => $d->getStatut(),
                'motif_refus' => $d->getMotifRefus(),
                'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $d->getDateModification()?->format('Y-m-d H:i:s'),
                'demandeur' => $d->getUtilisateur() ? [
                    'id' => $d->getUtilisateur()->getId(),
                    'nom' => $d->getUtilisateur()->getNom(),
                    'prenom' => $d->getUtilisateur()->getPrenom(),
                    'email' => $d->getUtilisateur()->getEmail(),
                    'telephone' => $d->getUtilisateur()->getTelephone(),
                    'isHabitant' => $d->getUtilisateur()->isHabitant() ? true : false,
                    'situationMatrimoniale' => $d->getUtilisateur()->getSituationMatrimoniale(),
                    'nombreEnfant' => $d->getUtilisateur()->getNombreEnfant(),
                    'lieuNaissance' => $d->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $d->getUtilisateur()->getDateNaissance() ? $d->getUtilisateur()->getDateNaissance()->format('Y-m-d') : null,
                    'adresse' => $d->getUtilisateur()->getAdresse(),
                    'numeroElecteur' => $d->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $d->getUtilisateur()->getProfession(),
                ] : null,

                'localite' => $d->getLocalite() ? [
                    'id' => $d->getLocalite()->getId(),
                    'nom' => $d->getLocalite()->getNom(),
                ] : null,
                'documentGenerer' => $d->getDocumentGenerer() ? true : false,
            ];

            if ($includeActor) {
                // on récupère l’acteur qui a créé la demande via AuditLog
                $actor = $auditRepo->findOneBy(
                    ['event' => 'ENTITY_CREATED', 'entityClass' => \App\Entity\DemandeTerrain::class, 'entityId' => (string) $d->getId()],
                    ['createdAt' => 'ASC'] // 1er log = création
                );
                $row['createdBy'] = $actor ? [
                    'actorId' => $actor->getActorId(),
                    'actor' => $actor->getActorIdentifier(),
                ] : null;
            }

            $data[] = $row;
        }

        return $this->json([
            'data' => $data,
            'meta' => [
                'page' => $result['page'],
                'size' => $result['size'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'sort' => $sort,
                'filters' => array_filter([
                    'search' => $search,
                    'statut' => $statut,
                    'typeDemande' => $typeDemande,
                    'typeDocument' => $typeDocument,
                    'userId' => $userId,
                    'localiteId' => $localiteId,
                    'from' => $from?->format(DATE_ATOM),
                    'to' => $to?->format(DATE_ATOM),
                    'includeActor' => $includeActor ?: null,
                ], fn($v) => $v !== null && $v !== ''),
            ],
        ]);
    }




    #[Route('/api/demande/create-from-electeur', name: 'api_demande_create_from_electeur', methods: ['POST'])]
    public function createDemandeFromElecteur(
        Request $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository,
        EntityManagerInterface $em,
        MailService $mailService,
        FonctionsService $fonctionsService
    ): Response {

        // --- 1) Lire données formulaire (multipart) ---
        $numeroElecteur = trim((string) $request->request->get('numeroElecteur'));
        $email = trim((string) $request->request->get('email')); // requis
        $typeDemande = (string) $request->request->get('typeDemande');     // Attribution / Régularisation / Authentification
        $typeTitre = (string) $request->request->get('typeTitre');       // Permis d'occuper / Bail communal / ...
        $typeDocument = (string) $request->request->get('typeDocument', 'CNI');
        $localiteId = (int) $request->request->get('localiteId');
        $superficie = $request->request->get('superficie');
        $usagePrevu = (string) $request->request->get('usagePrevu', '');
        $possedeAutreTerrain = filter_var($request->request->get('possedeAutreTerrain', 'false'), FILTER_VALIDATE_BOOLEAN);
        $terrainAKaolack = $request->request->has('terrainAKaolack') ? filter_var($request->request->get('terrainAKaolack'), FILTER_VALIDATE_BOOLEAN) : null;
        $terrainAilleurs = $request->request->has('terrainAilleurs') ? filter_var($request->request->get('terrainAilleurs'), FILTER_VALIDATE_BOOLEAN) : null;


        $adresse = (string) $request->request->get('adresse', '');
        $dateNaissanceStr = (string) $request->request->get('dateNaissance', '');
        $lieuNaissance = (string) $request->request->get('lieuNaissance', '');

        $profession = $request->request->get('profession');
        $adresse = $request->request->get('adresse');
        $numeroElecteur = $request->request->get('numeroElecteur');


        /** @var UploadedFile|null $recto */
        $recto = $request->files->get('recto');
        /** @var UploadedFile|null $verso */
        $verso = $request->files->get('verso');

        if (!$numeroElecteur) {
            return $this->json(['message' => 'Le NIN (numeroElecteur) est requis'], Response::HTTP_BAD_REQUEST);
        }
        if (!$email) {
            return $this->json(['message' => 'L’email est requis'], Response::HTTP_BAD_REQUEST);
        }
        if (!$typeDemande || !$localiteId || !$superficie) {
            return $this->json(['message' => 'Champs demande manquants (typeDemande, localiteId, superficie)'], Response::HTTP_BAD_REQUEST);
        }
        if (!$recto || !$verso) {
            return $this->json(['message' => 'Recto et Verso sont requis'], Response::HTTP_BAD_REQUEST);
        }

        // --- 2) Récup électeur à partir du NIN ---
        $electeur = $fonctionsService->fetchDataElecteur($numeroElecteur);
        if (!$electeur) {
            return $this->json(['message' => 'Électeur introuvable pour ce NIN'], Response::HTTP_NOT_FOUND);
        }

        // --- 3) Trouver/Créer l’utilisateur demandeur ---
        // Match par email OU par numeroElecteur
        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $user = $userRepository->findOneBy(['numeroElecteur' => $numeroElecteur]);
        }

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($email);
            $user->setRoles(User::ROLE_DEMANDEUR);
            $user->setEnabled(true);
            $user->setActiveted(false);
            $user->setTokenActiveted(bin2hex(random_bytes(32)));

            // Hydrate avec les infos électeur disponibles
            $user->setNom($electeur['NOM'] ?? null);
            $user->setPrenom($electeur['PRENOM'] ?? null);
            $user->setTelephone($electeur['TEL1'] ?? ($electeur['TEL2'] ?? ($electeur['WHATSAPP'] ?? null)));
            $user->setNumeroElecteur($numeroElecteur);

            if (!empty($dateNaissanceStr)) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateNaissanceStr);
                $user->setDateNaissance($date);
            }

            $user->setLieuNaissance($lieuNaissance);
            $user->setProfession($profession ?? $electeur['PROFESSION']);
            $user->setAdresse($adresse ?? $electeur['ADRESSE']);

            // mot de passe initial
            $pwd = $user->generatePassword(8);
            $user->setPassword(password_hash($pwd, PASSWORD_BCRYPT));
            $user->setPasswordClaire($pwd);

            $em->persist($user);
        } else {
            // Mise à jour des infos manquantes à partir de l’électeur (sans écraser ce que l’utilisateur a déjà saisi)
            if (!$user->getNumeroElecteur())
                $user->setNumeroElecteur($numeroElecteur);
            if (!$user->getNom() && !empty($electeur['NOM']))
                $user->setNom($electeur['NOM']);
            if (!$user->getPrenom() && !empty($electeur['PRENOM']))
                $user->setPrenom($electeur['PRENOM']);
            if (!$user->getTelephone() && (!empty($electeur['TEL1']) || !empty($electeur['TEL2']) || !empty($electeur['WHATSAPP']))) {
                $user->setTelephone($electeur['TEL1'] ?? ($electeur['TEL2'] ?? ($electeur['WHATSAPP'] ?? null)));
            }
            $em->persist($user);
        }

        // --- 4) Localité ---
        $localite = $localiteRepository->find($localiteId);
        if (!$localite) {
            return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // --- 5) Création de la demande ---
        $demande = new DemandeTerrain();
        $demande->setUtilisateur($user);
        $demande->setLocalite($localite);

        // Champs métier avec validations de l’entité
        $demande->setTypeDemande($typeDemande);
        if ($typeTitre) {
            $demande->setTypeTitre($typeTitre);
        }
        $demande->setTypeDocument($typeDocument);
        $demande->setSuperficie((float) $superficie);
        $demande->setUsagePrevu($usagePrevu ?: null);
        $demande->setPossedeAutreTerrain($possedeAutreTerrain);
        $demande->setTerrainAKaolack($terrainAKaolack);
        $demande->setTerrainAilleurs($terrainAilleurs);

        // Statut initial (déjà En attente dans le constructeur), dates
        $demande->setDateCreation(new \DateTime());
        $demande->setDateModification(new \DateTime());

        // --- 6) Fichiers (recto/verso) ---
        $documentDir = $this->getParameter('document_directory');

        $buildFileName = function (string $suffix, UploadedFile $file) use ($demande): string {
            return sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($demande->getTypeDocument() ?? 'doc')),
                $demande->getTypeDemande() ? str_replace(' ', '-', strtolower($demande->getTypeDemande())) : date('YmdHis'),
                $suffix,
                $demande->getUtilisateur() ? str_replace(' ', '-', strtolower($demande->getUtilisateur()->getEmail())) : date('YmdHis'),
                $file->guessExtension() ?: 'pdf'
            );
        };

        // Recto
        $rectoName = $buildFileName('recto', $recto);
        $recto->move($documentDir, $rectoName);
        $demande->setRecto($documentDir . "/" . $rectoName);

        // Verso
        $versoName = $buildFileName('verso', $verso);
        $verso->move($documentDir, $versoName);
        $demande->setVerso($documentDir . "/" . $versoName);

        // --- 7) Persistance + mails ---
        $em->persist($demande);
        $em->flush();

        // Notification
        try {
            $mailService->sendDemandeMail($demande);
        } catch (\Throwable $e) {
            return $this->json(
                ['message' => 'Demande créée, mais échec envoi email: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // --- 8) Réponse ---
        return $this->json([
            'message' => 'Demande créée avec succès depuis un électeur',
            'electeur' => [
                'NIN' => $electeur['NIN'] ?? null,
                'NUMERO' => $electeur['NUMERO'] ?? null,
                'NOM' => $electeur['NOM'] ?? null,
                'PRENOM' => $electeur['PRENOM'] ?? null,
                'TEL1' => $electeur['TEL1'] ?? null,
                'TEL2' => $electeur['TEL2'] ?? null,
                'WHATSAPP' => $electeur['WHATSAPP'] ?? null,
                'CENTRE' => $electeur['CENTRE'] ?? null,
                'BUREAU' => $electeur['BUREAU'] ?? null,
            ],
            'demande' => [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeTitre' => $demande->getTypeTitre(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'terrainAKaolack' => $demande->isTerrainAKaolack(),
                'terrainAilleurs' => $demande->isTerrainAilleurs(),
                'statut' => $demande->getStatut(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'dateCreation' => $demande->getDateCreation()?->format('Y-m-d H:i:s'),
                'localite' => $demande->getLocalite()?->toArray(),
                'demandeur' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'numeroElecteur' => $user->getNumeroElecteur(),
                ],
            ],
        ], Response::HTTP_CREATED);
    }



    #[Route('/api/demande/{id}/update-rapport', name: 'api_demande_update_rapport', methods: ['PUT'])]
    public function updateRapport(
        int $id,
        Request $request,
        DemandeTerrainRepository $demandeRepository,
        HistoriqueValidationRepository $historiqueValidationRepository
    ): Response {
        $demande = $demandeRepository->find($id);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['rapport']) || empty($data['rapport'])) {
            return $this->json(['message' => 'Le rapport est requis et ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }
        $userId = $data['userId'];
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user){
            return $this->json(['message' => 'Utilisateur non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $demande->setRapport($data['rapport']);
        $demande->setDateModification(new \DateTime());

        $this->em->persist($demande);

        $historiqueExistant = $historiqueValidationRepository
                    ->findOneBy(['demande' => $demande, 'validateur' => $user]);

        if (!$historiqueExistant) {
            $historique = new HistoriqueValidation();
            $historique->setDemande($demande);
            $historique->setAction('VALIDER');
            $historique->setValidateur($user);
            $this->em->persist($historique);
          
        }else{
            $historiqueExistant->setDemande($demande);
            $historiqueExistant->setValidateur($user);
            $historiqueExistant->setAction('VALIDER');
            $this->em->persist($historiqueExistant);
        }
        $this->em->flush();


        return $this->json([
            'message' => 'Rapport mis à jour avec succès',
            'rapport' => $demande->getRapport()
        ], Response::HTTP_OK);
    }

    #[Route('/api/demande/{id}/update-decision-commission', name: 'api_demande_update_decision_commission', methods: ['PUT'])]
    public function updateDecisionCommission(
        int $id,
        Request $request,
        DemandeTerrainRepository $demandeRepository
    ): Response {
        $demande = $demandeRepository->find($id);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['decisionCommission']) || empty($data['decisionCommission'])) {
            return $this->json(['message' => 'La décision de la commission est requise et ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $demande->setDecisionCommission($data['decisionCommission']);
        $demande->setDateModification(new \DateTime());

        $this->em->persist($demande);
        $this->em->flush();

        return $this->json([
            'message' => 'Décision de la commission mise à jour avec succès',
            'decisionCommission' => $demande->getDecisionCommission()
        ], Response::HTTP_OK);
    }

    #[Route('/api/demande/{id}/update-recommandation', name: 'api_demande_update_recommandation', methods: ['PUT'])]
    public function updateRecommandation(
        int $id,
        Request $request,
        DemandeTerrainRepository $demandeRepository
    ): Response {
        $demande = $demandeRepository->find($id);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['recommandation']) || empty($data['recommandation'])) {
            return $this->json(['message' => 'La recommandation est requise et ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        $demande->setRecommandation($data['recommandation']);
        $demande->setDateModification(new \DateTime());

        $this->em->persist($demande);
        $this->em->flush();


        return $this->json([
            'message' => 'Recommandation mise à jour avec succès',
            'recommandation' => $demande->getRecommandation()
        ], Response::HTTP_OK);
    }

}

