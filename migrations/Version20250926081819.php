<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926081819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gs_mairie_localites CHANGE nom nom VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE gs_mairie_lotissements CHANGE localisation localisation VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE gs_mairie_lots CHANGE statut statut VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE gs_mairie_users CHANGE date_naissance date_naissance DATE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `gs_mairie_localites` CHANGE nom nom VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `gs_mairie_lotissements` CHANGE localisation localisation VARCHAR(255) NOT NULL, CHANGE description description VARCHAR(255) NOT NULL, CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `gs_mairie_lots` CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `gs_mairie_users` CHANGE date_naissance date_naissance DATE DEFAULT NULL');
    }
}
