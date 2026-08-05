<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Relances famille : dates sur inscription et paiement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD last_pieces_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD last_payment_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE paiement ADD last_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP last_pieces_reminder_sent_at, DROP last_payment_reminder_sent_at');
        $this->addSql('ALTER TABLE paiement DROP last_reminder_sent_at');
    }
}
