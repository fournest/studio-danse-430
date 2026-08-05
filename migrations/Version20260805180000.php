<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table page_legale pour les pages légales du site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE page_legale (
                id INT AUTO_INCREMENT NOT NULL,
                titre VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                contenu LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_PAGE_LEGALE_SLUG (slug),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE page_legale');
    }
}
