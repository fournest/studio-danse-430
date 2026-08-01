<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renomme tarif_base → tarif ; ajoute remises manuelles bureau (foyer + inscription).
 */
final class Version20260801185000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cours.tarif + remiseManuelle/motifRemise sur Foyer et Inscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours CHANGE tarif_base tarif NUMERIC(8, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE foyer ADD remise_manuelle NUMERIC(8, 2) DEFAULT NULL, ADD motif_remise VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD remise_manuelle NUMERIC(8, 2) DEFAULT NULL, ADD motif_remise VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cours CHANGE tarif tarif_base NUMERIC(8, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE foyer DROP remise_manuelle, DROP motif_remise');
        $this->addSql('ALTER TABLE inscription DROP remise_manuelle, DROP motif_remise');
    }
}
