<?php

namespace App\EventSubscriber;

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

        if (!is_object($user)) {
            return;
        }

        // TTL du refresh token (ex: 30 jours)
        $ttl = new \DateTime();
        $ttl->modify('+30 days');

        // Créer un refresh token
        $refreshToken = $this->refreshTokenManager->create();
        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setRefreshToken(); // génère un token unique
        $refreshToken->setValid($ttl);

        $this->refreshTokenManager->save($refreshToken);

        // Ajouter au payload de réponse
        $data['refresh_token'] = $refreshToken->getRefreshToken();
        $event->setData($data);
    }
}
