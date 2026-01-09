<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtLoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private EntityManagerInterface $em
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();   // contient déjà "token"
        $user = $event->getUser();

        if (!\is_object($user)) {
            return;
        }

        try {
            $username = $user->getUserIdentifier();

            // Supprimer les anciens tokens de cet utilisateur pour éviter l'accumulation
            // Utilisation d'une requête DQL pour une suppression directe plus efficace
            $this->em->createQueryBuilder()
                ->delete(RefreshToken::class, 'rt')
                ->where('rt.username = :username')
                ->setParameter('username', $username)
                ->getQuery()
                ->execute();

            // TTL du refresh token (30 jours) - correspond à la config dans gesdinet_jwt_refresh_token.yaml
            $ttl = new \DateTime();
            $ttl->modify('+30 days');

            // Créer un nouveau refresh token via le RefreshTokenManager
            // Le manager utilise notre classe App\Entity\RefreshToken configurée
            $refreshToken = $this->refreshTokenManager->create();
            $refreshToken->setUsername($username);
            $refreshToken->setRefreshToken(); // génère un token unique
            $refreshToken->setValid($ttl);
            
            // S'assurer que created_at est toujours défini
            // Le constructeur de RefreshToken l'initialise déjà, mais on le force pour être sûr
            if ($refreshToken instanceof RefreshToken && \method_exists($refreshToken, 'setCreatedAt')) {
                $refreshToken->setCreatedAt(new \DateTime());
            }

            // Sauvegarder via le RefreshTokenManager (gère la persistance correctement)
            $this->refreshTokenManager->save($refreshToken);

            // Ajouter au payload de réponse
            $data['refresh_token'] = $refreshToken->getRefreshToken();
            $event->setData($data);
        } catch (\Throwable $e) {
            // En cas d'erreur, on continue sans bloquer l'authentification
            // Le token JWT principal est déjà dans la réponse
            // L'erreur est silencieuse pour ne pas perturber l'utilisateur
        }
    }
}
