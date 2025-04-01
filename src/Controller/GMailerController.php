<?php

namespace App\Controller;

use App\services\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GMailerController extends AbstractController
{
    private $mailerService;
    public function __construct(MailService $mailService)
    {
        $this->mailerService = $mailService;
    }

    #[Route('/api/sendSimpleMail', name: 'api_send_simple_mailer', methods: ['POST'])]
    public function sendMail(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $emails = $data['emails'];
        $message = $data['message'];
        $sujet = $data['sujet'];

        if (!$emails ||  !$message || !$sujet) {
            return $this->json('Les données sont imcompletes', 300);
        }
        $resultat = null;
        foreach ($emails as $email) {
            $resultat = $this->mailerService->sendSimplemail($email, $sujet, $message);
        }
        return $this->json($resultat, Response::HTTP_OK);
    }

    #[Route('/api/sendSimpleMailRefus', name: 'api_send_simple_mailer_refus', methods: ['POST'])]
    public function sendMailRefus(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'];
        $message = $data['message'];
        $sujet = $data['sujet'];

        if (!$email ||  !$message || !$sujet) {
            return $this->json('Les données sont imcompletes', 300);
        }
        $resultat = null;
        $resultat = $this->mailerService->sendSimplemail($email, $sujet, $message);
        return $this->json($resultat, Response::HTTP_OK);
    }
}
