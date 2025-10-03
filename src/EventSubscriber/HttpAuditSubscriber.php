<?php
namespace App\EventSubscriber;

use App\services\AuditLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

class HttpAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogger $logger,
        private Security $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST  => ['onRequest', 10],
            // KernelEvents::EXCEPTION => ['onException', 0],
            // KernelEvents::RESPONSE => ['onResponse', -10],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $req = $event->getRequest();

        // Génération d'un requestId/correlationId unique
        $requestId = $req->headers->get('X-Request-Id') ?? bin2hex(random_bytes(8));
        $req->attributes->set('request_id', $requestId);

        // Récupération de l'utilisateur connecté
        $user = $this->security->getUser();

        // Préparation des données pour le log
        $logData = [
            'event' => 'API_CALL',
            'httpMethod' => $req->getMethod(),
            'route' => $req->attributes->get('_route'),
            'path' => $req->getPathInfo(),
            'ip' => $req->getClientIp(),
            'userAgent' => $req->headers->get('User-Agent'),
            'requestId' => $requestId,
            'correlationId' => $requestId,
            'actorId' => $user ? $user->getId() : null,
            // Utilisation du username au lieu de userIdentifier
            'actorIdentifier' => $user ? $user->getUsername() : null,
            'payload' => $this->filterPayload($req->request->all() ?: $req->query->all()),
            'status' => 'START',
        ];

        $this->logger->log($logData);
    }

    public function onException(ExceptionEvent $event): void
    {
        $req = $event->getRequest();
        $user = $this->security->getUser();

        $logData = [
            'event' => 'API_CALL',
            'httpMethod' => $req->getMethod(),
            'route' => $req->attributes->get('_route'),
            'path' => $req->getPathInfo(),
            'ip' => $req->getClientIp(),
            'userAgent' => $req->headers->get('User-Agent'),
            'requestId' => $req->attributes->get('request_id'),
            'correlationId' => $req->attributes->get('request_id'),
            'actorId' => $user ? $user->getId() : null,
            // Utilisation du username au lieu de userIdentifier
            'actorIdentifier' => $user ? $user->getUsername() : null,
            'status' => 'ERROR',
            'message' => $event->getThrowable()->getMessage(),
        ];

        $this->logger->log($logData);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $req = $event->getRequest();
        $res = $event->getResponse();
        $user = $this->security->getUser();

        $logData = [
            'event' => 'API_CALL',
            'httpMethod' => $req->getMethod(),
            'route' => $req->attributes->get('_route'),
            'path' => $req->getPathInfo(),
            'ip' => $req->getClientIp(),
            'userAgent' => $req->headers->get('User-Agent'),
            'requestId' => $req->attributes->get('request_id'),
            'correlationId' => $req->attributes->get('request_id'),
            'actorId' => $user ? $user->getId() : null,
            // Utilisation du username au lieu de userIdentifier
            'actorIdentifier' => $user ? $user->getUsername() : null,
            'status' => (string)$res->getStatusCode(),
        ];

        $this->logger->log($logData);
    }

    private function filterPayload(array $data): array
    {
        $blocked = ['password', 'currentPassword', 'newPassword', 'token', '_csrf_token'];
        foreach ($blocked as $k) {
            if (array_key_exists($k, $data)) {
                $data[$k] = '***';
            }
        }
        return $data;
    }
}
