<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute durée, tarif de base et bornes d'âge sur les cours (saison 2026-2027).
 */
final class Version20260801184201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cours : duree_minutes, tarif_base, annee_naissance_min/max';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours ADD duree_minutes INT DEFAULT 90 NOT NULL, ADD tarif_base NUMERIC(8, 2) DEFAULT \'0.00\' NOT NULL, ADD annee_naissance_min INT DEFAULT NULL, ADD annee_naissance_max INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours DROP duree_minutes, DROP tarif_base, DROP annee_naissance_min, DROP annee_naissance_max');
    }
}
