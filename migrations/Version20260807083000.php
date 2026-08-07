<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260807083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Commande boutique : date d\'encaissement pour paiement espèces/chèque';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande_boutique ADD date_encaissement DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande_boutique DROP date_encaissement');
    }
}
