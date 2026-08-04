<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Invitations co-parent avec jeton unique temporaire.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invitation_coparent (id INT AUTO_INCREMENT NOT NULL, danseur_id INT NOT NULL, accepted_by_id INT DEFAULT NULL, token VARCHAR(64) NOT NULL, email VARCHAR(180) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_INVITATION_COPARENT_TOKEN (token), INDEX IDX_INVITATION_COPARENT_DANSEUR (danseur_id), INDEX IDX_INVITATION_COPARENT_ACCEPTED_BY (accepted_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invitation_coparent ADD CONSTRAINT FK_INVITATION_COPARENT_DANSEUR FOREIGN KEY (danseur_id) REFERENCES danseur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invitation_coparent ADD CONSTRAINT FK_INVITATION_COPARENT_ACCEPTED_BY FOREIGN KEY (accepted_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE invitation_coparent DROP FOREIGN KEY FK_INVITATION_COPARENT_DANSEUR');
        $this->addSql('ALTER TABLE invitation_coparent DROP FOREIGN KEY FK_INVITATION_COPARENT_ACCEPTED_BY');
        $this->addSql('DROP TABLE invitation_coparent');
    }
}
