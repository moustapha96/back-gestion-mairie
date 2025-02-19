<?php

// src/Controller/UserController.php

namespace App\Controller;


use App\Entity\User;
use App\Repository\UserRepository;
use App\services\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserController extends AbstractController
{

    private $security;
    private $mailService;

    public function __construct(Security $security, MailService $mailService)
    {
        $this->security = $security;
        $this->mailService = $mailService;
    }

    #[Route('/api/users/create', name: 'api_users_creation', methods: ['POST'])]
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
        $user->setNumeroElecteur($data["numeroElecteur"] ?? null);
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

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response(json_encode(['errors' => (string) $errors]), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $userRepository->save($user, true);

        return new Response(json_encode($user->toArray()), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
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
    #[Route('/api/users/mise-a-jour-compte/{id}', name: 'api_users_mise_a_jour_compte', methods: ['PUT'])]
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

        return new Response(json_encode(['message' => 'Utilisateur mis à jour avec succès']), Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }


    // create user with admin role
    #[Route('/api/users/create-admin', name: 'api_users_create_admin', methods: ['POST'])]
    public function createUserAdmin(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        MailService $mailService
    ): Response {
        $data = json_decode($request->getContent(), true);
        $user = new User();

        if (!isset($data['email'], $data['username'])) {
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
        $user->setUsername($data['username']);
        $user->setTelephone($data['telephone']);
        $user->setLieuNaissance($data['lieuNaissance']);
        $user->setDateNaissance(new \DateTime($data['dateNaissance']));
        $user->setActiveted(false);
        $user->setEnabled(false);


        $passwordGenere = $user->generatePassword(8);
        $user->setPassword(password_hash($passwordGenere, PASSWORD_BCRYPT));
        $user->setPasswordClaire($passwordGenere);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json([
                'status' => 'error',
                'errors' => (string) $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        $mailService->sendWelcomeMail($user, $user->getTokenActiveted());
        $userRepository->save($user, true);

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
                ]
            ],
            Response::HTTP_CREATED
        );
    }

    // la liste des users
    #[Route('/api/users/liste', name: 'api_users_liste', methods: ['GET'])]
    public function listeUser(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $resultats = [];

        foreach ($users as $user) {
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
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }

    // update status user
    #[Route('/api/users/update-activated-status/{id}', name: 'api_users_update_status', methods: ['PUT'])]
    public function updateStatus($id, Request $request,  UserRepository $userRepository): Response
    {
        $isActived = json_decode($request->getContent(), true)['isActive'];
        $user = $userRepository->find($id);
        if ($user) {
            if ($isActived == "desactive") {
                $user->setActiveted(false);
                $user->setEnabled(false);
            } else {
                $user->setActiveted(true);
                $user->setEnabled(true);
            }

            $userRepository->save($user, true);
            return $this->json('Utilisateur mis à jour', Response::HTTP_OK);
        }
        return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_BAD_REQUEST);
    }

    // activated-account
    #[Route('/api/users/activated-account/{token}', name: 'api_user_activated_account', methods: ['GET'])]
    public function activatedAccount($token, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['tokenActiveted' => $token]);
        if ($user) {
            $user->setActiveted(true);
            $user->setEnabled(true);
            $userRepository->save($user, true);
            return $this->json('Compte active', Response::HTTP_OK);
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

    #[Route('/api/users/upload-profile-picture/{userId}', name: 'api_users_upload_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture($userId, Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($userId);
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
                'success' => true,
                'message' => 'Profil mis à jour',
                'imageUrl' => $user->getAvatar(),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du téléchargement de l\'image'], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    #[Route('/api/users/{id}/details', name: 'api_user_show', methods: ['GET'])]
    public function details($id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        return $this->json($user, Response::HTTP_OK);
    }
}
