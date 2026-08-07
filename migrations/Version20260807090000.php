<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Billets événements avec QR Code (token, scan)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE billet (
            id INT AUTO_INCREMENT NOT NULL,
            event_id INT NOT NULL,
            commande_id INT DEFAULT NULL,
            user_id INT NOT NULL,
            nom_participant VARCHAR(150) NOT NULL,
            numero_place VARCHAR(100) DEFAULT NULL,
            token VARCHAR(36) NOT NULL,
            est_valide TINYINT(1) DEFAULT 0 NOT NULL,
            scanne_a DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_BILLET_TOKEN (token),
            INDEX IDX_BILLET_EVENT (event_id),
            INDEX IDX_BILLET_COMMANDE (commande_id),
            INDEX IDX_BILLET_USER (user_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE billet ADD CONSTRAINT FK_BILLET_EVENT FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE billet ADD CONSTRAINT FK_BILLET_COMMANDE FOREIGN KEY (commande_id) REFERENCES commande_boutique (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE billet ADD CONSTRAINT FK_BILLET_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE billet DROP FOREIGN KEY FK_BILLET_EVENT');
        $this->addSql('ALTER TABLE billet DROP FOREIGN KEY FK_BILLET_COMMANDE');
        $this->addSql('ALTER TABLE billet DROP FOREIGN KEY FK_BILLET_USER');
        $this->addSql('DROP TABLE billet');
    }
}
