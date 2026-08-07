<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Goodie : dates de vente éphémère (début, fin) et livraison prévue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE goodie ADD date_debut DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE goodie ADD date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE goodie ADD date_livraison_prevue DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE goodie DROP date_debut, DROP date_fin, DROP date_livraison_prevue');
    }
}
