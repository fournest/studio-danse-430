<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628143127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE costume (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, taille LONGTEXT DEFAULT NULL, description LONGTEXT DEFAULT NULL, phot VARCHAR(255) DEFAULT NULL, cours_id INT NOT NULL, INDEX IDX_3B0EB3DB7ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE costume ADD CONSTRAINT FK_3B0EB3DB7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE costume DROP FOREIGN KEY FK_3B0EB3DB7ECF78B0');
        $this->addSql('DROP TABLE costume');
    }
}
