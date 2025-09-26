<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925002722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `gs_mairie_configurations` (id INT AUTO_INCREMENT NOT NULL, valeur VARCHAR(255) DEFAULT NULL, cle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_reset_password_requests` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_11B9A5FAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `gs_mairie_reset_password_requests` ADD CONSTRAINT FK_11B9A5FAA76ED395 FOREIGN KEY (user_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE adn_configurations');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('ALTER TABLE gs_mairie_demande_terrains ADD niveau_validation_actuel_id INT NOT NULL, ADD type_titre VARCHAR(255) DEFAULT NULL, ADD terrain_akaolack TINYINT(1) DEFAULT NULL, ADD terrain_ailleurs TINYINT(1) DEFAULT NULL, ADD decision_commission LONGTEXT DEFAULT NULL, ADD rapport LONGTEXT DEFAULT NULL, ADD recommandation LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE gs_mairie_demande_terrains ADD CONSTRAINT FK_C21F8678C9C65258 FOREIGN KEY (niveau_validation_actuel_id) REFERENCES `gs_mairie_niveau_validations` (id)');
        $this->addSql('CREATE INDEX IDX_C21F8678C9C65258 ON gs_mairie_demande_terrains (niveau_validation_actuel_id)');
        $this->addSql('ALTER TABLE gs_mairie_parcelle ADD proprietaire_id INT DEFAULT NULL, ADD type_parcelle VARCHAR(255) DEFAULT NULL, ADD tf_de VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE gs_mairie_parcelle ADD CONSTRAINT FK_EF66BB0C76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('CREATE INDEX IDX_EF66BB0C76C50E4A ON gs_mairie_parcelle (proprietaire_id)');
        $this->addSql('ALTER TABLE gs_mairie_users ADD situation_matrimoniale VARCHAR(255) DEFAULT NULL, ADD nombre_enfant INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adn_configurations (id INT AUTO_INCREMENT NOT NULL, valeur VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, hashed_token VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES gs_mairie_users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE `gs_mairie_reset_password_requests` DROP FOREIGN KEY FK_11B9A5FAA76ED395');
        $this->addSql('DROP TABLE `gs_mairie_configurations`');
        $this->addSql('DROP TABLE `gs_mairie_reset_password_requests`');
        $this->addSql('ALTER TABLE `gs_mairie_demande_terrains` DROP FOREIGN KEY FK_C21F8678C9C65258');
        $this->addSql('DROP INDEX IDX_C21F8678C9C65258 ON `gs_mairie_demande_terrains`');
        $this->addSql('ALTER TABLE `gs_mairie_demande_terrains` DROP niveau_validation_actuel_id, DROP type_titre, DROP terrain_akaolack, DROP terrain_ailleurs, DROP decision_commission, DROP rapport, DROP recommandation');
        $this->addSql('ALTER TABLE `gs_mairie_parcelle` DROP FOREIGN KEY FK_EF66BB0C76C50E4A');
        $this->addSql('DROP INDEX IDX_EF66BB0C76C50E4A ON `gs_mairie_parcelle`');
        $this->addSql('ALTER TABLE `gs_mairie_parcelle` DROP proprietaire_id, DROP type_parcelle, DROP tf_de');
        $this->addSql('ALTER TABLE `gs_mairie_users` DROP situation_matrimoniale, DROP nombre_enfant');
    }
}
