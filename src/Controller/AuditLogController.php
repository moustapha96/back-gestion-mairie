<?php
// src/Controller/AuditLogController.php
namespace App\Controller;

use App\Repository\AuditLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/audit-logs', name: 'audit_logs_')]
final class AuditLogController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, AuditLogRepository $repo): JsonResponse
    {
        // Query params
        $page       = (int)$request->query->get('page', 1);
        $size       = (int)$request->query->get('size', 20);
        $sort       = (string)$request->query->get('sort', 'id,DESC'); // ex: event,ASC

        $event      = $request->query->get('event');
        $actorId    = $request->query->get('actorId');
        $entityClass= $request->query->get('entityClass');
        $entityId   = $request->query->get('entityId');
        $status     = $request->query->get('status');
        $requestId  = $request->query->get('requestId');

        $fromStr    = $request->query->get('from'); // ISO 8601 recommandé
        $toStr      = $request->query->get('to');

        $from = !empty($fromStr) ? new \DateTimeImmutable($fromStr) : null;
        $to   = !empty($toStr)   ? new \DateTimeImmutable($toStr)   : null;

        $result = $repo->searchPaginated([
            'page'        => $page,
            'size'        => $size,
            'sort'        => $sort,
            'event'       => $event ?: null,
            'actorId'     => $actorId ? (int)$actorId : null,
            'entityClass' => $entityClass ?: null,
            'entityId'    => $entityId ?: null,
            'status'      => $status ?: null,
            'requestId'   => $requestId ?: null,
            'from'        => $from,
            'to'          => $to,
        ]);

        // Normalisation simple -> projection légère pour éviter d’exposer tout l’objet
        $data = array_map(function($log) {
            /** @var \App\Entity\AuditLog $log */
            return [
                'id'            => (int)$log->getId(),
                'event'         => $log->getEvent(),
                'actorId'       => $log->getActorId(),
                'actor'         => $log->getActorIdentifier(),
                'entityClass'   => $log->getEntityClass(),
                'entityId'      => $log->getEntityId(),
                'httpMethod'    => $log->getHttpMethod(),
                'route'         => $log->getRoute(),
                'path'          => $log->getPath(),
                'ip'            => $log->getIp(),
                'userAgent'     => $log->getUserAgent(),
                'requestId'     => $log->getRequestId(),
                'correlationId' => $log->getCorrelationId(),
                'payload'       => $log->getPayload(),   // déjà filtré côté logger
                'changes'       => $log->getChanges(),
                'metadata'      => $log->getMetadata(),
                'status'        => $log->getStatus(),
                'message'       => $log->getMessage(),
                'createdAt'     =>  null,

            ];
        }, $result['items']);

        return $this->json([
            'data' => $data,
            'meta' => [
                'page'  => $result['page'],
                'size'  => $result['size'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'sort'  => $sort,
                'filters' => array_filter([
                    'event' => $event,
                    'actorId' => $actorId,
                    'entityClass' => $entityClass,
                    'entityId' => $entityId,
                    'status' => $status,
                    'requestId' => $requestId,
                    'from' => $from?->format(\DateTimeInterface::ATOM),
                    'to'   => $to?->format(\DateTimeInterface::ATOM),
                ]),
            ],
        ]);
    }
}
