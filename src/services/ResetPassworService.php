<?php

namespace App\services;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPassworService extends AbstractController
{


    use ResetPasswordControllerTrait;
    private $config;
    private $resetPasswordHelper;
    private $entityManager;
    private $tokenGenerator;
    private $mailer;
    private $translator;
    private $userPasswordHasher;

    private $userRepository;


    public function __construct(
        ConfigurationService $config,
        ResetPasswordHelperInterface $resetPasswordHelper,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        TranslatorInterface $translator,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        TokenGeneratorInterface $tokenGenerator
    ) {
        $this->config = $config;
        $this->resetPasswordHelper = $resetPasswordHelper;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userRepository = $userRepository;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @param string $password
     * @param string $token
     * @throws EntityNotFoundException
     */
    public function newPassword(string $password, string $token): string
    {

        if (!$token) {
            throw new EntityNotFoundException("token invalide");
        }

        $user =   $this->userRepository->findOneBy(['reset_token' => $token]);
        if (!$user) {
            throw new EntityNotFoundException("Utilisateur avec ce token n'esxite pas ");
        }
        // dd($user);
        if ($user->getResetTokenExpiredAt() < new \DateTimeImmutable()) {
            return "Le token a expiré , veuillez ressayer";
        }
        $user->setResetToken(null);
        $encodedPassword = $this->userPasswordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($encodedPassword);
        $user->setPasswordClaire($password);
        $user->setResetTokenExpiredAt(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return "Mot de passe mis à jour";
    }


    /**
     * @param string $password
     * @param string $token
     * @throws EntityNotFoundException
     */
    public function reset(string $password, string $token = null)
    {
        if ($token) {
            $this->storeTokenInSession($token);
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw new  EntityNotFoundException("Aucun jeton de réinitialisation du mot de passe trouvé dans l'URL ou dans la session.");
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $error =  sprintf(
                '%s - %s',
                $this->translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $this->translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            );
            throw new EntityNotFoundException($error);
        }

        $this->resetPasswordHelper->removeResetRequest($token);

        $encodedPassword = $this->userPasswordHasher->hashPassword(
            $user,
            $password
        );

        $user->setPassword($encodedPassword);
        $this->entityManager->flush();

        // The session is cleaned up after the password has been changed.
        $this->cleanSessionAfterReset();
    }


    private function getEmailSender(): Address
    {
        $emailbase = $this->config->get("email") ?? "moustaphakhouma965@gmail.com";
        $nombase = $this->config->get("titre") ?? "Gestion de la mairie";
        return new Address($emailbase, $nombase);
    }
    /**
     * @param string $emailFormData
     * @param string $uri
     * @throws EntityNotFoundException
     */
    public function processSendingPasswordResetEmail(string $emailFormData, string $uri): string
    {
        // Recherche de l'utilisateur par email
        $user = $this->userRepository->findOneBy(['email' => $emailFormData]);

        // Vérifier si l'utilisateur existe
        if (!$user) {
            return 'Aucun utilisateur trouvé avec cet email.';
        }

        try {
            // Génération du token de réinitialisation
            // $tokenG = $this->resetPasswordHelper->generateResetToken($user);
            // Enregistrement du token dans l'entité utilisateur
            $token = $this->tokenGenerator->generateToken();
            $user->setResetToken($token);
            $user->setResetTokenExpiredAt(new \DateTimeImmutable('+30 minutes'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Construction de l'URL de réinitialisation
            $url = $uri . '?token=' . urlencode($token);

            // Création et envoi de l'email
            $email = (new TemplatedEmail())
                ->from($this->getEmailSender())
                ->to($user->getEmail())
                ->subject('Votre demande de réinitialisation de mot de passe')
                ->htmlTemplate('reset_password/emailTokenApi.html.twig')
                ->context([
                    'url' => $url,
                    'resetToken' => $token,
                    'user' => $user
                ]);

            $this->mailer->send($email);

            return 'Demande envoyée, merci de vérifier votre boîte mail.';
        } catch (ResetPasswordExceptionInterface $e) {

            return $e;
            // return 'Erreur lors de la génération du lien de réinitialisation : ' . $e->getMessage();
        } catch (\Throwable $th) {
            return 'Une erreur est survenue lors de l’envoi de l’email. Veuillez réessayer plus tard.' . $th->getMessage();
        }
    }
}
