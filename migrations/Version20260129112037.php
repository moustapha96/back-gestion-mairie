<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129112037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_messages (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(40) NOT NULL, categorie VARCHAR(40) NOT NULL, reference VARCHAR(40) DEFAULT NULL, message LONGTEXT NOT NULL, consent TINYINT(1) NOT NULL, piece_jointe VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_articles_terrains` (id INT AUTO_INCREMENT NOT NULL, categorie_id INT DEFAULT NULL, auteur_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, content LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B5715083BCF5E72D (categorie_id), INDEX IDX_B571508360BB6FE6 (auteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_attribuation_parcelle` (id INT AUTO_INCREMENT NOT NULL, demande_id INT DEFAULT NULL, parcelle_id INT DEFAULT NULL, date_effet DATETIME DEFAULT NULL, date_fin DATETIME DEFAULT NULL, montant DOUBLE PRECISION DEFAULT NULL, frequence VARCHAR(255) DEFAULT NULL, etat_paiement TINYINT(1) DEFAULT NULL, conditions_mise_en_valeur LONGTEXT DEFAULT NULL, duree_validation VARCHAR(255) DEFAULT NULL, decision_conseil VARCHAR(255) DEFAULT NULL, pv_commision LONGTEXT DEFAULT NULL, pv_validation_provisoire LONGTEXT DEFAULT NULL, pv_attribution_provisoire LONGTEXT DEFAULT NULL, pv_approbation_prefet LONGTEXT DEFAULT NULL, pv_approbation_conseil LONGTEXT DEFAULT NULL, statut_attribution VARCHAR(255) DEFAULT \'DRAFT\' NOT NULL, date_validation_provisoire DATETIME DEFAULT NULL, date_attribution_provisoire DATETIME DEFAULT NULL, date_approbation_prefet DATETIME DEFAULT NULL, date_approbation_conseil DATETIME DEFAULT NULL, date_attribution_definitive DATETIME DEFAULT NULL, doc_notification_url VARCHAR(255) DEFAULT NULL, pdf_notification_url VARCHAR(255) DEFAULT NULL, bulletin_liquidation_url VARCHAR(255) DEFAULT NULL, numero VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_FD50F1F980E95E18 (demande_id), UNIQUE INDEX UNIQ_FD50F1F94433ED66 (parcelle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_audit_log` (id BIGINT AUTO_INCREMENT NOT NULL, actor_id INT DEFAULT NULL, actor_identifier VARCHAR(180) DEFAULT NULL, event VARCHAR(100) NOT NULL, entity_class VARCHAR(255) DEFAULT NULL, entity_id VARCHAR(64) DEFAULT NULL, http_method VARCHAR(10) DEFAULT NULL, route VARCHAR(1024) DEFAULT NULL, path VARCHAR(2048) DEFAULT NULL, ip VARCHAR(64) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, request_id VARCHAR(64) DEFAULT NULL, correlation_id VARCHAR(64) DEFAULT NULL, payload LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', changes LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', metadata LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', status VARCHAR(20) DEFAULT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_auditlog_created (created_at), INDEX idx_auditlog_actor (actor_id), INDEX idx_auditlog_event (event), INDEX idx_auditlog_entity (entity_class, entity_id), INDEX idx_auditlog_request (request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_categories_terrains` (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_configurations` (id INT AUTO_INCREMENT NOT NULL, valeur VARCHAR(255) DEFAULT NULL, cle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gs_mairie_demandes (id INT AUTO_INCREMENT NOT NULL, quartier_id INT DEFAULT NULL, utilisateur_id INT DEFAULT NULL, type_demande VARCHAR(30) NOT NULL, superficie DOUBLE PRECISION DEFAULT NULL, usage_prevu VARCHAR(255) DEFAULT NULL, possede_autre_terrain TINYINT(1) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, motif_refus LONGTEXT DEFAULT NULL, date_creation DATETIME DEFAULT NULL, date_modification DATETIME DEFAULT NULL, type_document VARCHAR(255) DEFAULT NULL, recto VARCHAR(255) DEFAULT NULL, verso VARCHAR(255) DEFAULT NULL, type_titre VARCHAR(255) DEFAULT NULL, terrain_akaolack TINYINT(1) DEFAULT NULL, terrain_ailleurs TINYINT(1) DEFAULT NULL, decision_commission LONGTEXT DEFAULT NULL, rapport LONGTEXT DEFAULT NULL, recommandation LONGTEXT DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, lieu_naissance VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, profession VARCHAR(255) DEFAULT NULL, telephone VARCHAR(13) DEFAULT NULL, numero_electeur VARCHAR(20) DEFAULT NULL, habitant TINYINT(1) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, situation_matrimoniale VARCHAR(255) DEFAULT NULL, nombre_enfant INT DEFAULT NULL, statut_logement VARCHAR(255) DEFAULT NULL, localite VARCHAR(255) DEFAULT NULL, numero VARCHAR(255) DEFAULT NULL, INDEX IDX_979C9B41DF1E57AB (quartier_id), INDEX IDX_979C9B41FB88E14F (utilisateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_documents` (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, contenu LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', date_creation DATETIME NOT NULL, fichier VARCHAR(255) DEFAULT NULL, is_generated TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_images_article` (id INT AUTO_INCREMENT NOT NULL, article_id INT NOT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_BA063AA27294869C (article_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_localites` (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, prix DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_lotissements` (id INT AUTO_INCREMENT NOT NULL, localite_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, INDEX IDX_920C80D2924DD2B5 (localite_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_lots` (id INT AUTO_INCREMENT NOT NULL, lotissement_id INT DEFAULT NULL, numero_lot VARCHAR(255) DEFAULT NULL, superficie DOUBLE PRECISION DEFAULT NULL, type_usage VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, prix DOUBLE PRECISION DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, INDEX IDX_86DA0B6DF79944C3 (lotissement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_parcelle` (id INT AUTO_INCREMENT NOT NULL, lotissement_id INT DEFAULT NULL, proprietaire_id INT DEFAULT NULL, numero VARCHAR(20) DEFAULT NULL, surface DOUBLE PRECISION DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, type_parcelle VARCHAR(255) DEFAULT NULL, tf_de VARCHAR(255) DEFAULT NULL, INDEX IDX_EF66BB0CF79944C3 (lotissement_id), INDEX IDX_EF66BB0C76C50E4A (proprietaire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_plan_lotissements` (id INT AUTO_INCREMENT NOT NULL, lotissement_id INT DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, version VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_F9380669F79944C3 (lotissement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_reset_password_requests` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_11B9A5FAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_signatures` (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, date_signature DATETIME DEFAULT NULL, signature VARCHAR(255) DEFAULT NULL, ordre INT NOT NULL, UNIQUE INDEX UNIQ_9A4313D4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_titre_fonciers` (id INT AUTO_INCREMENT NOT NULL, quartier_id INT DEFAULT NULL, numero VARCHAR(255) DEFAULT NULL, superficie DOUBLE PRECISION DEFAULT NULL, titre_figure LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', etat_droit_reel LONGTEXT DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, fichier VARCHAR(255) DEFAULT NULL, INDEX IDX_CC59D394DF1E57AB (quartier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `gs_mairie_users` (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expired_at DATETIME DEFAULT NULL, enabled TINYINT(1) DEFAULT NULL, activeted TINYINT(1) DEFAULT NULL, token_activeted VARCHAR(255) DEFAULT NULL, password_claire VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, date_naissance DATE NOT NULL, lieu_naissance VARCHAR(255) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, profession VARCHAR(255) DEFAULT NULL, telephone VARCHAR(13) DEFAULT NULL, numero_electeur VARCHAR(20) DEFAULT NULL, habitant TINYINT(1) DEFAULT NULL, situation_matrimoniale VARCHAR(255) DEFAULT NULL, nombre_enfant INT DEFAULT NULL, situationdemande_demandeurur VARCHAR(255) DEFAULT NULL, situation_demandeur VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_5B4BED37F85E0677 (username), UNIQUE INDEX UNIQ_5B4BED37E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `gs_mairie_articles_terrains` ADD CONSTRAINT FK_B5715083BCF5E72D FOREIGN KEY (categorie_id) REFERENCES `gs_mairie_categories_terrains` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_articles_terrains` ADD CONSTRAINT FK_B571508360BB6FE6 FOREIGN KEY (auteur_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_attribuation_parcelle` ADD CONSTRAINT FK_FD50F1F980E95E18 FOREIGN KEY (demande_id) REFERENCES gs_mairie_demandes (id)');
        $this->addSql('ALTER TABLE `gs_mairie_attribuation_parcelle` ADD CONSTRAINT FK_FD50F1F94433ED66 FOREIGN KEY (parcelle_id) REFERENCES `gs_mairie_parcelle` (id)');
        $this->addSql('ALTER TABLE gs_mairie_demandes ADD CONSTRAINT FK_979C9B41DF1E57AB FOREIGN KEY (quartier_id) REFERENCES `gs_mairie_localites` (id)');
        $this->addSql('ALTER TABLE gs_mairie_demandes ADD CONSTRAINT FK_979C9B41FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_images_article` ADD CONSTRAINT FK_BA063AA27294869C FOREIGN KEY (article_id) REFERENCES `gs_mairie_articles_terrains` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_lotissements` ADD CONSTRAINT FK_920C80D2924DD2B5 FOREIGN KEY (localite_id) REFERENCES `gs_mairie_localites` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_lots` ADD CONSTRAINT FK_86DA0B6DF79944C3 FOREIGN KEY (lotissement_id) REFERENCES `gs_mairie_lotissements` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_parcelle` ADD CONSTRAINT FK_EF66BB0CF79944C3 FOREIGN KEY (lotissement_id) REFERENCES `gs_mairie_lotissements` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_parcelle` ADD CONSTRAINT FK_EF66BB0C76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_plan_lotissements` ADD CONSTRAINT FK_F9380669F79944C3 FOREIGN KEY (lotissement_id) REFERENCES `gs_mairie_lotissements` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_reset_password_requests` ADD CONSTRAINT FK_11B9A5FAA76ED395 FOREIGN KEY (user_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_signatures` ADD CONSTRAINT FK_9A4313D4A76ED395 FOREIGN KEY (user_id) REFERENCES `gs_mairie_users` (id)');
        $this->addSql('ALTER TABLE `gs_mairie_titre_fonciers` ADD CONSTRAINT FK_CC59D394DF1E57AB FOREIGN KEY (quartier_id) REFERENCES `gs_mairie_localites` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `gs_mairie_articles_terrains` DROP FOREIGN KEY FK_B5715083BCF5E72D');
        $this->addSql('ALTER TABLE `gs_mairie_articles_terrains` DROP FOREIGN KEY FK_B571508360BB6FE6');
        $this->addSql('ALTER TABLE `gs_mairie_attribuation_parcelle` DROP FOREIGN KEY FK_FD50F1F980E95E18');
        $this->addSql('ALTER TABLE `gs_mairie_attribuation_parcelle` DROP FOREIGN KEY FK_FD50F1F94433ED66');
        $this->addSql('ALTER TABLE gs_mairie_demandes DROP FOREIGN KEY FK_979C9B41DF1E57AB');
        $this->addSql('ALTER TABLE gs_mairie_demandes DROP FOREIGN KEY FK_979C9B41FB88E14F');
        $this->addSql('ALTER TABLE `gs_mairie_images_article` DROP FOREIGN KEY FK_BA063AA27294869C');
        $this->addSql('ALTER TABLE `gs_mairie_lotissements` DROP FOREIGN KEY FK_920C80D2924DD2B5');
        $this->addSql('ALTER TABLE `gs_mairie_lots` DROP FOREIGN KEY FK_86DA0B6DF79944C3');
        $this->addSql('ALTER TABLE `gs_mairie_parcelle` DROP FOREIGN KEY FK_EF66BB0CF79944C3');
        $this->addSql('ALTER TABLE `gs_mairie_parcelle` DROP FOREIGN KEY FK_EF66BB0C76C50E4A');
        $this->addSql('ALTER TABLE `gs_mairie_plan_lotissements` DROP FOREIGN KEY FK_F9380669F79944C3');
        $this->addSql('ALTER TABLE `gs_mairie_reset_password_requests` DROP FOREIGN KEY FK_11B9A5FAA76ED395');
        $this->addSql('ALTER TABLE `gs_mairie_signatures` DROP FOREIGN KEY FK_9A4313D4A76ED395');
        $this->addSql('ALTER TABLE `gs_mairie_titre_fonciers` DROP FOREIGN KEY FK_CC59D394DF1E57AB');
        $this->addSql('DROP TABLE contact_messages');
        $this->addSql('DROP TABLE `gs_mairie_articles_terrains`');
        $this->addSql('DROP TABLE `gs_mairie_attribuation_parcelle`');
        $this->addSql('DROP TABLE `gs_mairie_audit_log`');
        $this->addSql('DROP TABLE `gs_mairie_categories_terrains`');
        $this->addSql('DROP TABLE `gs_mairie_configurations`');
        $this->addSql('DROP TABLE gs_mairie_demandes');
        $this->addSql('DROP TABLE `gs_mairie_documents`');
        $this->addSql('DROP TABLE `gs_mairie_images_article`');
        $this->addSql('DROP TABLE `gs_mairie_localites`');
        $this->addSql('DROP TABLE `gs_mairie_lotissements`');
        $this->addSql('DROP TABLE `gs_mairie_lots`');
        $this->addSql('DROP TABLE `gs_mairie_parcelle`');
        $this->addSql('DROP TABLE `gs_mairie_plan_lotissements`');
        $this->addSql('DROP TABLE `gs_mairie_reset_password_requests`');
        $this->addSql('DROP TABLE `gs_mairie_signatures`');
        $this->addSql('DROP TABLE `gs_mairie_titre_fonciers`');
        $this->addSql('DROP TABLE `gs_mairie_users`');
    }
}
