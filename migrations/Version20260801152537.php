<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260801152537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE danseur ADD parent2_nom VARCHAR(255) DEFAULT NULL, ADD parent2_prenom VARCHAR(255) DEFAULT NULL, ADD parent2_telephone VARCHAR(50) DEFAULT NULL, ADD parent2_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE foyer ADD parent2_nom VARCHAR(255) DEFAULT NULL, ADD parent2_prenom VARCHAR(255) DEFAULT NULL, ADD parent2_email VARCHAR(255) DEFAULT NULL, ADD parent2_telephone VARCHAR(50) DEFAULT NULL, ADD parent2_is_different TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE inscription ADD payeur_nom VARCHAR(255) DEFAULT NULL, ADD payeur_prenom VARCHAR(255) DEFAULT NULL, ADD payeur_email VARCHAR(255) DEFAULT NULL, ADD demande_facture_ce TINYINT DEFAULT 0 NOT NULL, ADD nom_entreprise_ce VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE danseur DROP parent2_nom, DROP parent2_prenom, DROP parent2_telephone, DROP parent2_email');
        $this->addSql('ALTER TABLE foyer DROP parent2_nom, DROP parent2_prenom, DROP parent2_email, DROP parent2_telephone, DROP parent2_is_different');
        $this->addSql('ALTER TABLE inscription DROP payeur_nom, DROP payeur_prenom, DROP payeur_email, DROP demande_facture_ce, DROP nom_entreprise_ce');
    }
}
