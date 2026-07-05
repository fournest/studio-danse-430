<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260705174127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE foyer (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, adresse VARCHAR(255) NOT NULL, code_postal VARCHAR(10) NOT NULL, ville VARCHAR(255) NOT NULL, contact_urgence VARCHAR(255) DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_4EB44E88A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE foyer ADD CONSTRAINT FK_4EB44E88A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE danseur ADD foyer_id INT NOT NULL');
        $this->addSql('ALTER TABLE danseur ADD CONSTRAINT FK_DCEA39412B919A58 FOREIGN KEY (foyer_id) REFERENCES foyer (id)');
        $this->addSql('CREATE INDEX IDX_DCEA39412B919A58 ON danseur (foyer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE foyer DROP FOREIGN KEY FK_4EB44E88A76ED395');
        $this->addSql('DROP TABLE foyer');
        $this->addSql('ALTER TABLE danseur DROP FOREIGN KEY FK_DCEA39412B919A58');
        $this->addSql('DROP INDEX IDX_DCEA39412B919A58 ON danseur');
        $this->addSql('ALTER TABLE danseur DROP foyer_id');
    }
}
