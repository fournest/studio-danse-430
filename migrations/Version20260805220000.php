<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Paiement : date de déclaration famille + statut paiement_declare';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE paiement ADD date_declaration DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE paiement SET statut = \'paiement_declare\' WHERE statut = \'recu\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE paiement SET statut = \'recu\' WHERE statut = \'paiement_declare\'');
        $this->addSql('ALTER TABLE paiement DROP date_declaration');
    }
}
