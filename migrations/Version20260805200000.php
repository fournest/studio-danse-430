<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table ldc_document pour l’historique des déclarations LDC';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE ldc_document (
                id INT AUTO_INCREMENT NOT NULL,
                uploaded_by_id INT DEFAULT NULL,
                annee VARCHAR(20) NOT NULL,
                nom_fichier VARCHAR(255) DEFAULT NULL,
                uploaded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                is_current TINYINT(1) NOT NULL DEFAULT 0,
                INDEX IDX_LDC_DOCUMENT_UPLOADED_BY (uploaded_by_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql('ALTER TABLE ldc_document ADD CONSTRAINT FK_LDC_DOCUMENT_UPLOADED_BY FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ldc_document DROP FOREIGN KEY FK_LDC_DOCUMENT_UPLOADED_BY');
        $this->addSql('DROP TABLE ldc_document');
    }
}
