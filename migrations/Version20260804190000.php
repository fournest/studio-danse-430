<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Commande boutique (tunnel e-commerce séparé) + mode paiement location costume';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commande_boutique (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, foyer_id INT DEFAULT NULL, email VARCHAR(120) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, nom_complet VARCHAR(120) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, code_postal VARCHAR(10) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, mode_retrait VARCHAR(255) NOT NULL, mode_paiement VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_COMMANDE_BOUTIQUE_USER (user_id), INDEX IDX_COMMANDE_BOUTIQUE_FOYER (foyer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande_boutique_ligne (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, goodie_id INT NOT NULL, taille VARCHAR(50) DEFAULT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, prix_total NUMERIC(10, 2) NOT NULL, INDEX IDX_COMMANDE_BOUTIQUE_LIGNE_CMD (commande_id), INDEX IDX_COMMANDE_BOUTIQUE_LIGNE_GOODIE (goodie_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE commande_boutique ADD CONSTRAINT FK_COMMANDE_BOUTIQUE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande_boutique ADD CONSTRAINT FK_COMMANDE_BOUTIQUE_FOYER FOREIGN KEY (foyer_id) REFERENCES foyer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE commande_boutique_ligne ADD CONSTRAINT FK_COMMANDE_BOUTIQUE_LIGNE_CMD FOREIGN KEY (commande_id) REFERENCES commande_boutique (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_boutique_ligne ADD CONSTRAINT FK_COMMANDE_BOUTIQUE_LIGNE_GOODIE FOREIGN KEY (goodie_id) REFERENCES goodie (id)');
        $this->addSql('ALTER TABLE reservation_costume ADD mode_paiement_souhaite VARCHAR(30) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande_boutique_ligne DROP FOREIGN KEY FK_COMMANDE_BOUTIQUE_LIGNE_CMD');
        $this->addSql('ALTER TABLE commande_boutique_ligne DROP FOREIGN KEY FK_COMMANDE_BOUTIQUE_LIGNE_GOODIE');
        $this->addSql('ALTER TABLE commande_boutique DROP FOREIGN KEY FK_COMMANDE_BOUTIQUE_USER');
        $this->addSql('ALTER TABLE commande_boutique DROP FOREIGN KEY FK_COMMANDE_BOUTIQUE_FOYER');
        $this->addSql('DROP TABLE commande_boutique_ligne');
        $this->addSql('DROP TABLE commande_boutique');
        $this->addSql('ALTER TABLE reservation_costume DROP mode_paiement_souhaite');
    }
}
