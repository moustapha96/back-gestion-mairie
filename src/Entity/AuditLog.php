<?php
// src/Entity/AuditLog.php
namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: '`gs_mairie_audit_log`')]
#[ORM\Index(columns: ["created_at"], name: "idx_auditlog_created")]
#[ORM\Index(columns: ["actor_id"], name: "idx_auditlog_actor")]
#[ORM\Index(columns: ["event"], name: "idx_auditlog_event")]
#[ORM\Index(columns: ["entity_class", "entity_id"], name: "idx_auditlog_entity")]
#[ORM\Index(columns: ["request_id"], name: "idx_auditlog_request")]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "bigint")]
    private ?int $id = null;

    // Qui a fait l’action
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $actorId = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $actorIdentifier = null; // username/email

    // Quoi
    #[ORM\Column(length: 100)]
    private string $event; // ex: LOGIN, API_CALL, ENTITY_CREATED, ENTITY_UPDATED, ENTITY_DELETED, BUSINESS_ACTION

    // Cible éventuelle (entité métier)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $entityClass = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $entityId = null;

    // Contexte HTTP
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $httpMethod = null;

    #[ORM\Column(length: 1024, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    // Corrélation requête
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $requestId = null; // ex: header X-Request-Id

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $correlationId = null; // pour chaîner plusieurs requêtes

    // Données
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null; // request payload (filtré)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $changes = null; // diff Doctrine pour entités

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null; // extra (orgId, tenant, headers utiles...)

    // Résultat
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = null; // SUCCESS/ERROR

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: "datetime_immutable")]
    public \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters et Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getActorId(): ?int
    {
        return $this->actorId;
    }

    public function setActorId(?int $actorId): self
    {
        $this->actorId = $actorId;
        return $this;
    }

    public function getActorIdentifier(): ?string
    {
        return $this->actorIdentifier;
    }

    public function setActorIdentifier(?string $actorIdentifier): self
    {
        $this->actorIdentifier = $actorIdentifier;
        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(?string $entityClass): self
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId): self
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    public function setHttpMethod(?string $httpMethod): self
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function setRoute(?string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function setCorrelationId(?string $correlationId): self
    {
        $this->correlationId = $correlationId;
        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function setChanges(?array $changes): self
    {
        $this->changes = $changes;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
