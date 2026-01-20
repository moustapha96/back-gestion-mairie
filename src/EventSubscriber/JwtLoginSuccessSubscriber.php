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

            // Supprimer uniquement les tokens expirés ou invalides de cet utilisateur
            // On garde les tokens valides pour permettre plusieurs sessions
            $now = new \DateTime();
            $this->em->createQueryBuilder()
                ->delete(RefreshToken::class, 'rt')
                ->where('rt.username = :username')
                ->andWhere('rt.valid < :now')
                ->setParameter('username', $username)
                ->setParameter('now', $now)
                ->getQuery()
                ->execute();

            // TTL du refresh token (30 jours) - correspond à la config dans gesdinet_jwt_refresh_token.yaml
            $ttl = new \DateTime();
            $ttl->modify('+30 days');

            // Créer un nouveau refresh token via le RefreshTokenManager
            // Le manager utilise notre classe App\Entity\RefreshToken configurée
            $refreshToken = $this->refreshTokenManager->create();
            
            if (!$refreshToken instanceof RefreshToken) {
                // Si le manager ne retourne pas notre classe, on ne peut pas continuer
                return;
            }
            
            $refreshToken->setUsername($username);
            $refreshToken->setRefreshToken(); // génère un token unique
            $refreshToken->setValid($ttl);
            
            // S'assurer que created_at est toujours défini avant la sauvegarde
            // Le constructeur l'initialise, mais on le force pour être absolument sûr
            $refreshToken->setCreatedAt(new \DateTime());

            // Sauvegarder via le RefreshTokenManager (gère la persistance correctement)
            $this->refreshTokenManager->save($refreshToken);

            // Ajouter au payload de réponse
            $data['refresh_token'] = $refreshToken->getRefreshToken();
            $event->setData($data);
        } catch (\Throwable $e) {
            // En cas d'erreur, on continue sans bloquer l'authentification
            // Le token JWT principal est déjà dans la réponse
            // On peut logger l'erreur pour le débogage en développement
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log('Erreur lors de la création du refresh token: ' . $e->getMessage());
            }
        }
    }
}
