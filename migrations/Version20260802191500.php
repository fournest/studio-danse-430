<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802191500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la référence unique de virement sur le foyer (ex. COTIS-2026-DUPONT).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE foyer ADD reference_virement VARCHAR(64) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE foyer DROP reference_virement');
    }
}
