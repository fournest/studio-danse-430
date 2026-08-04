<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804155500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Boutique goodies + rattachement des achats/locations au foyer.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE goodie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, categorie VARCHAR(50) NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, tailles_disponibles JSON DEFAULT NULL, stock INT DEFAULT 0 NOT NULL, image_filename VARCHAR(255) DEFAULT NULL, est_actif TINYINT(1) DEFAULT 1 NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE achat_goodie (id INT AUTO_INCREMENT NOT NULL, foyer_id INT NOT NULL, goodie_id INT NOT NULL, saison VARCHAR(20) NOT NULL, taille VARCHAR(50) DEFAULT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, prix_total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_ACHAT_FOYER (foyer_id), INDEX IDX_ACHAT_GOODIE (goodie_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE achat_goodie ADD CONSTRAINT FK_ACHAT_FOYER FOREIGN KEY (foyer_id) REFERENCES foyer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE achat_goodie ADD CONSTRAINT FK_ACHAT_GOODIE FOREIGN KEY (goodie_id) REFERENCES goodie (id)');
        $this->addSql('ALTER TABLE reservation_costume ADD foyer_id INT DEFAULT NULL, ADD saison VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation_costume ADD CONSTRAINT FK_RES_COSTUME_FOYER FOREIGN KEY (foyer_id) REFERENCES foyer (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_RES_COSTUME_FOYER ON reservation_costume (foyer_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE achat_goodie DROP FOREIGN KEY FK_ACHAT_FOYER');
        $this->addSql('ALTER TABLE achat_goodie DROP FOREIGN KEY FK_ACHAT_GOODIE');
        $this->addSql('DROP TABLE achat_goodie');
        $this->addSql('DROP TABLE goodie');
        $this->addSql('ALTER TABLE reservation_costume DROP FOREIGN KEY FK_RES_COSTUME_FOYER');
        $this->addSql('DROP INDEX IDX_RES_COSTUME_FOYER ON reservation_costume');
        $this->addSql('ALTER TABLE reservation_costume DROP foyer_id, DROP saison');
    }
}
