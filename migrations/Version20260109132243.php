<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109132243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ created_at à la table refresh_tokens avec une valeur par défaut';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne existe déjà
        $table = $schema->getTable('refresh_tokens');
        
        if (!$table->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE refresh_tokens ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        } else {
            // Si la colonne existe mais n'a pas de valeur par défaut, on la modifie
            $this->addSql('ALTER TABLE refresh_tokens MODIFY created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('refresh_tokens');
        
        if ($table->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE refresh_tokens DROP created_at');
        }
    }
}
