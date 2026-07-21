<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260721151146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation_costume (id INT AUTO_INCREMENT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, quantite INT NOT NULL, prix_total NUMERIC(10, 2) NOT NULL, statut VARCHAR(255) NOT NULL, remarques LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, costume_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_5C9B085ECFCDCFA6 (costume_id), INDEX IDX_5C9B085EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reservation_costume ADD CONSTRAINT FK_5C9B085ECFCDCFA6 FOREIGN KEY (costume_id) REFERENCES costume (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_costume ADD CONSTRAINT FK_5C9B085EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_costume DROP FOREIGN KEY FK_5C9B085ECFCDCFA6');
        $this->addSql('ALTER TABLE reservation_costume DROP FOREIGN KEY FK_5C9B085EA76ED395');
        $this->addSql('DROP TABLE reservation_costume');
    }
}
