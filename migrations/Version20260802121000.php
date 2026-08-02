<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260802121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute est_en_liste_d_attente sur inscription (capacité cours / liste d\'attente).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD est_en_liste_d_attente TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP est_en_liste_d_attente');
    }
}
