<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250929124704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gs_mairie_historique_validations ADD niveau_nom VARCHAR(150) DEFAULT NULL, ADD niveau_ordre INT DEFAULT NULL, ADD role_requis VARCHAR(100) DEFAULT NULL, ADD statut_avant VARCHAR(100) DEFAULT NULL, ADD statut_apres VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `gs_mairie_historique_validations` DROP niveau_nom, DROP niveau_ordre, DROP role_requis, DROP statut_avant, DROP statut_apres');
    }
}
