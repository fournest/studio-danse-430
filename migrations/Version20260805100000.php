<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Table demande_fusion_foyer (raccordement de foyers à la même adresse)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE demande_fusion_foyer (id INT AUTO_INCREMENT NOT NULL, foyer_source_id INT NOT NULL, foyer_target_id INT NOT NULL, demandeur_id INT NOT NULL, accepte_par_id INT DEFAULT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_DEMANDE_FUSION_TOKEN (token), INDEX IDX_DEMANDE_FUSION_SOURCE (foyer_source_id), INDEX IDX_DEMANDE_FUSION_TARGET (foyer_target_id), INDEX IDX_DEMANDE_FUSION_DEMANDEUR (demandeur_id), INDEX IDX_DEMANDE_FUSION_ACCEPTE (accepte_par_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_fusion_foyer ADD CONSTRAINT FK_DEMANDE_FUSION_SOURCE FOREIGN KEY (foyer_source_id) REFERENCES foyer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_fusion_foyer ADD CONSTRAINT FK_DEMANDE_FUSION_TARGET FOREIGN KEY (foyer_target_id) REFERENCES foyer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_fusion_foyer ADD CONSTRAINT FK_DEMANDE_FUSION_DEMANDEUR FOREIGN KEY (demandeur_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande_fusion_foyer ADD CONSTRAINT FK_DEMANDE_FUSION_ACCEPTE FOREIGN KEY (accepte_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE demande_fusion_foyer DROP FOREIGN KEY FK_DEMANDE_FUSION_SOURCE');
        $this->addSql('ALTER TABLE demande_fusion_foyer DROP FOREIGN KEY FK_DEMANDE_FUSION_TARGET');
        $this->addSql('ALTER TABLE demande_fusion_foyer DROP FOREIGN KEY FK_DEMANDE_FUSION_DEMANDEUR');
        $this->addSql('ALTER TABLE demande_fusion_foyer DROP FOREIGN KEY FK_DEMANDE_FUSION_ACCEPTE');
        $this->addSql('DROP TABLE demande_fusion_foyer');
    }
}
