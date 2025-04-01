<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\UserRepository;
use App\services\ResetPassworService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/password-reset', name: 'api_request_password_reset', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository   $userRepository,
        ResetPassworService $resetPasswordService,
        TokenGeneratorInterface $tokenGenerator
    ): Response {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'];
        $uri = $data['uri'] . "/auth/reset-pass";

        if (empty($email)) {
            return new Response("Email is required", 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (empty($user)) {
            return $this->json(['message' => "Utilisateur introuvable"], Response::HTTP_NOT_FOUND);
        }

        if ($user->getResetToken() !== null && $user->getResetTokenExpiredAt() > new \DateTime()) {
            return $this->json(['message' => "Un email de réinitialisation de mot de passe vous a été deja envoyé , réessayez dans 30 minutes"], 200);
        }

        $result =  $resetPasswordService->processSendingPasswordResetEmail($email, $uri);
        return $this->json(['message' => $result], Response::HTTP_OK);
    }


    #[Route('/api/password-reset/new', name: 'api_reset_password_new', methods: ['POST'])]
    public function newPassword(Request $request, UserRepository $userRepository, ResetPassworService $resetPasswordService): Response
    {

        $data = json_decode($request->getContent(), true);

        $password = $data['password'];
        $confirmPassword = $data['confirmPassword'];
        $token = $data['token'];

        $user = $userRepository->findOneBy(['reset_token' => $token]);

        if (empty($user)) {
            return $this->json(['message' => "Token Non valide"], 400);
        }
        if ($confirmPassword !== $password) {
            return $this->json(['message' => "Les mots de passes ne correspondent pas"], 400);
        }


        $resultat = $resetPasswordService->newPassword($password, $token);
        if (!$resultat) {
            return $this->json(['message' => "Erreur lors de la reinitialisation du mot de passe"], 400);
        }
        return $this->json(['message' => $resultat], Response::HTTP_OK);
    }
}
