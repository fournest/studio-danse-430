<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Costumes hors gala : disponibilité, tarif location, thème et genre.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE costume ADD disponible_hors_gala TINYINT(1) DEFAULT 1 NOT NULL, ADD tarif_location_hors_gala NUMERIC(10, 2) DEFAULT NULL, ADD theme VARCHAR(100) DEFAULT NULL, ADD genre VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE costume DROP disponible_hors_gala, DROP tarif_location_hors_gala, DROP theme, DROP genre');
    }
}
