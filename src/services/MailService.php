<?php


namespace App\services;

use App\Entity\Request as Demande;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService extends AbstractController
{
    private $config;
    private $mailer;
    private $userRepository;
    private string $adminEmail;
    public function __construct(
        ConfigurationService $config,
        MailerInterface $mailer,
        UserRepository $userRepository,

    ) {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
        $this->adminEmail = "support@kaolackcommune.sn";
    }

    private function sendEmail(string $to, string $subject, string $template, array $context = []): void
    {
        $email = (new TemplatedEmail())
            ->from(addresses: new Address($this->adminEmail, "Mairie de Kaolack"))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }

    public function sendSimplemail($email, $subject, $message,)
    {
        try {
            $emailSend = (new TemplatedEmail())
                ->from($this->getEmailSender())
                ->to($email)
                ->subject($subject)
                ->htmlTemplate('nouveau_email/model-send-mail.html.twig')
                ->context([
                    'sujet' => $subject,
                    'message' => $message
                ]);
            $this->mailer->send($emailSend);
            return "Email envoyé avec succés !";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    private function getEmailSender(): Address
    {
        $emailbase = $this->config->get("email") ?? "support@kaolackcommune.sn";
        $nombase = $this->config->get("titre") ?? "Gestion de la mairie";
        return new Address($emailbase, $nombase);
    }

    public function sendWelcomeMail($email, $token, $url): string
    {
        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $emailSend = (new TemplatedEmail())
                    ->from($this->getEmailSender())
                    ->to($email)
                    ->subject("Bienvenue sur Gestion de la Mairie !")
                    ->htmlTemplate('nouveau_email/bienvenue.html.twig')
                    ->context([
                        'name' => $user->getUsername(),
                        'prenom' => $user->getPrenom(),
                        'nom' => $user->getNom(),
                        'token' => $token,
                        'activationUrl' => sprintf('%s/activate?token=%s', $url, $token),
                    ]);
                $this->mailer->send($emailSend);
                return "Email envoyé avec succés !";
            }
            return "L'utilisateur n'existe pas !";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }



    public function sendConfirmationDemande(Demande $demande): string
    {
        try {
            $emailSend = (new TemplatedEmail())

                ->from($this->getEmailSender())
                ->to($demande->getUtilisateur()->getEmail())
                ->subject("Confirmation de votre demande de terrain")
                ->htmlTemplate('nouveau_email/demande-confirmation.html.twig')
                ->context([
                    'demande' => $demande,
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                'localite' => $demande->getLocalite(),
                'userEmail' => $demande->getUtilisateur()->getEmail(),
                ]);
            $this->mailer->send($emailSend);
            return "Email de confirmation envoyé avec succès !";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }



    public function sendDemandeMail(Demande $demande): string
    {
        try {
            $emailSend = (new TemplatedEmail())
                ->from($this->getEmailSender())
                ->to($demande->getUtilisateur()->getEmail())
                ->subject("Confirmation de votre demande de terrain")
                ->htmlTemplate('nouveau_email/compte-existant-demande-confirmation.html.twig')
                ->context([
                    'demande' => $demande,
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'localite' => $demande->getLocalite(),
                    'userEmail' => $demande->getUtilisateur()->getEmail(),
                ]);
            $this->mailer->send($emailSend);
            return "Email de confirmation envoyé avec succès pour le user existant!";
        } catch (\Throwable $th) {
            return "Erreur lors de l'envoi de l'email de confirmation ";
        }
    }

    public function sendStatusChangeMail(Demande $demande): string
    {
        try {
            $emailSend = (new TemplatedEmail())
                ->from($this->getEmailSender())
                ->to($demande->getUtilisateur()->getEmail())
                ->subject("Mise à jour du statut de votre demande")
                ->htmlTemplate('nouveau_email/demande-status-change.html.twig')
                ->context([
                    'demande' => $demande,
                    'nom' => $demande->getUtilisateur()->getNom(),
                    'prenom' => $demande->getUtilisateur()->getPrenom(),
                    'statut' => $demande->getStatut(),
                ]);
            $this->mailer->send($emailSend);
            return "Email de notification envoyé avec succès !";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function sendAccountCreationMail(User $user, string $password): string
    {
        try {
            $emailSend = (new TemplatedEmail())
                ->from($this->getEmailSender())
                ->to($user->getEmail())
                ->subject("Bienvenue - Votre compte a été créé")
                ->htmlTemplate('nouveau_email/account_created.html.twig')

                ->context([
                    'user' => $user,
                    'password' => $password,
                    'token' => $user->getTokenActiveted(),
                    'activationUrl' => sprintf('%s/activate?token=%s', 'https://glotissement.kaolackcommune.sn', $user->getTokenActiveted()),
                    'name' => $user->getUsername(),
                    'prenom' => $user->getPrenom(),
                    'nom' => $user->getNom(),
                ]);
            $this->mailer->send($emailSend);
            return "Email de bienvenue envoyé avec succès !";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function sendDocumentGeneratedEmail(string $to, array $documentDetails): void
    {
        $this->sendEmail(
            $to,
            'Document généré avec succès',
            'nouveau_email/document_generated.html.twig',
            ['document' => $documentDetails]
        );
    }
    public function sendNewRequestEmail(string $to, array $requestDetails): void
    {
        $this->sendEmail(
            $to,
            'Nouvelle demande reçue',
            'nouveau_email/request_received.html.twig',
            ['request' => $requestDetails]
        );
    }

    public function sendStatusChangeEmail(string $to, string $status, array $requestDetails): void
    {
        $this->sendEmail(
            $to,
            'Mise à jour du statut de votre demande',
            'nouveau_email/status_updated.html.twig',
            ['status' => $status, 'request' => $requestDetails]
        );
    }


    public function sendEmailChangeRole(string $to, string $role, $user): void
    {
        $this->sendEmail(
            $to,
            'Mise à jour de votre rôle sur la plateforme de la Mairie',
            'nouveau_email/role_updated.html.twig',
            ['role' => $role, 'user' => $user]
        );
    }

    public function sendAccountCreationEmail(string $to, string $username): void
    {
        $this->sendEmail(
            $to,
            'Bienvenue sur la plateforme de la Mairie',
            'nouveau_email/account_created.html.twig',
            ['username' => $username]
        );
    }

    public function sendAccountStatusChangeEmail(string $to, bool $isActive, $user): void
    {
        $statusText = $isActive ? 'activé' : 'désactivé';
        $this->sendEmail(
            $to,
            "Votre compte a été $statusText",
            'nouveau_email/account_status_changed.html.twig',
            [
                'status' => $statusText,
                'user' => $user,
                'activationUrl' => 'https://glotissement.kaolackcommune.sn/auth/sign-in',
            ]
        );
    }

    public function sendAdminNotification(string $entityType, array $details): void
    {
        $this->sendEmail(
            $this->adminEmail,
            "Nouvelle création de $entityType",
            'nouveau_email/admin_notification.html.twig',
            ['entityType' => $entityType, 'details' => $details]
        );
    }
}
