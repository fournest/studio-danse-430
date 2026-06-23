<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623122255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cours (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, jour VARCHAR(10) NOT NULL, heure TIME NOT NULL, professeur VARCHAR(50) NOT NULL, capacite_max INT NOT NULL, whatsapp_group_link VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE danseur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, date_naissance DATE NOT NULL, parent_id INT NOT NULL, INDEX IDX_DCEA3941727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE danseur_cours (danseur_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_22960E365942A4C7 (danseur_id), INDEX IDX_22960E367ECF78B0 (cours_id), PRIMARY KEY (danseur_id, cours_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gala (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, date_heure DATETIME NOT NULL, billetweb_event_id VARCHAR(100) DEFAULT NULL, places_disponibles INT NOT NULL, salle_id INT NOT NULL, INDEX IDX_3D5DC4D4DC304035 (salle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inscription (id INT AUTO_INCREMENT NOT NULL, saison VARCHAR(20) NOT NULL, statut_dossier VARCHAR(255) NOT NULL, certificat_medical VARCHAR(255) DEFAULT NULL, statut_paiement VARCHAR(255) NOT NULL, mode_paiement VARCHAR(50) DEFAULT NULL, hello_asso_payment_id VARCHAR(100) DEFAULT NULL, danseur_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_5E90F6D65942A4C7 (danseur_id), INDEX IDX_5E90F6D67ECF78B0 (cours_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE salle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, adresse VARCHAR(255) NOT NULL, capacite INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE danseur ADD CONSTRAINT FK_DCEA3941727ACA70 FOREIGN KEY (parent_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE danseur_cours ADD CONSTRAINT FK_22960E365942A4C7 FOREIGN KEY (danseur_id) REFERENCES danseur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE danseur_cours ADD CONSTRAINT FK_22960E367ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE gala ADD CONSTRAINT FK_3D5DC4D4DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D65942A4C7 FOREIGN KEY (danseur_id) REFERENCES danseur (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D67ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE danseur DROP FOREIGN KEY FK_DCEA3941727ACA70');
        $this->addSql('ALTER TABLE danseur_cours DROP FOREIGN KEY FK_22960E365942A4C7');
        $this->addSql('ALTER TABLE danseur_cours DROP FOREIGN KEY FK_22960E367ECF78B0');
        $this->addSql('ALTER TABLE gala DROP FOREIGN KEY FK_3D5DC4D4DC304035');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D65942A4C7');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D67ECF78B0');
        $this->addSql('DROP TABLE cours');
        $this->addSql('DROP TABLE danseur');
        $this->addSql('DROP TABLE danseur_cours');
        $this->addSql('DROP TABLE gala');
        $this->addSql('DROP TABLE inscription');
        $this->addSql('DROP TABLE salle');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
