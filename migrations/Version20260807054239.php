<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807054239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor Gala → Event : rename table, drop billetweb_event_id, add type et mode_placement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gala DROP FOREIGN KEY FK_3D5DC4D4DC304035');
        $this->addSql('RENAME TABLE gala TO event');
        $this->addSql('ALTER TABLE event DROP billetweb_event_id');
        $this->addSql("ALTER TABLE event ADD type VARCHAR(255) DEFAULT 'Gala de Danse' NOT NULL");
        $this->addSql("ALTER TABLE event ADD mode_placement VARCHAR(255) DEFAULT 'Placement Libre (Jauge simple)' NOT NULL");
        $this->addSql('ALTER TABLE event CHANGE date_heure date_heure DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE event RENAME INDEX IDX_3D5DC4D4DC304035 TO IDX_3BAE0AA7DC304035');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7DC304035');
        $this->addSql('ALTER TABLE event DROP type, DROP mode_placement');
        $this->addSql('ALTER TABLE event ADD billetweb_event_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE event CHANGE date_heure date_heure DATETIME NOT NULL');
        $this->addSql('ALTER TABLE event RENAME INDEX IDX_3BAE0AA7DC304035 TO IDX_3D5DC4D4DC304035');
        $this->addSql('RENAME TABLE event TO gala');
        $this->addSql('ALTER TABLE gala ADD CONSTRAINT FK_3D5DC4D4DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
    }
}
