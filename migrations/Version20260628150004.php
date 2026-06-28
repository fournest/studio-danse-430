<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260628150004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE costume DROP FOREIGN KEY `FK_3B0EB3DB7ECF78B0`');
        $this->addSql('DROP INDEX IDX_3B0EB3DB7ECF78B0 ON costume');
        $this->addSql('ALTER TABLE costume DROP cours_id, CHANGE taille taille VARCHAR(50) DEFAULT NULL, CHANGE prix prix INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE costume ADD cours_id INT NOT NULL, CHANGE taille taille LONGTEXT DEFAULT NULL, CHANGE prix prix INT NOT NULL');
        $this->addSql('ALTER TABLE costume ADD CONSTRAINT `FK_3B0EB3DB7ECF78B0` FOREIGN KEY (cours_id) REFERENCES cours (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_3B0EB3DB7ECF78B0 ON costume (cours_id)');
    }
}
