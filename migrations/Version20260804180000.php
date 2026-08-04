<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260804180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cours : numero_groupe, age_min, age_max';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours ADD numero_groupe VARCHAR(30) DEFAULT NULL, ADD age_min INT DEFAULT NULL, ADD age_max INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours DROP numero_groupe, DROP age_min, DROP age_max');
    }
}
