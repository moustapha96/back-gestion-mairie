<?php

namespace App\Controller;

use App\Entity\DemandeTerrain;
use App\Entity\User;
use App\Entity\DocumentGenere;
use App\Repository\DemandeTerrainRepository;
use App\Repository\LocaliteRepository;
use App\Repository\UserRepository;
use App\services\FonctionsService;
use App\services\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        LocaliteRepository $localiteRepository
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



        $user = $userRepository->findOneBy(["email" => $email]);
        if (!$user) {
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
            ->setStatut(DemandeTerrain::STATUT_EN_COURS)
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

        $documentGenere = $this->genererDocument($demande);
        $documentGenere->setDemandeTerrain($demande);
        $this->em->persist($documentGenere);
        $demande->setDocumentGenerer($documentGenere);
        $this->mailService->sendDemandeMail($demande);
        $this->em->flush();

        return $this->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->toArray(),
            'localite' => $localite->toArray()
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
            ->setDateCreation(new \DateTime())
            ->setStatut(DemandeTerrain::STATUT_EN_COURS)
            ->setPossedeAutreTerrain($possedeAutreTerrain)
            ->setMotifRefus(null)
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


        $documentGenere = $this->genererDocument($demande);
        $documentGenere->setDemandeTerrain($demande);
        $this->em->persist($documentGenere);
        $demande->setDocumentGenerer($documentGenere);

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
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance(),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
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
                ] : null,
                'localite' => $localite ? $localite->toArray() : null,
                'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null
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
        LocaliteRepository $localiteRepository,
        UserRepository $userRepository
    ): Response {

        $demandes = $demandeRepository->findAll();
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
                'documentGenerer' => $demande->getDocumentGenerer() ? $demande->getDocumentGenerer()->toArray() : null
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
        $this->em->persist($demande);
        $this->mailService->sendStatusChangeMail($demande);
        $this->em->flush();

        return $this->json("Statut mis à jour", Response::HTTP_OK);
    }


    function cleanUtf8($text)
    {
        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xA0-\xFF]/', '', $text);
    }



    #[Route('/api/demande/import', name: 'api_demande_import', methods: ['POST'])]
    public function importerDemandes(
        Request $request,
        FonctionsService $fonctionsService,
        LocaliteRepository $localiteRepository,
        UserRepository $userRepository
    ): Response {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Fichier non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($file->getClientOriginalExtension() !== 'xlsx') {
            return $this->json(['error' => 'Format de fichier incorrect'], Response::HTTP_BAD_REQUEST);
        }

        $expectedHeaders = [
            'CNI',
            'Email',
            'Nom',
            'Prenom',
            'Telephone',
            'Adresse',
            'Lieu de Naissance',
            'Date de Naissance',
            'Profession',
            'Type de demande',
            'Localite',
            'Superficie',
            'Usage prevu',
            'Date Demande'
        ];

        try {
            // Lire le fichier Excel
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                return $this->json(['error' => 'Le fichier est vide'], Response::HTTP_BAD_REQUEST);
            }

            // Lecture de l'en-tête
            $headers = array_shift($rows);

            if (array_diff($expectedHeaders, $headers)) {
                return $this->json([
                    'error' => 'Format de fichier incorrect',
                    'attendu' => $expectedHeaders,
                    'reçu' => $headers
                ], Response::HTTP_BAD_REQUEST);
            }

            $batchSize = 20;
            $count = 0;

            foreach ($rows as $row) {
                if (count($row) < count($expectedHeaders)) {
                    continue; // Ignore les lignes incomplètes
                }

                [
                    $cni,
                    $email,
                    $nom,
                    $prenom,
                    $telephone,
                    $adresse,
                    $lieuDeNaissance,
                    $dateNaissance,
                    $profession,
                    $typeDemande,
                    $localite,
                    $superficie,
                    $usagePrevu,
                    $dateDemande
                ] = $row;

                // Vérification et normalisation des données
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($cni) || empty($nom) || empty($localite)) {
                    continue;
                }

                $localiteTrouve = $localiteRepository->findOneBy(['nom' => $localite]);
                if (!$localiteTrouve) {
                    continue;
                }

                $utilisateur = $userRepository->findOneBy(['email' => $email, 'numeroElecteur' => $cni]);
                if (!$utilisateur) {
                    $utilisateur = new User();
                    $utilisateur->setNom($nom);
                    $utilisateur->setEmail($email);
                    $utilisateur->setPrenom($prenom);
                    $utilisateur->setAdresse($adresse);
                    $utilisateur->setTelephone($telephone);
                    $utilisateur->setProfession($profession);
                    $utilisateur->setNumeroElecteur($cni);
                    $utilisateur->setLieuNaissance($lieuDeNaissance);
                    $utilisateur->setDateNaissance(new \DateTime($dateNaissance));
                    $utilisateur->setEnabled(true);
                    $utilisateur->setActiveted(false);
                    $utilisateur->setRoles(User::ROLE_DEMANDEUR);
                    $passwordGenere = $utilisateur->generatePassword(8);
                    $utilisateur->setPassword(password_hash($passwordGenere, PASSWORD_BCRYPT));
                    $utilisateur->setPasswordClaire($passwordGenere);
                    $utilisateur->setTokenActiveted(bin2hex(random_bytes(32)));
                    $utilisateur->setUsername($email);

                    // VERIFIER SI C'EST UN HABITANT
                    $resultat = $fonctionsService->checkNumeroElecteurExist($cni);
                    $utilisateur->setHabitant($resultat ?? false);
                    $this->em->persist($utilisateur);
                }

                $demande = new DemandeTerrain();
                $demande->setLocalite($localiteTrouve);
                $demande->setSuperficie($superficie);
                $demande->setTypeDemande($typeDemande);
                $demande->setUsagePrevu($usagePrevu);
                $demande->setDateCreation(new \DateTime($dateDemande));
                $demande->setDateModification(new \DateTime($dateDemande));
                $demande->setStatut(DemandeTerrain::STATUT_EN_COURS);
                $demande->setPossedeAutreTerrain(false);
                $demande->setTypeDocument('CNI');
                $demande->setDocumentGenerer(null);
                $demande->setUtilisateur($utilisateur);
                $demande->setRecto(null);
                $demande->setVerso(null);

                $documentGenere = $this->genererDocument($demande);
                $documentGenere->setDemandeTerrain($demande);
                $this->em->persist($documentGenere);
                $demande->setDocumentGenerer($documentGenere);
                $this->em->persist($demande);
                if (($count % $batchSize) === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
                $count++;
            }

            $this->em->flush();

            return $this->json(['message' => 'Importation terminée', 'total' => $count], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'importation : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
                    'lieuNaissance' => $demande->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $demande->getUtilisateur()->getDateNaissance(),
                    'numeroElecteur' => $demande->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $demande->getUtilisateur()->getProfession(),
                    'adresse' => $demande->getUtilisateur()->getAdresse(),
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


    #[Route('/api/demande/{id}/update', name: 'api_demande_update', methods: ['POST'])]
    public function updateDemande(
        $id,
        DemandeTerrainRepository $demandeRepository,
        Request $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository,
        EntityManagerInterface $entityManager // Assurez-vous d'injecter l'EntityManager
    ): Response {
        $demande = $demandeRepository->find($id);

        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Mise à jour des champs si fournis
        $userId = $request->request->get('userId');
        if ($userId) {
            $user = $userRepository->find($userId);
            if (!$user) {
                return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }
        }
        // dd($request);

        // Mise à jour des champs de base
        $demande->setSuperficie($request->request->get('superficie') ?? $demande->getSuperficie());
        $demande->setUsagePrevu($request->request->get('usagePrevu') ?? $demande->getUsagePrevu());
        $demande->setPossedeAutreTerrain($request->request->get('possedeAutreTerrain') ?? $demande->isPossedeAutreTerrain());
        $demande->setTypeDemande($request->request->get('typeDemande') ?? $demande->getTypeDemande());
        $demande->setTypeDocument($request->request->get('typeDocument') ?? $demande->getTypeDocument());
        $demande->setStatut($request->request->get('statut') ?? $demande->getStatut());

        // Ajouter cette ligne pour le motif de refus
        $demande->setMotifRefus($request->request->get('motif_refus') ?? $demande->getMotifRefus());

        // Mise à jour de la localité - CORRECTION DE LA SYNTAXE
        if ($request->request->get('localiteId')) {
            $localite = $localiteRepository->find($request->request->get('localiteId'));
            if (!$localite) {
                return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
            }
            $demande->setLocalite($localite);
        }

        $demande->setDateModification(new \DateTime());

        // Gestion des fichiers recto et verso
        /** @var UploadedFile|null $recto */
        $recto = $request->files->get('recto');
        /** @var UploadedFile|null $verso */
        $verso = $request->files->get('verso');

        $documentDir = $this->getParameter('document_directory');

        if ($recto) {
            $newFilename = sprintf(
                '%s-%s-recto-%s.%s',
                str_replace(' ', '-', strtolower($demande->getTypeDocument())),
                $demande->getTypeDemande() ? str_replace(' ', '-', strtolower($demande->getTypeDemande())) : date('YmdHis'),
                str_replace(' ', '-', strtolower($demande->getUtilisateur()->getEmail())),
                $recto->guessExtension()
            );
            $recto->move($documentDir, $newFilename);
            $demande->setRecto($documentDir . "/" . $newFilename);
        }

        if ($verso) {
            $newFilename = sprintf(
                '%s-%s-verso-%s.%s',
                str_replace(' ', '-', strtolower($demande->getTypeDocument())),
                $demande->getTypeDemande() ? str_replace(' ', '-', strtolower($demande->getTypeDemande())) : date('YmdHis'),
                str_replace(' ', '-', strtolower($demande->getUtilisateur()->getEmail())),
                $verso->guessExtension()
            );
            $verso->move($documentDir, $newFilename);
            $demande->setVerso($documentDir . "/" . $newFilename);
        }

        // Utiliser $entityManager au lieu de $this->em
        $entityManager->persist($demande);
        $entityManager->flush();

        return $this->json([
            'message' => 'Demande mise à jour avec succès',
            'demande' => [
                'id' => $demande->getId(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'statut' => $demande->getStatut(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
                'motif_refus' => $demande->getMotifRefus(),
                'dateModification' => $demande->getDateModification()->format('Y-m-d H:i:s'),
                'localite' => $demande->getLocalite() ? $demande->getLocalite()->toArray() : null,
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
}
