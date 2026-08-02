<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802094335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs santé QS-Sport / certificat médical sur danseur.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE danseur ADD statut_sante VARCHAR(255) DEFAULT \'en_attente\' NOT NULL, ADD certificat_filename VARCHAR(255) DEFAULT NULL, ADD attestation_qs_sport_valide TINYINT DEFAULT 0 NOT NULL, ADD date_signature_qs_sport DATETIME DEFAULT NULL, ADD remarque_sante LONGTEXT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE danseur DROP statut_sante, DROP certificat_filename, DROP attestation_qs_sport_valide, DROP date_signature_qs_sport, DROP remarque_sante, DROP updated_at');
    }
}
