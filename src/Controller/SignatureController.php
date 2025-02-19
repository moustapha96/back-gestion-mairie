<?php

namespace App\Controller;

use App\Entity\Signature;
use App\Entity\User;
use App\Repository\SignatureRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignatureController extends AbstractController
{

    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/api/signatures/create', name: 'api_create_signature', methods: ['POST'])]
    public function createSignature(
        Request $request,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): Response {

        $userId = $request->request->get('userId') ?? $data['userId'] ?? null;
        $ordre = $request->request->get('ordre') ?? $data['ordre'] ?? null;


        $data = json_decode($request->getContent(), true);

        // Vérifier si l'utilisateur existe
        $user = $userRepository->find($userId);
        if (!$user) {
            return new Response(json_encode([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé.',
            ]), 404, ['Content-Type' => 'application/json']);
        }

        // Créer la signature
        $signature = new Signature();
        $signature->setUser($user);
        $signature->setDateSignature(new \DateTime());
        $signature->setOrdre($ordre);

        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');
        if ($file) {
            $newFilename = sprintf(
                '%s-%s.%s',
                strtolower("signature"),
                $user ? str_replace(' ', '-', strtolower($user->getEmail())) : date('YmdHis'),
                $file->guessExtension()
            );
            $file->move($this->getParameter('signature_directory'), $newFilename);
            $url = $this->getParameter('signature_directory') . "/" . $newFilename;
            $signature->setSignature($url);
        } else {
            return $this->json(['message' => 'Veuillez uploader un document'], Response::HTTP_BAD_REQUEST);
        }

        // Valider l'entité Signature
        $errors = $validator->validate($signature);
        if (count($errors) > 0) {
            return new Response(json_encode(['errors' => (string) $errors]), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'application/json']);
        }

        // Sauvegarder la signature
        $this->em->persist($signature);
        $this->em->flush();

        return new Response(json_encode([
            'status' => 'success',
            'message' => 'Signature créée avec succès.',
            'signature' => $signature->toArray(),
        ]), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }
}
