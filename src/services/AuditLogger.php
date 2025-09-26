<?php

// src/services/AuditLogger.php
namespace App\services;

use Doctrine\DBAL\Connection;

class AuditLogger
{
    public function __construct(private Connection $conn) {}

    public function log(array $data): void
    {

        $this->conn->insert('gs_mairie_audit_log', [
            'actor_id'       => $data['actorId']       ?? null,
            'actor_identifier'=> $data['actor']        ?? null,
            'event'          => $data['event']         ?? 'UNKNOWN',
            'entity_class'   => $data['entityClass']   ?? null,
            'entity_id'      => $data['entityId']      ?? null,
            'http_method'    => $data['httpMethod']    ?? null,
            'route'          => $data['route']         ?? null,
            'path'           => $data['path']          ?? null,
            'ip'             => $data['ip']            ?? null,
            'user_agent'     => $data['userAgent']     ?? null,
            'request_id'     => $data['requestId']     ?? null,
            'correlation_id' => $data['correlationId'] ?? null,
            'payload'        => isset($data['payload']) ? json_encode($data['payload']) : null,
            'changes'        => isset($data['changes']) ? json_encode($data['changes']) : null,
            'metadata'       => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'status'         => $data['status']        ?? null,
            'message'        => $data['message']       ?? null,
           
        ], [
            'integer', 'string', 'string', 'string', 'string', 'string', 'string',
            'string', 'string', 'text', 'string', 'string', 'text', 'text', 'text',
            'string', 'text'
        ]);
    }
}
