<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260802092657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute Inscription.montantTotal et la table paiement (modes de règlement / échelonnement).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) NOT NULL, mode VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, reference VARCHAR(100) DEFAULT NULL, emetteur VARCHAR(255) DEFAULT NULL, date_encaissement_prevue DATE DEFAULT NULL, date_encaissement_reelle DATE DEFAULT NULL, remarques LONGTEXT DEFAULT NULL, inscription_id INT NOT NULL, INDEX IDX_B1DC7A1E5DAC5993 (inscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E5DAC5993 FOREIGN KEY (inscription_id) REFERENCES inscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cours CHANGE duree_minutes duree_minutes INT NOT NULL, CHANGE tarif tarif NUMERIC(8, 2) NOT NULL');
        $this->addSql('ALTER TABLE inscription ADD montant_total NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E5DAC5993');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('ALTER TABLE cours CHANGE duree_minutes duree_minutes INT DEFAULT 90 NOT NULL, CHANGE tarif tarif NUMERIC(8, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE inscription DROP montant_total');
    }
}
