<?php

namespace App\Controller;

use App\services\EncryptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EncryptionController extends AbstractController
{

    private EncryptionService $encryptionService;

    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    #[Route('/encrypt', name: 'encrypt')]
    public function encrypt(): Response
    {
        $data = 'Ceci est un document confidentiel.';

        // Chiffrement
        $encrypted = $this->encryptionService->encrypt($data);

        return $this->json([
            'encrypted_data' => $encrypted['data'],
            'iv' => $encrypted['iv'],
        ]);
    }

    #[Route('/decrypt', name: 'decrypt')]
    public function decrypt(): Response
    {
        $encryptedData = 'fqaKyGT2l62MtkJ9GoEbV636gmLS+HTBX0amiQx++eL+e/EFpvd7cS25u8jQDDVL'; // Données chiffrées
        $iv = 'TsTWtUow266H5js05JRRfQ=='; // IV correspondant

        // Déchiffrement
        $decrypted = $this->encryptionService->decrypt($encryptedData, $iv);

        return $this->json([
            'decrypted_data' => $decrypted,
        ]);
    }
}
