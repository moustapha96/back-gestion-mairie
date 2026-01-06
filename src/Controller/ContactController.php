<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $fileBaseUrl,                 // ex: https://cdn.mairie.sn (fallback: host courant)
        private string $contactUploadDir,            // %kernel.project_dir%/public/uploads/contact
    ) {
    }

    #[Route('', name: 'contact_create', methods: ['POST'])]
    public function create(Request $req): Response
    {
        $isJson = str_starts_with((string) $req->headers->get('Content-Type'), 'application/json');
        $data = $isJson ? (json_decode($req->getContent(), true) ?? []) : $req->request->all();

        // Récup inputs
        $nom = trim((string) ($data['nom'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $tel = trim((string) ($data['telephone'] ?? ''));
        $categorie = trim((string) ($data['categorie'] ?? ''));
        $reference = trim((string) ($data['reference'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $consent = filter_var($data['consent'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Pièce jointe
        /** @var UploadedFile|null $uploaded */
        $uploaded = $req->files->get('pieceJointe');

        // ===== Validations minimales (alignées au front) =====
        if ($nom === '')
            return $this->json(['message' => 'Nom requis'], Response::HTTP_BAD_REQUEST);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Email invalide'], Response::HTTP_BAD_REQUEST);
        }
        // Tel SN: très proche de ton regex front, on reste permissif: max ~20 chars
        if ($tel === '' || strlen(preg_replace('/\s+/', '', $tel)) < 9) {
            return $this->json(['message' => 'Téléphone invalide'], Response::HTTP_BAD_REQUEST);
        }
        if (!in_array($categorie, ContactMessage::ALLOWED_CATEGORIES, true)) {
            return $this->json(['message' => 'Catégorie invalide'], Response::HTTP_BAD_REQUEST);
        }
        if ($message === '')
            return $this->json(['message' => 'Message requis'], Response::HTTP_BAD_REQUEST);
        if ($consent !== true) {
            return $this->json(['message' => 'Consentement requis'], Response::HTTP_BAD_REQUEST);
        }

        // ===== Enregistrement =====
        $cm = new ContactMessage();
        $cm->setNom($nom)
            ->setEmail($email)
            ->setTelephone($tel)
            ->setCategorie($categorie)
            ->setReference($reference !== '' ? $reference : null)
            ->setMessage($message)
            ->setConsent(true);

        // ----- Sauvegarde pièce jointe si présente -----
        if ($uploaded instanceof UploadedFile) {
            // Validation fichier (<=5Mo, pdf/jpg/png)
            // $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
            // if (!in_array($uploaded->getClientMimeType() ?: '', $allowed, true)) {
            //     return $this->json(['message' => 'Format de fichier non autorisé'], Response::HTTP_BAD_REQUEST);
            // }
            if ($uploaded->getSize() > 5 * 1024 * 1024) {
                return $this->json(['message' => 'Fichier trop volumineux (max 5 Mo)'], Response::HTTP_BAD_REQUEST);
            }

            $webPath = $this->storeAttachment($uploaded, $email);
            $cm->setPieceJointe($webPath);
        }

        $this->em->persist($cm);
        $this->em->flush();

        // Réponse
        return $this->json([
            'success' => true,
            'item' => $this->serializeItem($req, $cm),
            'message' => 'Message reçu, merci de votre contact.',
        ], Response::HTTP_CREATED);
    }

    // (Optionnel) petit GET admin pour vérifier
    #[Route('', name: 'contact_list', methods: ['GET'])]
    public function list(ContactMessageRepository $repo): Response
    {
        $items = array_map(fn(ContactMessage $m) => [
            'id' => $m->getId(),
            'nom' => $m->getNom(),
            'email' => $m->getEmail(),
            'telephone' => $m->getTelephone(),
            'categorie' => $m->getCategorie(),
            'reference' => $m->getReference(),
            'message' => mb_strimwidth($m->getMessage(), 0, 140, '…'),
            'pieceJointe' => $m->getPieceJointe(),
            'createdAt' => $m->getCreatedAt()->format('c'),
        ], $repo->findBy([], ['id' => 'DESC']));

        return $this->json(['success' => true, 'items' => $items]);
    }

    // ========= helpers =========

    private function storeAttachment(UploadedFile $file, string $email): string
    {
        if (!is_dir($this->contactUploadDir)) {
            @mkdir($this->contactUploadDir, 0775, true);
        }

        $slugger = new AsciiSlugger();
        $emailSlug = (string) $slugger->slug($email ?: 'non-renseigne')->lower();
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'piece-jointe';
        $nameSlug = (string) $slugger->slug($baseName)->lower();
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'pdf');

        $filename = sprintf(
            '%s-%s-%s.%s',
            $nameSlug,
            date('YmdHis'),
            bin2hex(random_bytes(4)),
            $ext
        );

        $file->move($this->contactUploadDir, $filename);

        // On renvoie le chemin web relatif (servi par le serveur web)
        return '/uploads/contact/' . $filename;
    }

    private function absoluteUrl(Request $r, ?string $webPath): ?string
    {
        if (!$webPath)
            return null;
        if (preg_match('#^https?://#i', $webPath))
            return $webPath;

        $base = rtrim($this->fileBaseUrl ?: ($r->getScheme() . '://' . $r->getHttpHost()), '/');
        return $base . $webPath;
    }

    private function serializeItem(Request $r, ContactMessage $m): array
    {
        return [
            'id' => $m->getId(),
            'nom' => $m->getNom(),
            'email' => $m->getEmail(),
            'telephone' => $m->getTelephone(),
            'categorie' => $m->getCategorie(),
            'reference' => $m->getReference(),
            'message' => $m->getMessage(),
            'consent' => $m->getConsent(),
            'pieceJointe' => $m->getPieceJointe(),                    // relatif
            'pieceJointeUrl' => $this->absoluteUrl($r, $m->getPieceJointe()), // absolu pour le front
            'createdAt' => $m->getCreatedAt()->format('c'),
        ];
    }
}
