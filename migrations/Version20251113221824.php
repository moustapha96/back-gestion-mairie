<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251113221824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_valid ON refresh_tokens');
        $this->addSql('ALTER TABLE refresh_tokens DROP created_at');
        $this->addSql('DROP INDEX idx_refresh_token ON refresh_tokens');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E1C74F2195 ON refresh_tokens (refresh_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_tokens ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_valid ON refresh_tokens (valid)');
        $this->addSql('DROP INDEX uniq_9bace7e1c74f2195 ON refresh_tokens');
        $this->addSql('CREATE UNIQUE INDEX idx_refresh_token ON refresh_tokens (refresh_token)');
    }
}
