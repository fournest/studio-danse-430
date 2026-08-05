<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Actualite : option publierDansFil pour le fil d’actualités';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite ADD publier_dans_fil TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE actualite DROP publier_dans_fil');
    }
}
