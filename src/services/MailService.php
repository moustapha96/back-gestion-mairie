<?php


namespace App\services;


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

    public function __construct(
        ConfigurationService $config,
        MailerInterface $mailer,
        UserRepository $userRepository,

    ) {
        $this->config = $config;
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
    }

    public function sendSimplemail($email, $subject, $message,)
    {
        try {
            $emailSend = (new TemplatedEmail())
                ->from($this->getEmailSender())
                ->to($email)
                ->subject($subject)
                ->htmlTemplate('emails_to/model-send-mail.html.twig')
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
        $emailbase = $this->config->get("email") ?? "moustaphakhouma965@gmail.com";
        $nombase = $this->config->get("name") ?? "Gestion de la mairie";
        return new Address($emailbase, $nombase);
    }

    public function sendWelcomeMail($email, $token)
    {
        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user) {
                $emailSend = (new TemplatedEmail())
                    ->from($this->getEmailSender())
                    ->to($email)
                    ->subject("Bienvenue sur Gestion de la Mairie !")
                    ->htmlTemplate('template_email/bienvenue.html.twig')
                    ->context([
                        'name' => $user->getUsername(),
                        'token' => $token
                    ]);
                $this->mailer->send($emailSend);
                return "Email envoyé avec succés !";
            }
            return "L'utilisateur n'existe pas !";
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
