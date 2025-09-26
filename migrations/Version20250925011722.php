<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925011722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `gs_mairie_audit_log` (id BIGINT AUTO_INCREMENT NOT NULL, actor_id INT DEFAULT NULL, actor_identifier VARCHAR(180) DEFAULT NULL, event VARCHAR(100) NOT NULL, entity_class VARCHAR(255) DEFAULT NULL, entity_id VARCHAR(64) DEFAULT NULL, http_method VARCHAR(10) DEFAULT NULL, route VARCHAR(1024) DEFAULT NULL, path VARCHAR(2048) DEFAULT NULL, ip VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, request_id VARCHAR(64) DEFAULT NULL, correlation_id VARCHAR(64) DEFAULT NULL, payload LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', changes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', status VARCHAR(20) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_auditlog_created (created_at), INDEX idx_auditlog_actor (actor_id), INDEX idx_auditlog_event (event), INDEX idx_auditlog_entity (entity_class, entity_id), INDEX idx_auditlog_request (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `gs_mairie_audit_log`');
    }
}
