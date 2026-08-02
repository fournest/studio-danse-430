<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802183700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute parent2_invited_at sur danseur (invitation co-parent).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE danseur ADD parent2_invited_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE danseur DROP parent2_invited_at');
    }
}
