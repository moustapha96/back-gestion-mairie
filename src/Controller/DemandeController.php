<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Request as EntityRequest;
use App\Repository\LocaliteRepository;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use App\services\AttributionMailer;
use App\services\FonctionsService;
use App\services\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

class DemandeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailService $mailService,
        private AttributionMailer $attribMailer,
    ) {
    }

    #[Route('/api/demande/nouvelle-demande', name: 'api_demande_nouvelle_demande', methods: ['POST'])]

    public function createDemande(
        HttpRequest $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository
    ): Response {
        
        // --------- 0) Helpers locaux ----------
        $sanitize = static function (string $s): string {
            $s = strtolower(trim($s));
            $s = str_replace([' ', '@'], ['-', 'at'], $s);
            return preg_replace('/[^a-z0-9\-_.]/', '-', $s);
        };
        $makePublicDocPath = function (string $filename): string {
            // => /documents/xxx.ext  (paramètre: app.upload.documents_dir = %kernel.project_dir%/public/documents)
            return '/documents/' . ltrim($filename, '/');
        };
        $makeAbsoluteUrl = function (HttpRequest $req, string $webPath): string {
            return $req->getScheme() . '://' . $req->getHttpHost() . $webPath;
        };

        // --------- 1) Lecture inputs ----------
        // NB: on reste en form-data ici, mais tu peux unifier JSON+multipart si besoin
        $superficie = $request->request->get('superficie') ?? null;
        $usagePrevu = $request->request->get('usagePrevu') ?? null;
        $localiteId = $request->request->get('localiteId');
        $typeDemande = $request->request->get('typeDemande') ?? null;
        $typeDocument = $request->request->get('typeDocument') ?? null;
        $typeTitre = $request->request->get('typeTitre') ?? null;
        $possedeAutreTerrain = $request->request->get('possedeAutreTerrain') ?? null;
        $terrainAilleurs = $request->request->get('terrainAilleurs') ?? null;
        $terrainAKaolack = $request->request->get('terrainAKaolack') ?? null;

        // Données "demandeur" (sans userId)
        $prenom = $request->request->get('prenom');
        $nom = $request->request->get('nom');
        $email = $request->request->get('email');
        $telephone = $request->request->get('telephone');
        $profession = $request->request->get('profession');
        $adresse = $request->request->get('adresse');
        $lieuNaissance = $request->request->get('lieuNaissance');
        $dateNaissance = $request->request->get('dateNaissance');  // yyyy-mm-dd (si fourni)
        $numeroElecteur = $request->request->get('numeroElecteur');
        $nombreEnfant = $request->request->get('nombreEnfant');
        $situationMatrimoniale = $request->request->get('situationMatrimoniale');
        $situationDemandeur = $request->request->get('situationDemandeur');

        /** @var UploadedFile|null $recto */
        $recto = $request->files->get('recto');
        /** @var UploadedFile|null $verso */
        $verso = $request->files->get('verso');

        // --------- 2) Validations minimales ----------
        if (!$typeDemande || !$localiteId) {
            return $this->json(['message' => 'Données manquantes ou invalides (typeDemande, localiteId)'], Response::HTTP_BAD_REQUEST);
        }
        // On se base sur l’email pour retrouver/créer l’utilisateur
        if (!$email) {
            return $this->json(['message' => 'Email requis pour créer ou retrouver le demandeur'], Response::HTTP_BAD_REQUEST);
        }

        // --------- 3) Localité ----------
        $localite = $localiteRepository->find($localiteId);
        if (!$localite) {
            return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // --------- 4) Utilisateur : retrouver ou créer ----------
        $utilisateur = $userRepository->findOneBy(['email' => $email]);
        $userExiste = (bool) $utilisateur;

        if (!$utilisateur) {
            $utilisateur = new User();
            $utilisateur->setPrenom($prenom);
            $utilisateur->setNom($nom);
            $utilisateur->setEmail($email);
            $utilisateur->setUsername($email);
            $utilisateur->setTelephone($telephone);
            $utilisateur->setRoles(User::ROLE_DEMANDEUR);
            $utilisateur->setProfession($profession);
            $utilisateur->setAdresse($adresse);
            $utilisateur->setLieuNaissance($lieuNaissance);
            if ($dateNaissance) {
                try {
                    $utilisateur->setDateNaissance(new \DateTime($dateNaissance));
                } catch (\Throwable $e) { /* optionnel */
                }
            }
            $utilisateur->setNumeroElecteur($numeroElecteur);
            $utilisateur->setNombreEnfant($nombreEnfant !== null ? (int) $nombreEnfant : null);
            $utilisateur->setSituationMatrimoniale($situationMatrimoniale);
            $utilisateur->setSituationDemandeur($situationDemandeur);
            // mot de passe par défaut (si account à activer plus tard)
            $utilisateur->setPassword(\password_hash('Password123!', PASSWORD_BCRYPT));
            $utilisateur->setPasswordClaire('Password123!');
            $utilisateur->setTokenActiveted(bin2hex(random_bytes(32)));
            $utilisateur->setActiveted(false);
            $utilisateur->setEnabled(true);
            $rawPassword = "Password123!";
            $this->em->persist($utilisateur);
        }

        // --------- 5) Créer la Demande ----------
        $demande = new EntityRequest();
        $demande
            ->setSuperficie($superficie !== null ? (float) $superficie : 0)
            ->setTypeDemande($typeDemande)
            ->setUsagePrevu($usagePrevu)
            ->setUtilisateur($utilisateur)
            ->setTypeDocument($typeDocument)
            ->setTypeTitre($typeTitre)
            ->setTerrainAKaolack($terrainAKaolack !== null ? filter_var($terrainAKaolack, FILTER_VALIDATE_BOOLEAN) : null)
            ->setDateCreation(new \DateTime())
            ->setStatut(EntityRequest::STATUT_EN_ATTENTE)
            ->setPossedeAutreTerrain($possedeAutreTerrain !== null ? filter_var($possedeAutreTerrain, FILTER_VALIDATE_BOOLEAN) : null)
            ->setMotifRefus(null)
            ->setDecisionCommission(null)
            ->setRapport(null)
            ->setTerrainAilleurs($terrainAilleurs !== null ? filter_var($terrainAilleurs, FILTER_VALIDATE_BOOLEAN) : null)
            ->setRecommandation(null)
            ->setLocalite($localite->getNom() ?? null)
            ->setQuartier($localite);

        // --------- 6) Upload fichiers (optionnels ici ; rends-les obligatoires si nécessaire) ----------
        $uploadDir = $this->getParameter('app.upload.documents_dir'); // .../public/documents
        $emailSlug = $email ? $sanitize($email) : 'non-renseigne';
        $rectoUrl = null;
        $versoUrl = null;

        if ($recto) {
            $rectoName = sprintf(
                '%s-%s-%s-%s.%s',
                $sanitize($typeDocument ?? 'doc'),
                $sanitize($typeDemande ?? date('YmdHis')),
                'recto',
                $emailSlug,
                $recto->guessExtension() ?: 'pdf'
            );
            $recto->move($uploadDir, $rectoName);
            $webPath = $makePublicDocPath($rectoName);      // /documents/xxx
            $demande->setRecto($webPath);
            $rectoUrl = $makeAbsoluteUrl($request, $webPath);
        }

        if ($verso) {
            $versoName = sprintf(
                '%s-%s-%s-%s.%s',
                $sanitize($typeDocument ?? 'doc'),
                $sanitize($typeDemande ?? date('YmdHis')),
                'verso',
                $emailSlug,
                $verso->guessExtension() ?: 'pdf'
            );
            $verso->move($uploadDir, $versoName);
            $webPath = $makePublicDocPath($versoName);
            $demande->setVerso($webPath);
            $versoUrl = $makeAbsoluteUrl($request, $webPath);
        }

        $this->em->persist($demande);
        $this->em->flush();

        // --------- 7) Notification ----------
        try {
            // même approche que tes autres mails : simple et robuste
            $this->attribMailer->notifyDemandeCreationToNewUser($demande, [
                'isNewUser' => !$userExiste,
                'rawPassword' => $rawPassword,                       // null si user existant
                'activationToken' => $utilisateur->getTokenActiveted(),  // si tu gères l’activation front
            ]);

        } catch (\Throwable $e) {
            // on n’empêche pas la création si le mail échoue (log si besoin)
        }

        return $this->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->toArray(),
            'quartier' => $demande->getQuartier()->toArray(),
            'rectoUrl' => $rectoUrl,
            'versoUrl' => $versoUrl,
            'userExist' => $userExiste,
            'user' => $utilisateur->toArray(),
        ], Response::HTTP_CREATED);
    }
    // ------------------------------------------------------------

    #[Route('/api/demandes/traitement', name: 'api_demande_traitement', methods: ['GET'])]
    public function demandeTraitement(RequestRepository $requestRepo): Response
    {
        $demandes = $requestRepo->findBy(['statut' => EntityRequest::STATUT_EN_COURS_TRAITEMENT]);
        $out = [];
        foreach ($demandes as $d) {
            $out[] = [
                'id' => $d->getId(),
                'typeDemande' => $d->getTypeDemande(),
                'superficie' => $d->getSuperficie(),
                'usagePrevu' => $d->getUsagePrevu(),
                'possedeAutreTerrain' => $d->isPossedeAutreTerrain(),
                'statut' => $d->getStatut(),
                'recto' => $d->getRecto(),
                'verso' => $d->getVerso(),
                'motif_refus' => $d->getMotifRefus(),
                'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
                'dateModification' => $d->getDateModification()?->format('Y-m-d H:i:s'),
                'typeDocument' => $d->getTypeDocument(),
                'typeTitre' => $d->getTypeTitre(),
                'demandeur' => $d->getUtilisateur() ? [
                    'id' => $d->getUtilisateur()->getId(),
                    'nom' => $d->getUtilisateur()->getNom(),
                    'prenom' => $d->getUtilisateur()->getPrenom(),
                    'email' => $d->getUtilisateur()->getEmail(),
                    'telephone' => $d->getUtilisateur()->getTelephone(),
                    'lieuNaissance' => $d->getUtilisateur()->getLieuNaissance(),
                    'dateNaissance' => $d->getUtilisateur()->getDateNaissance()?->format('Y-m-d'),
                    'numeroElecteur' => $d->getUtilisateur()->getNumeroElecteur(),
                    'profession' => $d->getUtilisateur()->getProfession(),
                    'adresse' => $d->getUtilisateur()->getAdresse(),
                ] : null,
                // localité texte + objet quartier
                'localite' => $d->getLocalite(),
                'quartier' => $d->getQuartier()?->toArray(),
            ];
        }
        return $this->json($out, Response::HTTP_OK);
    }

    // ------------------------------------------------------------

    #[Route('/api/demande/user/{id}/liste', name: 'api_demande_user_liste', methods: ['GET'])]
    public function demandeUser(
        int $id,
        RequestRepository $requestRepo,
        UserRepository $userRepo
    ): Response {
        $user = $userRepo->find($id);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $demandes = $requestRepo->findBy(['utilisateur' => $user]);
        $out = [];
        foreach ($demandes as $d) {
            $out[] = [
                'id' => $d->getId(),
                'typeDemande' => $d->getTypeDemande(),
                'typeDocument' => $d->getTypeDocument(),
                'typeTitre' => $d->getTypeTitre(),
                'superficie' => $d->getSuperficie(),
                'usagePrevu' => $d->getUsagePrevu(),
                'possedeAutreTerrain' => $d->isPossedeAutreTerrain(),
                'statut' => $d->getStatut(),
                'recto' => $d->getRecto(),
                'verso' => $d->getVerso(),
                'motif_refus' => $d->getMotifRefus(),
                'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
                // localité texte + objet quartier
                'localite' => $d->getLocalite(),
                'quartier' => $d->getQuartier()?->toArray(),
                'demandeur' => [
                    'id' => $user->getId(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'email' => $user->getEmail(),
                    'telephone' => $user->getTelephone(),
                    'lieuNaissance' => $user->getLieuNaissance(),
                    'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
                    'numeroElecteur' => $user->getNumeroElecteur(),
                    'profession' => $user->getProfession(),
                    'adresse' => $user->getAdresse(),
                    'isHabitant' => (bool) $user->isHabitant(),
                ],
            ];
        }

        return $this->json($out, Response::HTTP_OK);
    }

    // ------------------------------------------------------------

    #[Route('/api/demande/file/{demandeId}', name: 'api_demande_user_get_file', methods: ['GET'])]
    public function demandeUserDocument(int $demandeId, RequestRepository $requestRepo): Response
    {
        $demande = $requestRepo->find($demandeId);
        if (!$demande) {
            return $this->json(['message' => 'Demande non trouvée'], Response::HTTP_NOT_FOUND);
        }
        $recto = $demande->getRecto();
        $verso = $demande->getVerso();
        if (!$recto || !$verso) {
            return $this->json(['message' => 'Fichier non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $mimeRecto = @mime_content_type($recto);
        $mimeVerso = @mime_content_type($verso);
        if ($mimeRecto !== 'application/pdf' || $mimeVerso !== 'application/pdf') {
            return $this->json(['message' => 'Les fichiers doivent être des PDF'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        try {
            $base64Recto = base64_encode(file_get_contents($recto));
            $base64Verso = base64_encode(file_get_contents($verso));
            return $this->json(['recto' => $base64Recto, 'verso' => $base64Verso], Response::HTTP_OK);
        } catch (\Throwable $e) {
            return $this->json(['message' => 'Erreur lecture/encodage fichier', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // ------------------------------------------------------------

    #[Route('/api/demande/create-from-electeur', name: 'api_demande_create_from_electeur', methods: ['POST'])]
    public function createDemandeFromElecteur(
        Request $request,
        UserRepository $userRepository,
        LocaliteRepository $localiteRepository,
        EntityManagerInterface $em,
        MailService $mailService,
        FonctionsService $fonctionsService
    ): Response {
        // --- 1) Inputs
        $numeroElecteur = trim((string) $request->request->get('numeroElecteur'));
        $email = trim((string) $request->request->get('email'));
        $typeDemande = (string) $request->request->get('typeDemande');
        $typeTitre = (string) $request->request->get('typeTitre');
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
        $nombreEnfants = (int) $request->request->get('nombreEnfants', 0);
        $situationMatrimoniale = (string) $request->request->get('situationMatrimoniale', '');
        $situationDemandeur = (string) $request->request->get('statutLogement', '');

        if (!$numeroElecteur)
            return $this->json(['message' => 'Le NIN (numeroElecteur) est requis'], Response::HTTP_BAD_REQUEST);
        if (!$email)
            return $this->json(['message' => 'L’email est requis'], Response::HTTP_BAD_REQUEST);
        if (!$typeDemande || !$localiteId)
            return $this->json(['message' => 'Champs manquants (typeDemande, localiteId)'], Response::HTTP_BAD_REQUEST);

        // --- 2) Électeur
        $electeur = $fonctionsService->fetchDataElecteur($numeroElecteur);
        if (!$electeur) {
            return $this->json(['message' => 'Électeur introuvable pour ce NIN'], Response::HTTP_NOT_FOUND);
        }

        // --- 3) Utilisateur
        $user = $userRepository->findOneBy(['email' => $email]) ?: $userRepository->findOneBy(['numeroElecteur' => $numeroElecteur]);
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($email);
            $user->setRoles(User::ROLE_DEMANDEUR);
            $user->setEnabled(true);
            $user->setActiveted(false);
            $user->setTokenActiveted(bin2hex(random_bytes(32)));

            $user->setNom($electeur['NOM'] ?? null);
            $user->setPrenom($electeur['PRENOM'] ?? null);
            $user->setTelephone($electeur['TEL1'] ?? ($electeur['TEL2'] ?? ($electeur['WHATSAPP'] ?? null)));
            $user->setNumeroElecteur($numeroElecteur);
            $user->setNombreEnfant($nombreEnfants ?? 0);
            $user->setSituationMatrimoniale($situationMatrimoniale ?? null);
            $user->setSituationDemandeur($situationDemandeur ?? null);

            if (!empty($dateNaissanceStr)) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateNaissanceStr) ?: new \DateTime($dateNaissanceStr);
                $user->setDateNaissance($date);
            }
            $user->setLieuNaissance($lieuNaissance);
            $user->setProfession($profession ?? ($electeur['PROFESSION'] ?? null));
            $user->setAdresse($adresse ?? ($electeur['ADRESSE'] ?? null));

            $pwd = $user->generatePassword(8);
            $user->setPassword(password_hash($pwd, PASSWORD_BCRYPT));
            $user->setPasswordClaire($pwd);

            $em->persist($user);
        } else {
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

        // --- 4) Localité
        $quartier = $localiteRepository->find($localiteId);
        if (!$quartier) {
            return $this->json(['message' => 'Localité non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // --- 5) Création Request
        $demande = new EntityRequest();
        $demande->setUtilisateur($user);
        $demande->setQuartier($quartier);
        $demande->setLocalite($quartier->getNom()); // texte

        $demande->setTypeDemande($typeDemande);
        if ($typeTitre)
            $demande->setTypeTitre($typeTitre);
        $demande->setTypeDocument($typeDocument);
        $demande->setSuperficie((float) $superficie);
        $demande->setUsagePrevu($usagePrevu ?: null);
        $demande->setPossedeAutreTerrain($possedeAutreTerrain);
        $demande->setTerrainAKaolack($terrainAKaolack);
        $demande->setTerrainAilleurs($terrainAilleurs);

        $demande->setDateCreation(new \DateTime());
        $demande->setDateModification(new \DateTime());

        // Fichiers (optionnels ici)
        /** @var UploadedFile|null $recto */
        $recto = $request->files->get('recto');
        /** @var UploadedFile|null $verso */
        $verso = $request->files->get('verso');

        $documentDir = $this->getParameter('document_directory');
        $buildFileName = function (string $suffix, UploadedFile $file) use ($demande, $user): string {
            return sprintf(
                '%s-%s-%s-%s.%s',
                str_replace(' ', '-', strtolower($demande->getTypeDocument() ?? 'doc')),
                $demande->getTypeDemande() ? str_replace(' ', '-', strtolower($demande->getTypeDemande())) : date('YmdHis'),
                $suffix,
                str_replace(' ', '-', strtolower($user->getEmail() ?? 'anon')),
                $file->guessExtension() ?: 'pdf'
            );
        };

        if ($recto) {
            $rectoName = $buildFileName('recto', $recto);
            $recto->move($documentDir, $rectoName);
            $demande->setRecto($documentDir . "/" . $rectoName);
        }
        if ($verso) {
            $versoName = $buildFileName('verso', $verso);
            $verso->move($documentDir, $versoName);
            $demande->setVerso($documentDir . "/" . $versoName);
        }

        $demande->setUtilisateur($user);
        $em->persist($demande);
        $em->flush();

        try {
            $mailService->sendDemandeMail($demande);
        } catch (\Throwable $e) {
            return $this->json(['message' => 'Demande créée, email non envoyé: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

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
                'localite' => $demande->getLocalite(),
                'quartier' => $demande->getQuartier()?->toArray(),
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
}
