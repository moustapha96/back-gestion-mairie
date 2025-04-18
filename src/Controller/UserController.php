<?php

// src/Controller/UserController.php

namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use App\services\FonctionsService;
use App\services\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends AbstractController
{

    private $security;
    private $mailService;
    private $fonctionsService;

    public function __construct(Security $security, MailService $mailService, FonctionsService $fonctionsService)
    {
        $this->security = $security;
        $this->mailService = $mailService;
        $this->fonctionsService = $fonctionsService;
    }

    #[Route('/api/user/create', name: 'api_users_creation', methods: ['POST'])]
    public function createUser(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
    ): Response {



        $data = json_decode($request->getContent(), true);

        $user = new User();
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return new Response(json_encode([
                'status' => 'error',
                'message' => 'Cet utilisateur existe déjà',
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setTokenActiveted(bin2hex(random_bytes(32)));
        // $user->setNumeroElecteur(null);
        $user->setDateNaissance(isset($data["dateNaissance"]) ? new \DateTime($data["dateNaissance"]) : null);
        $user->setLieuNaissance($data["lieuNaissance"] ?? null);
        $user->setTelephone($data["telephone"] ?? null);
        $user->setRoles($data['roles'] ?? [User::ROLE_DEMANDEUR]);
        $user->setUsername($data['email']);
        $user->setAdresse($data['adresse'] ?? null);
        $user->setPrenom($data["prenom"] ?? null);
        $user->setProfession($data['profession'] ?? null);
        $user->setEmail($data['email']);
        $user->setNom($data["nom"] ?? null);
        $user->setActiveted(false);
        $user->setEnabled(false);

        // $resultat = $this->fonctionsService->checkNumeroElecteurExist($cni);
        $user->setHabitant(false);
        $url = $data['url'] ?? null;

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response(json_encode(['errors' => (string) $errors]), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $userRepository->save($user, true);
        $this->mailService->sendWelcomeMail($user->getEmail(), $user->getTokenActiveted(), $url);
        return new Response(json_encode($user->toArray()), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }


    #[Route('/api/user/inscription', name: 'api_users_inscription', methods: ['POST'])]
    public function inscription(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
    ): Response {

        $data = json_decode($request->getContent(), true);
        // dd($data);
        $user = new User();
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return new JsonResponse([
                'message' => 'Cet utilisateur existe déjà',
            ], Response::HTTP_CONFLICT);
        }

        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setTokenActiveted(bin2hex(random_bytes(32)));
        // $user->setNumeroElecteur(null);
        $user->setDateNaissance(isset($data["dateNaissance"]) ? new \DateTime($data["dateNaissance"]) : null);
        $user->setLieuNaissance($data["lieuNaissance"] ?? null);
        $user->setTelephone($data["telephone"] ?? null);
        $user->setRoles($data['roles'] ?? User::ROLE_DEMANDEUR);
        $user->setUsername($data['email']);
        $user->setAdresse($data['adresse'] ?? null);
        $user->setPrenom($data["prenom"] ?? null);
        $user->setProfession($data['profession'] ?? null);
        $user->setEmail($data['email']);
        $user->setNom($data["nom"] ?? null);
        $user->setActiveted(false);
        $user->setEnabled(false);
        // $resultat = $this->fonctionsService->checkNumeroElecteurExist($cni);
        $user->setHabitant(false);

        $url = $data['url'] ?? null;

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response(json_encode(['errors' => (string) $errors]), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $userRepository->save($user, true);
        $this->mailService->sendWelcomeMail($user->getEmail(), $user->getTokenActiveted(), $url);
        return new JsonResponse(
            ["message" => "Veuillez verifier votre email pour activer votre compte"],
            Response::HTTP_CREATED,
            ['Content-Type' => 'application/json']
        );
    }

    #[Route('/api/verifier-compte/{token}', name: 'api_user_verifier_compte', methods: ['GET'])]
    public function verifierCompte(string $token, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['tokenActiveted' => $token]);
        if ($user) {
            $user->setActiveted(true);
            $user->setEnabled(true);
            $user->setTokenActiveted(null);
            $userRepository->save($user, true);
            return $this->json($user, Response::HTTP_OK);
        }
        return $this->json(['message' => 'Token invalide'], Response::HTTP_BAD_REQUEST);
    }

    //fonction pour desactiver un compte
    #[Route('/api/deactiver-compte/{idUser}', name: 'api_user_deactiver_compte', methods: ['GET'])]
    public function deactiverCompte(int $idUser,  UserRepository $userRepository): Response
    {
        $user = $userRepository->find($idUser);
        if ($user) {
            $user->setEnabled(false);
            $userRepository->save($user, true);
            return $this->json($user, Response::HTTP_OK);
        }
        return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_BAD_REQUEST);
    }

    // une fonctio pour permettre à l'utilisateur de modifier ces informations
    #[Route('/api/user/update-profile/{id}', name: 'api_users_mise_a_jour_compte', methods: ['PUT'])]
    public function modifierCompte(
        Request $request,
        int $id,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): Response {
        $user = $userRepository->find($id);

        if (!$user) {
            return new Response(json_encode(['message' => 'Utilisateur non trouvé']), Response::HTTP_NOT_FOUND, ['Content-Type' => 'application/json']);
        }

        $data = json_decode($request->getContent(), true);
        // Récupération des champs avec des valeurs par défaut
        $email = $data['email'] ?? $user->getEmail();
        $password = $data['password'] ?? null;
        $newPassword = $data['newPassword'] ?? null;
        $adresse = $data['adresse'] ?? $user->getAdresse();
        $telephone = $data['telephone'] ?? $user->getTelephone();
        $nom = $data['nom'] ?? $user->getNom();
        $prenom = $data['prenom'] ?? $user->getPrenom();
        $dateNaissance = isset($data['dateNaissance']) ? new \DateTime($data['dateNaissance']) : $user->getDateNaissance();
        $lieuNaissance = $data['lieuNaissance'] ?? $user->getLieuNaissance();
        $numeroElecteur = $data['numeroElecteur'] ?? $user->getNumeroElecteur();

        if ($email) {
            $user->setUsername($email);
        }

        // Mise à jour du mot de passe s'il y a un nouveau
        if ($newPassword && $password) {

            // Vérification du mot de passe actuel avant modification
            if ($password && !password_verify($password, $user->getPassword())) {
                return new Response(json_encode(['message' => 'Mot de passe incorrect']), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
            }

            if (strlen($newPassword) < 8) {
                return new Response(json_encode(['message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères']), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
            }
            $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        }

        // Mise à jour des informations utilisateur
        $user->setNom($nom);
        $user->setEmail($email);
        $user->setPrenom($prenom);
        $user->setAdresse($adresse);
        $user->setTelephone($telephone);
        $user->setDateNaissance($dateNaissance);
        $user->setLieuNaissance($lieuNaissance);
        $user->setNumeroElecteur($numeroElecteur);

        // Validation des nouvelles données
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response(json_encode(['errors' => (string) $errors]), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }
        $userRepository->save($user, true);

        return new Response(json_encode($user->toArray()), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }


    // create user with admin role
    #[Route('/api/user/create-admin', name: 'api_users_create_admin', methods: ['POST'])]
    public function createUserAdmin(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
    ): Response {
        $data = json_decode($request->getContent(), true);
        $user = new User();

        if (!isset($data['email'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Les champs email et username sont obligatoires.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Un utilisateur avec cet email existe déjà.'
            ], Response::HTTP_CONFLICT);
        }
        $user->setRoles(User::ROLE_ADMIN);
        $user->setNom($data['nom']);
        $user->setEmail($data['email']);
        $user->setPrenom($data['prenom']);
        $user->setAdresse($data['adresse']);
        $user->setUsername($data['email']);
        // profession   
        $user->setProfession($data['profession']);
        $user->setTelephone($data['telephone']);
        $user->setLieuNaissance($data['lieuNaissance']);
        $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        $user->setNumeroElecteur($data['numeroElecteur'] ?? null);
        $resultat = $this->fonctionsService->checkNumeroElecteurExist($data['numeroElecteur']);
        $user->setHabitant($resultat ?? false);

        $user->setActiveted(false);
        $user->setEnabled(false);
        $user->setTokenActiveted(bin2hex(random_bytes(32)));

        $passwordGenere = $user->generatePassword(8);
        $user->setPassword(password_hash($passwordGenere, PASSWORD_BCRYPT));
        $user->setPasswordClaire($passwordGenere);

        // dd($user);
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json([
                'status' => 'error',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $userRepository->save($user, true);
        $resultat = $this->mailService->sendAccountCreationMail($user, $passwordGenere);

        return $this->json(
            [
                'status' => 'success',
                'message' => 'Administrateur créé avec succès. Un email a été envoyé.',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'username' => $user->getUsername(),
                    'roles' => $user->getRoles(),
                    'adresse' => $user->getAdresse(),
                    'telephone' => $user->getTelephone(),
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'dateNaissance' => $user->getDateNaissance(),
                    'lieuNaissance' => $user->getLieuNaissance(),
                    'numeroElecteur' => $user->getNumeroElecteur()
                ]
            ],
            Response::HTTP_CREATED
        );
    }


    #[Route('/api/users/liste', name: 'api_users_liste', methods: ['GET'])]
    public function listeUser(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([
            'roles' => User::ROLE_DEMANDEUR
        ]);

        $resultats = [];
        foreach ($users as $user) {
            // Only include the fields you need, avoid including relationships
            $resultats[] = [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'prenom' => $user->getPrenom(),
                'enabled' => $user->isEnabled(),
                'adresse' => $user->getAdresse(),
                'isActive' => $user->isActiveted(),
                'username' => $user->getUsername(),
                'telephone' => $user->getTelephone(),
                'dateNaissance' => $user->getDateNaissance(),
                'lieuNaissance' => $user->getLieuNaissance(),
                'isHabitant' => $user->isHabitant(),
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }

    // update status user
    #[Route('/api/user/update-activated-status/{id}', name: 'api_users_update_status', methods: ['PUT'])]
    public function updateStatus($id, Request $request,  UserRepository $userRepository): Response
    {
        $isActive = json_decode($request->getContent(), true)['isActive'];
        $user = $userRepository->find($id);
        if ($user) {
            $user->setActiveted(boolval($isActive));
            $userRepository->save($user, true);

            $this->mailService->sendAccountStatusChangeEmail($user->getEmail(),  $isActive, $user);
            return $this->json('Utilisateur mis à jour', Response::HTTP_OK);
        }
        return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_BAD_REQUEST);
    }

    // activated-account
    #[Route('/api/user/activated-account/{token}', name: 'api_user_activated_account', methods: ['GET'])]
    public function activatedAccount($token, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['tokenActiveted' => $token]);
        if ($user) {
            $user->setActiveted(true);
            $user->setEnabled(true);
            $userRepository->save($user, true);
            return $this->json($user->toArray(), Response::HTTP_OK);
        } else {
            return $this->json(['message' => 'Compte non trouvé'], Response::HTTP_BAD_REQUEST);
        }
    }

    // /auth/status
    #[Route('/api/auth/status', name: 'api_user_auth_status', methods: ['GET'])]
    public function status(): Response
    {
        $data = [
            'url' => "/api/auth/status"
        ];

        return $this->json($data, Response::HTTP_OK);
    }


    #[Route('/api/user/upload-profile-picture', name: 'api_user_upload_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture(Request $request, UserRepository $userRepository): Response
    {
        $userId = $request->request->get('userId');
        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $file = $request->files->get('image');

        if (!$file) {
            return $this->json(['error' => 'Fichier non trouvé'], Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = sprintf(
            '%s-%s-%s.%s',
            $user->getId(),
            $user->getEmail(),
            'profile',
            'png'
        );

        try {
            $file->move($this->getParameter('profile_directory'), $newFilename);
            $user->setAvatar($newFilename);
            $userRepository->save($user, true);

            return $this->json([
                'message' => 'Profil mis à jour',
                'avatar' => $user->getAvatar(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du téléchargement de l\'image: ' . $e->getMessage()], Response::HTTP_NO_CONTENT);
        }
    }

    #[Route('/api/users/set-compte-enable/{id}', name: 'api_user_set_compte_enable', methods: ['PUT'])]
    public function updateCompteEnable($id, Request $request,  UserRepository $userRepository): Response
    {
        $enabled = json_decode($request->getContent(), true)['enabled'];
        $user = $userRepository->find($id);
        if ($user) {
            if ($enabled == "inactif") {
                $user->setActiveted(false);
                $user->setEnabled(false);
            } else {
                $user->setActiveted(true);
                $user->setEnabled(true);
            }
            $userRepository->save($user, true);

            if ($user->isActiveted()) {
                return $this->json("Compte activé avec success", Response::HTTP_OK);
            } else {
                return $this->json("Compte desactivé avec success", Response::HTTP_OK);
            }
        }
        return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
    }


    // methode de reccuperation des données d'un utilisateur
    #[Route('/api/user/{id}/details2', name: 'api_user_show_2', methods: ['GET'])]
    public function details2($id, UserRepository $userRepository, FonctionsService $fonctionsService): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        $resultat = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'activated' => $user->isActiveted(),
            'prenom' => $user->getPrenom(),
            'dateNaissance' => $user->getDateNaissance(),
            'lieuNaissance' => $user->getLieuNaissance(),
            'numeroElecteur' => $user->getNumeroElecteur(),
            'telephone' => $user->getTelephone(),
            'adresse' => $user->getAdresse(),
            'demandes' => $user->getDemandes(),
            'enabled' => $user->isEnabled(),
            'isHabitant' => $fonctionsService->checkNumeroElecteurExist($user->getNumeroElecteur()),
            // 'avatar' => $user->getAvatar(),
        ];
        return $this->json($resultat, Response::HTTP_OK);
    }

    #[Route('/api/user/{id}/details', name: 'api_user_show', methods: ['GET'])]
    public function details($id, UserRepository $userRepository, FonctionsService $fonctionsService): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);

        // Prepare demandes with localite information
        $demandesArray = [];
        foreach ($user->getDemandes() as $demande) {
            $demandeData = [
                'id' => $demande->getId(),
                'typeDemande' => $demande->getTypeDemande(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'possedeAutreTerrain' => $demande->isPossedeAutreTerrain(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getDateCreation(),
                'dateModification' => $demande->getDateModification(),
                'document' => $demande->getDocument(),
                'typeDocument' => $demande->getTypeDocument(),
                'recto' => $demande->getRecto(),
                'verso' => $demande->getVerso(),
            ];

            // Add localite information if available
            if ($demande->getLocalite()) {
                $localite = $demande->getLocalite();
                $demandeData['localite'] = [
                    'id' => $localite->getId(),
                    'nom' => $localite->getNom(),
                    'prix' => $localite->getPrix(),
                    'description' => $localite->getDescription(),
                    'latitude' => $localite->getLatitude(),
                    'longitude' => $localite->getLongitude(),
                ];
            } else {
                $demandeData['localite'] = null;
            }

            $demandesArray[] = $demandeData;
        }

        $resultat = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'activated' => $user->isActiveted(),
            'prenom' => $user->getPrenom(),
            'dateNaissance' => $user->getDateNaissance(),
            'lieuNaissance' => $user->getLieuNaissance(),
            'numeroElecteur' => $user->getNumeroElecteur(),
            'telephone' => $user->getTelephone(),
            'adresse' => $user->getAdresse(),
            'demandes' => $demandesArray,
            'enabled' => $user->isEnabled(),
            'isHabitant' => $fonctionsService->checkNumeroElecteurExist($user->getNumeroElecteur()),
            // 'avatar' => $user->getAvatar(),
        ];

        return $this->json($resultat, Response::HTTP_OK);
    }

    // la liste des user sauf admin
    #[Route('/api/user/liste', name: 'api_user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository, FonctionsService $fonctionsService): Response
    {
        $users = $userRepository->findAll();
        $resultat = [];
        foreach ($users as $user) {
            $isHabitant = $this->fonctionsService->checkNumeroElecteurExist($user->getNumeroElecteur());


            $resultat[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'roles' => $user->getRoles(),
                'prenom' => $user->getPrenom(),
                'dateNaissance' => $user->getDateNaissance(),
                'lieuNaissance' => $user->getLieuNaissance(),
                'numeroElecteur' => $user->getNumeroElecteur() ?? null,
                'telephone' => $user->getTelephone(),
                'adresse' => $user->getAdresse(),
                'enabled' => $user->isEnabled(),
                'activated' => $user->isActiveted(),
                'isHabitant' => $fonctionsService->checkNumeroElecteurExist($user->getNumeroElecteur()),
                // 'demandes' => $user->getDemandes(),
                'demandes' => count($user->getDemandes()),
                'passwordClaire' => $user->getPasswordClaire(),
                // 'avatar' => $user->getAvatar(),
            ];
        }

        return $this->json($resultat, Response::HTTP_OK);
    }

    // get data if the user is habitant
    #[Route('/api/user/{id}/is-habitant', name: 'api_user_is_habitant', methods: ['GET'])]
    public function isHabitant($id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvée'], Response::HTTP_NOT_FOUND);

        $resultat = $this->fonctionsService->fetchDataElecteur($user->getNumeroElecteur());

        return $this->json($resultat, Response::HTTP_OK);
    }

    // updatePassword
    #[Route('/api/user/{id}/update-password', name: 'api_user_update_password', methods: ['PUT'])]
    public function updatePassword($id, Request $request,  UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvée'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);

        if ($data['currentPassword'] === $data['newPassword']) {
            return $this->json(['message' => 'Le nouveau mot de passe doit différer du mot de passe actuel'], Response::HTTP_BAD_REQUEST);
        }

        if (!password_verify($data['currentPassword'], $user->getPassword())) {
            return $this->json(['message' => 'Mot de passe incorrect'], Response::HTTP_BAD_REQUEST);
        }

        $newPassword = password_hash($data['newPassword'], PASSWORD_BCRYPT);
        $user->setPassword($newPassword);
        $user->setPasswordClaire($data['newPassword']);
        $userRepository->save($user, true);
        return $this->json(['message' => 'Mot de passe mis à jour avec success', 'password' => $data['newPassword'], 'hash' => $user->getPassword()], Response::HTTP_OK);
    }


    // se connecter sur une base de données et reccuperer les users dans la table users qui ont le meme numeroElecteur

    #[Route('/api/user/check-numero-electeur/{email}', name: 'api_check_numero_electeur', methods: ['GET'])]
    public function checkNumeroElecteur(string $email): Response
    {
        try {
            // Connexion à la base de données
            $pdo = new \PDO(
                'mysql:host=localhost:3306;dbname=adn_db_actif',
                'root',
                '',
                array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
            );

            // Préparation de la requête
            $query = "SELECT * FROM adn_users WHERE email = :email";
            $stmt = $pdo->prepare($query);
            $stmt->execute(['email' => $email]);

            // Récupération des résultats
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($results)) {
                return $this->json([
                    'message' => 'Aucun utilisateur trouvé avec ce numéro d\'électeur',
                    'found' => false
                ], Response::HTTP_OK);
            }

            return $this->json([
                'message' => 'Utilisateurs trouvés',
                'found' => true,
                'users' => $results
            ], Response::HTTP_OK);
        } catch (\PDOException $e) {
            return $this->json([
                'message' => 'Erreur de connexion à la base de données',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // methode pour mettre a jour le role
    #[Route('/api/user/{id}/update-role', name: 'api_user_update_role', methods: ['PUT'])]
    public function updateRole($id, Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvée'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);

        $user->setRoles($data['role']);
        $this->mailService->sendEmailChangeRole($user->getEmail(), $data['role'], $user);
        $userRepository->save($user, true);
        return $this->json(['message' => 'Role mis à jour avec success'], Response::HTTP_OK);
    }
}
