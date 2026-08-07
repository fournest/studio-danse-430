<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Event : consignesStaff + bénévoles (ManyToMany User)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD consignes_staff LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE TABLE event_benevole (
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            INDEX IDX_EVENT_BENEVOLE_EVENT (event_id),
            INDEX IDX_EVENT_BENEVOLE_USER (user_id),
            PRIMARY KEY (event_id, user_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_benevole ADD CONSTRAINT FK_EVENT_BENEVOLE_EVENT FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_benevole ADD CONSTRAINT FK_EVENT_BENEVOLE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_benevole DROP FOREIGN KEY FK_EVENT_BENEVOLE_EVENT');
        $this->addSql('ALTER TABLE event_benevole DROP FOREIGN KEY FK_EVENT_BENEVOLE_USER');
        $this->addSql('DROP TABLE event_benevole');
        $this->addSql('ALTER TABLE event DROP consignes_staff');
    }
}
