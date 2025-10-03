<?php


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
use App\Entity\Request as Demande;
use Doctrine\ORM\EntityManagerInterface;

class UserController extends AbstractController
{

    private $security;
    private $mailService;
    private $fonctionsService;

    public function __construct(
        Security $security,
        private string $fileBaseUrl,
        MailService $mailService,
        FonctionsService $fonctionsService
    ) {
        $this->security = $security;
        $this->mailService = $mailService;
        $this->fonctionsService = $fonctionsService;
    }



    #[Route('/api/user/liste', name: 'api_users_index_liste', methods: ['GET'])]
    public function listPaginated(Request $request, UserRepository $repo): Response
    {
        $page = (int) $request->query->get('page', 1);
        $size = (int) $request->query->get('size', 10);
        $search = $request->query->get('search');     // string
        $role = $request->query->get('role');       // "ROLE_ADMIN"...
        $enabled = $request->query->get('enabled');    // "true" | "false" | null
        $activated = $request->query->get('activated');  // "true" | "false" | null
        $sort = $request->query->get('sort', 'id,DESC'); // "nom,ASC" ...

        $result = $repo->searchPaginated([
            'all' => false, // ⬅️ garder la pagination
            'page' => $page,
            'size' => $size,
            'search' => $search ?: null,
            'role' => $role ?: null,
            'enabled' => $enabled !== null ? filter_var($enabled, FILTER_VALIDATE_BOOLEAN) : null,
            'activated' => $activated !== null ? filter_var($activated, FILTER_VALIDATE_BOOLEAN) : null,
            'sort' => $sort,
        ]);

        $counts = $result['counts'] ?? [];

        // Projection légère (NE PAS toucher la collection → pas de JOIN cachés)
        $data = array_map(function (User $u) use ($counts) {
            $id = $u->getId();

            return [
                'id' => $id,
                'nom' => $u->getNom(),
                'prenom' => $u->getPrenom(),
                'email' => $u->getEmail(),
                'username' => $u->getUsername(),
                'telephone' => $u->getTelephone(),
                'adresse' => $u->getAdresse(),
                'roles' => $u->getRoles(),
                'enabled' => $u->isEnabled(),
                'activated' => $u->isActiveted(),
                'nombre' => $u->getAdresse(), // (vérifie ce champ si c'est volontaire)
                'isHabitant' => $u->isHabitant(),
                'demandes' => $counts[$id] ?? 0,     // ⬅️ utilise le compteur
                'numeroElecteur' => $u->getNumeroElecteur(),
                'profession' => $u->getProfession(),
                'situationMatrimoniale' => $u->getSituationMatrimoniale(),
                'nombreEnfant' => $u->getNombreEnfant(),
                'situationDemandeur' => $u->getSituationDemandeur(),
            ];
        }, $result['items']);

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
                    'role' => $role,
                    'enabled' => $enabled,
                    'activated' => $activated,
                ], fn($v) => $v !== null && $v !== ''),
            ],
        ], Response::HTTP_OK);
    }



    #[Route('/api/user/create', name: 'api_users_creation', methods: ['POST'])]
    public function createUser(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {

        $data = json_decode($request->getContent(), true);

        $user = new User();
        if ($userRepository->findOneBy(['email' => $data['email']])) {
            return new Response(json_encode([
                'status' => 'error',
                'message' => 'Cet utilisateur existe déjà',
            ]), 400, ['Content-Type' => 'application/json']);
        }

        $numeroElecteur = $data['numeroElecteur'] ?? null;
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        $user->setTokenActiveted(bin2hex(random_bytes(32)));
        $user->setNumeroElecteur($numeroElecteur ?? null);
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
        $user->setEnabled(true);
        $user->setSituationDemandeur($data["stuationDemandeur"] ?? null);
        $user->setNombreEnfant($data["nombreEnfant"] ?? 0);
        $user->setPasswordClaire($data['password'] ?? "Password123!");
        $user->setSituationMatrimoniale($data["situationMatrimoniale"] ?? null);

        $demandeId = isset($data["demandeId"]) ? $data["demandeId"] : null;
        if ($demandeId) {
            $demande = $em->getRepository(Demande::class)->find($demandeId);
            if ($demande) {
                $user->adddemande_demandeur($demande);
            }
        }
        
        if ($numeroElecteur !== null) {
            $resultat = $this->fonctionsService->checkNumeroElecteurExist($numeroElecteur);
            $user->setHabitant($resultat);
        } else {
            $user->setHabitant(false);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new Response(json_encode(['errors' => (string) $errors]), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        $userRepository->save($user, true);
        $password = $data['password'] ?? 'Password123!';
        $resultat = $this->mailService->sendAccountCreationMail($user, $password);
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
        $user->setNumeroElecteur(isset($data['numeroElecteur']) ? $data['numeroElecteur'] : null);
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
        $user->setNombreEnfant(isset($data['nombreEnfant']) ? $data['nombreEnfant'] : 0);
        $user->setSituationMatrimoniale(isset($data['situationMatrimoniale']) ? $data['situationMatrimoniale'] : null);

        $user->setActiveted(false);
        $user->setEnabled(true);
        $user->setSituationDemandeur($data['situationDemandeur'] ?? null);
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
    public function deactiverCompte(int $idUser, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($idUser);
        if ($user) {
            $user->setEnabled(false);
            $userRepository->save($user, true);
            return $this->json($user, Response::HTTP_OK);
        }
        return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_BAD_REQUEST);
    }


    #[Route('/api/user/update-profile/{id}', name: 'api_users_mise_a_jour_compte', methods: ['PUT'])]
    public function modifierCompte(
        Request $request,
        int $id,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): Response {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

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
        $situationMatrimoniale = $data['situationMatrimoniale'] ?? $user->getSituationMatrimoniale();
        $nombreEnfant = $data['nombreEnfant'] ?? $user->getNombreEnfant();
        $profession = $data['profession'] ?? $user->getProfession();
        $situationDemandeur = $data['situationDemandeur'] ?? $user->getSituationDemandeur();

        if ($email) {
            $user->setUsername($email);
            $user->setEmail($email);
        }

        if ($newPassword && $password) {
            if (!password_verify($password, $user->getPassword())) {
                return $this->json(['message' => 'Mot de passe incorrect'], Response::HTTP_BAD_REQUEST);
            }
            if (strlen($newPassword) < 8) {
                return $this->json(['message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères'], Response::HTTP_BAD_REQUEST);
            }
            $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        }

        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setAdresse($adresse);
        $user->setTelephone($telephone);
        $user->setDateNaissance($dateNaissance);
        $user->setLieuNaissance($lieuNaissance);
        $user->setNumeroElecteur($numeroElecteur);
        $user->setNombreEnfant($nombreEnfant);
        $user->setSituationMatrimoniale($situationMatrimoniale);
        $user->setProfession($profession);
        $user->setSituationDemandeur($situationDemandeur ?? null);


        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $userRepository->save($user, true);

        // 👉 Projection SAFE (aucun service / I/O)
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'adresse' => $user->getAdresse(),
            'lieuNaissance' => $user->getLieuNaissance(),
            'dateNaissance' => $user->getDateNaissance()?->format('Y-m-d'),
            'numeroElecteur' => $user->getNumeroElecteur(),
            'roles' => $user->getRoles(),
            'enabled' => $user->isEnabled(),
            'activated' => $user->isActiveted(),
            'situationMatrimoniale' => $user->getSituationMatrimoniale(),
            'profession' => $user->getProfession(),
            'nombreEnfant' => $user->getProfession(),
            'situationDemandeur' => $user->getSituationDemandeur(),
        ], Response::HTTP_OK);
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
        $user->setRoles($data['role']);
        $user->setNom($data['nom'] ?? null);
        $user->setEmail($data['email']);
        $user->setPrenom($data['prenom'] ?? null);
        $user->setAdresse($data['adresse'] ?? null);
        $user->setUsername($data['email']);
        // profession   
        $user->setProfession($data['profession'] ?? null);
        $user->setTelephone($data['telephone'] ?? null);
        $user->setLieuNaissance($data['lieuNaissance'] ?? null);
        $user->setDateNaissance(new \DateTime($data['dateNaissance'] ?? null));
        $user->setNumeroElecteur($data['numeroElecteur'] ?? null);
        $user->setNombreEnfant($data['nombreEnfant'] ?? null);
        $user->setSituationMatrimoniale($data['situationMatrimoniale'] ?? null);
        $user->setSituationDemandeur($data['situationDemandeur'] ?? null);

        $resultat = $this->fonctionsService->checkNumeroElecteurExist($data['numeroElecteur']);
        $user->setHabitant($resultat ?? false);

        $user->setActiveted(false);
        $user->setEnabled(true);
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
                    'numeroElecteur' => $user->getNumeroElecteur(),
                    'nombreEnfant' => $user->getNombreEnfant(),
                    'profession' => $user->getProfession(),
                    'situationMatrimoniale' => $user->getSituationMatrimoniale(),
                    'situationDemandeur' => $user->getSituationDemandeur()
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
        ], [
            'id' => 'DESC'
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
                'numeroElecteur' => $user->getNumeroElecteur(),
                'nombreEnfant' => $user->getNombreEnfant(),
                'profession' => $user->getProfession(),
                'situationMatrimoniale' => $user->getSituationMatrimoniale(),
                'situationDemandeur' => $user->getSituationDemandeur()
            ];
        }
        return $this->json($resultats, Response::HTTP_OK);
    }

    // update status user
    #[Route('/api/user/update-activated-status/{id}', name: 'api_users_update_status', methods: ['PUT'])]
    public function updateStatus($id, Request $request, UserRepository $userRepository): Response
    {
        $data = json_decode($request->getContent(), true);
        $isActive = $data['activated'];

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json("Compte Utilisateur non trouvée", Response::HTTP_BAD_REQUEST);
        }

        $user->setActiveted(boolval($isActive));
        $userRepository->save($user, true);

        if ($user->getEmail()) {
            $this->mailService->sendAccountStatusChangeEmail($user->getEmail(), $isActive, $user);
        }

        return $this->json('Compte utilisateur mis à jour', 200);

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
    public function updateCompteEnable($id, Request $request, UserRepository $userRepository): Response
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


    #[Route('/api/user/{id}/details', name: 'api_user_show', methods: ['GET'])]
    public function details($id, UserRepository $userRepository, FonctionsService $fonctionsService): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);

        // Prepare demandes with localite information
        $demandesArray = [];
        foreach ($user->getdemande_demandeurs() as $demande) {
            $demandesArray[] = $this->serializeItem($demande);
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
            'avatar' => $user->getAvatar(),
            'roles' => $user->getRoles(),
            'nombreEnfant' => $user->getNombreEnfant(),
            'profession' => $user->getProfession(),
            'situationMatrimoniale' => $user->getSituationMatrimoniale(),
            'situationDemandeur' => $user->getSituationDemandeur()
        ];

        return $this->json($resultat, Response::HTTP_OK);
    }

    // la liste des user sauf admin
    #[Route('/api/user/liste', name: 'api_user_list', methods: ['GET'])]
    public function list(UserRepository $userRepository, FonctionsService $fonctionsService): Response
    {
        $users = $userRepository->findBy([], ['id' => 'DESC']);
        $resultat = [];
        foreach ($users as $user) {
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
                'demandes' => count($user->getdemande_demandeurs()),
                'parcelles' => count($user->getParcelles()),
                'passwordClaire' => $user->getPasswordClaire(),
                'nombreEnfant' => $user->getNombreEnfant(),
                'profession' => $user->getProfession(),
                'situationMatrimoniale' => $user->getSituationMatrimoniale(),
                'situationDemandeur' => $user->getSituationDemandeur()
            ];
        }

        return $this->json($resultat, Response::HTTP_OK);
    }

    #[Route('/api/user/demandeurs', name: 'api_user_list_demandeur', methods: ['GET'])]
    public function listDemandeur(
        Request $req,
        EntityManagerInterface $em,
        FonctionsService $fonctionsService
    ): Response {
        $minDemandes = max(1, (int) $req->query->get('minDemandes', 1));

        // u = User, d = Request (demande), p = Parcelle
        $qb = $em->createQueryBuilder()
            ->from(User::class, 'u')
            ->leftJoin('u.demande_demandeurs', 'd')
            ->leftJoin('u.parcelles', 'p')
            ->select([
                'u.id            AS id',
                'u.email         AS email',
                'u.nom           AS nom',
                'u.prenom        AS prenom',
                'u.roles         AS roles',
                'u.telephone     AS telephone',
                'u.adresse       AS adresse',
                'u.numeroElecteur AS numeroElecteur',
                'u.profession    AS profession',
                'u.situationMatrimoniale AS situationMatrimoniale',
                'u.situationDemandeur    AS situationDemandeur',
                'u.enabled       AS enabled',
                'u.activeted     AS activated',
                'u.dateNaissance AS dateNaissance',
                'u.lieuNaissance AS lieuNaissance',
                // Compteurs
                'COUNT(DISTINCT d.id) AS demandes',
                'COUNT(DISTINCT p.id) AS parcelles',
            ])
            ->groupBy('u.id')
            ->having('COUNT(DISTINCT d.id) >= :minDemandes')
            ->setParameter('minDemandes', $minDemandes)
            ->orderBy('u.id', 'DESC');

        // IMPORTANT: on hydrate en tableau pour récupérer les alias "demandes", "parcelles"
        $rows = $qb->getQuery()->getArrayResult();

        // Post-traitement / sérialisation légère (dates + isHabitant)
        $out = [];
        foreach ($rows as $r) {
            $dateNaissance = $r['dateNaissance'] instanceof \DateTimeInterface
                ? $r['dateNaissance']->format('Y-m-d')
                : ($r['dateNaissance'] ?? null);

            $numeroElecteur = $r['numeroElecteur'] ?? null;
            $isHabitant = $numeroElecteur ? (bool) $fonctionsService->checkNumeroElecteurExist($numeroElecteur) : false;

            $out[] = [
                'id' => (int) $r['id'],
                'email' => $r['email'],
                'nom' => $r['nom'],
                'prenom' => $r['prenom'],
                'roles' => is_array($r['roles']) ? $r['roles'] : [],
                'telephone' => $r['telephone'],
                'adresse' => $r['adresse'],
                'numeroElecteur' => $numeroElecteur,
                'profession' => $r['profession'],
                'situationMatrimoniale' => $r['situationMatrimoniale'],
                'situationDemandeur' => $r['situationDemandeur'],
                'enabled' => $r['enabled'],
                'activated' => $r['activated'],
                'dateNaissance' => $dateNaissance,
                'lieuNaissance' => $r['lieuNaissance'],
                'isHabitant' => $isHabitant,
                // alias récupérés car getArrayResult()
                'demandes' => (int) $r['demandes'],
                'parcelles' => (int) $r['parcelles'],
            ];
        }

        return $this->json($out, Response::HTTP_OK);
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
    public function updatePassword($id, Request $request, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user)
            return $this->json(['message' => 'Utilisateur non trouvée'], Response::HTTP_NOT_FOUND);
        $data = json_decode($request->getContent(), true);

        // if ($data['currentPassword'] === $data['newPassword']) {
        //     return $this->json(['message' => 'Le nouveau mot de passe doit différer du mot de passe actuel'], Response::HTTP_BAD_REQUEST);
        // }

        // if (!password_verify($data['currentPassword'], $user->getPassword())) {
        //     return $this->json(['message' => 'Mot de passe incorrect'], Response::HTTP_BAD_REQUEST);
        // }

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


    private function serializeItem(Demande $d): array
    {
        if (method_exists($d, 'toArray')) {
            $arr = $d->toArray();

            // Normalisation des URLs fichiers
            foreach (['recto', 'verso'] as $k) {
                if (!empty($arr[$k]) && !preg_match('#^https?://#i', (string) $arr[$k])) {
                    $v = (string) $arr[$k];
                    if ($v !== '' && $v[0] !== '/') {
                        $v = '/' . ltrim($v, '/');
                    }
                    if ($v !== '' && !str_starts_with($v, '/tfs/')) {
                        $v = '/tfs' . $v;
                    }
                    $arr[$k] = rtrim($this->fileBaseUrl, '/') . $v;
                }
            }

            // localite = STRING (champ texte)
            $arr['localite'] = $d->getLocalite();

            // quartier = OBJET (relation)
            $quartier = $d->getQuartier();
            $arr['quartier'] = $quartier ? [
                'id' => $quartier->getId(),
                'nom' => method_exists($quartier, 'getNom') ? $quartier->getNom() : null,
                'prix' => method_exists($quartier, 'getPrix') ? $quartier->getPrix() : null,
                'longitude' => method_exists($quartier, 'getLongitude') ? $quartier->getLongitude() : null,
                'latitude' => method_exists($quartier, 'getLatitude') ? $quartier->getLatitude() : null,
                'description' => method_exists($quartier, 'getDescription') ? $quartier->getDescription() : null,
            ] : null;

            // (Optionnel) enveloppe "demandeur" si toArray ne le fait pas
            if (!isset($arr['demandeur'])) {
                $arr['demandeur'] = [
                    'prenom' => $d->getPrenom(),
                    'nom' => $d->getNom(),
                    'email' => $d->getEmail(),
                    'telephone' => $d->getTelephone(),
                    'adresse' => $d->getAdresse(),
                    'profession' => $d->getProfession(),
                    'numeroElecteur' => $d->getNumeroElecteur(),
                    'dateNaissance' => $d->getDateNaissance()?->format('Y-m-d'),
                    'lieuNaissance' => $d->getLieuNaissance(),
                    'situationMatrimoniale' => $d->getSituationMatrimoniale(),
                    'statutLogement' => $d->getStatutLogement(),
                    'nombreEnfant' => $d->getNombreEnfant(),
                    'isHabitant' => $this->fonctionsService->checkNumeroElecteurExist($d->getNumeroElecteur()),
                ];
            }

            return $arr;
        }

        $historiques = [];
        foreach ($d->getHistoriqueValidations() as $historique) {
            $historiques[] = $historique->toArray();
        }

        return [
            'id' => $d->getId(),
            'typeDemande' => $d->getTypeDemande(),
            'typeDocument' => $d->getTypeDocument(),
            'typeTitre' => $d->getTypeTitre(),
            'superficie' => $d->getSuperficie(),
            'usagePrevu' => $d->getUsagePrevu(),
            'possedeAutreTerrain' => $d->isPossedeAutreTerrain(),
            'statut' => $d->getStatut(),
            'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $d->getDateModification()?->format('Y-m-d H:i:s'),
            'motif_refus' => $d->getMotifRefus(),
            'recto' => $d->getRecto(),
            'verso' => $d->getVerso(),
            'terrainAKaolack' => $d->isTerrainAKaolack(),
            'terrainAilleurs' => $d->isTerrainAilleurs(),
            'decisionCommission' => $d->getDecisionCommission(),
            'rapport' => $d->getRapport(),
            'localite' => $d->getLocalite(), // <- déjà présent ici
            'recommandation' => $d->getRecommandation(),
            'niveauValidationActuel' => $d->getNiveauValidationActuel() ? $d->getNiveauValidationActuel()->toArray() : null,
            'historiqueValidations' => $historiques,
            'demandeur' => [
                'prenom' => $d->getPrenom(),
                'nom' => $d->getNom(),
                'email' => $d->getEmail(),
                'telephone' => $d->getTelephone(),
                'adresse' => $d->getAdresse(),
                'profession' => $d->getProfession(),
                'numeroElecteur' => $d->getNumeroElecteur(),
                'dateNaissance' => $d->getDateNaissance()?->format('Y-m-d'),
                'lieuNaissance' => $d->getLieuNaissance(),
                'situationMatrimoniale' => $d->getSituationMatrimoniale(),
                'statutLogement' => $d->getStatutLogement(),
                'nombreEnfant' => $d->getNombreEnfant(),
                'isHabitant' => $this->fonctionsService->checkNumeroElecteurExist($d->getNumeroElecteur()),
            ],
            'quartier' => $d->getQuartier() ? [
                'id' => $d->getQuartier()->getId(),
                'nom' => $d->getQuartier()->getNom(),
                'prix' => $d->getQuartier()->getPrix(),
                'longitude' => $d->getQuartier()->getLongitude(),
                'latitude' => $d->getQuartier()->getLatitude(),
                'description' => $d->getQuartier()->getDescription(),
            ] : null,
        ];
    }
}
