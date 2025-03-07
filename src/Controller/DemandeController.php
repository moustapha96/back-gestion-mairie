<?php

namespace App\Controller;

use App\Entity\DemandeTerrain;
use App\Entity\User;
use App\Entity\DocumentGenere;
use App\Repository\DemandeTerrainRepository;
use App\Repository\LocaliteRepository;
use App\Repository\UserRepository;
use App\services\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Smalot\PdfParser\Parser;

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

        $file = $request->files->get('document');


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

        // if ($file) {
        //     $newFilename = sprintf(
        //         '%s-%s-%s-%s.%s',
        //         str_replace(' ', '-', strtolower($typeDocument)),
        //         $typeDemande ? str_replace(' ', '-', strtolower($typeDemande)) : date('YmdHis'),
        //         (new \DateTime())->format('Y-m-d'),
        //         $user ? str_replace(' ', '-', strtolower($user->getEmail())) : date('YmdHis'),
        //         $file->guessExtension()
        //     );
        //     $file->move($this->getParameter('document_directory'), $newFilename);
        //     $url = $this->getParameter('document_directory') . "/" . $newFilename;
        //     $demande->setDocument($url);
        // } else {
        //     return $this->json('Veuillez uploader un document', Response::HTTP_BAD_REQUEST);
        // }


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


    // liste des tout les demandes
    #[Route('/api/demande/liste', name: 'api_demande_liste', methods: ['GET'])]
    public function listeDemande(DemandeTerrainRepository $demandeRepository): Response
    {
        $demandes = $demandeRepository->findAll();
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
                ] : null,
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }

    // liste des demandes d'un demandeur
    #[Route('/api/demandes/demandeur/{id}', name: 'api_demande_demandeur', methods: ['GET'])]
    public function demandeDemandeur(int $id, LocaliteRepository $localiteRepository, DemandeTerrainRepository $demandeRepository, UserRepository $userRepository): Response
    {
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
        LocaliteRepository $localiteRepository
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
            ] : null,
            'localite' => $localite ? [
                'id' => $localite->getId(),
                'nom' => $localite->getNom(),
                'prix' => $localite->getPrix(),
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



    // #[Route('/api/demande/file/{demandeId}', name: 'api_demande_user_get_file', methods: ['GET'])]
    // public function demandeUserDocument(int $demandeId, DemandeTerrainRepository $demandeRepository): Response
    // {
    //     $demande = $demandeRepository->findOneBy(['id' => $demandeId]);
    //     if (!$demande) {
    //         return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
    //     }

    //     $recto = $demande->getRecto();
    //     $verso = $demande->getVerso();

    //     if (!$recto && !$verso) {
    //         return $this->json(['message' => 'Fichier non trouvé'], Response::HTTP_NOT_FOUND);
    //     }

    //     $parser = new Parser();

    //     try {
    //         $pdfRecto = $parser->parseFile($recto);
    //         $pdfVerso = $parser->parseFile($verso);

    //         // Convertir en UTF-8
    //         $textRecto = mb_convert_encoding($pdfRecto->getText(), 'UTF-8', 'auto');
    //         $textVerso = mb_convert_encoding($pdfVerso->getText(), 'UTF-8', 'auto');

    //         // Vérifier et nettoyer l'encodage
    //         if (!mb_check_encoding($textRecto, 'UTF-8')) {
    //             $textRecto = utf8_encode($textRecto);
    //         }
    //         if (!mb_check_encoding($textVerso, 'UTF-8')) {
    //             $textVerso = utf8_encode($textVerso);
    //         }

    //         // Lire les fichiers en base64
    //         $base64Recto = base64_encode(file_get_contents($recto));
    //         $base64Verso = base64_encode(file_get_contents($verso));

    //         return $this->json([
    //             'recto' => [
    //                 // 'content' => $base64Recto,
    //                 'text' => $textRecto
    //             ],
    //             'verso' => [
    //                 // 'content' => $base64Verso,
    //                 'text' => $textVerso
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->json(
    //             ['message' => 'Erreur lors de la lecture du fichier', 'error' => $e->getMessage()],
    //             Response::HTTP_INTERNAL_SERVER_ERROR
    //         );
    //     }
    // }
}
