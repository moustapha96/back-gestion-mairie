<?php

// src/EventSubscriber/DoctrineAuditSubscriber.php
namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\services\AuditLogger;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class DoctrineAuditSubscriber
{
    public function __construct(private AuditLogger $logger) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        // CREATED
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof AuditLog) { continue; } // ⛔️ ne pas se logger soi-même
            $this->logger->log([
                'event'       => 'ENTITY_CREATED',
                'entityClass' => $entity::class,
                'entityId'    => method_exists($entity, 'getId') ? (string)$entity->getId() : null,
                'changes'     => $this->toScalarArray($uow->getEntityChangeSet($entity)),
                'status'      => 'SUCCESS',
            ]);
        }

        // UPDATED
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof AuditLog) { continue; }
            $this->logger->log([
                'event'       => 'ENTITY_UPDATED',
                'entityClass' => $entity::class,
                'entityId'    => method_exists($entity, 'getId') ? (string)$entity->getId() : null,
                'changes'     => $this->toScalarArray($uow->getEntityChangeSet($entity)),
                'status'      => 'SUCCESS',
                'created_at'      => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        }

        // DELETED
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof AuditLog) { continue; }
            $this->logger->log([
                'event'       => 'ENTITY_DELETED',
                'entityClass' => $entity::class,
                'entityId'    => method_exists($entity, 'getId') ? (string)$entity->getId() : null,
                'status'      => 'SUCCESS',
            ]);
        }
    }

    private function toScalarArray(array $changeSet): array
    {
        $out = [];
        foreach ($changeSet as $field => [$old, $new]) {
            $out[$field] = [
                'old' => $this->scalarize($old),
                'new' => $this->scalarize($new),
            ];
        }
        return $out;
    }

    private function scalarize(mixed $v): mixed
    {
        if ($v instanceof \DateTimeInterface) {
            return $v->format(\DateTimeInterface::ATOM);
        }
        if (is_object($v)) {
            if (method_exists($v, 'getId')) {
                return ['_class' => $v::class, 'id' => (string)$v->getId()];
            }
            return ['_class' => $v::class];
        }
        if (is_array($v)) {
            return array_map(fn($x) => $this->scalarize($x), $v);
        }
        return $v;
    }
}
