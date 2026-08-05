<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805094608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_activation_token CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE account_activation_token RENAME INDEX uniq_aat_token_hash TO UNIQ_D7523EBFB3BC57DA');
        $this->addSql('ALTER TABLE account_activation_token RENAME INDEX idx_aat_user TO IDX_D7523EBFA76ED395');
        $this->addSql('ALTER TABLE achat_goodie RENAME INDEX idx_achat_foyer TO IDX_DE54DF882B919A58');
        $this->addSql('ALTER TABLE achat_goodie RENAME INDEX idx_achat_goodie TO IDX_DE54DF88388135BB');
        $this->addSql('ALTER TABLE actualite CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commande_boutique CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commande_boutique RENAME INDEX idx_commande_boutique_user TO IDX_770224CEA76ED395');
        $this->addSql('ALTER TABLE commande_boutique RENAME INDEX idx_commande_boutique_foyer TO IDX_770224CE2B919A58');
        $this->addSql('ALTER TABLE commande_boutique_ligne RENAME INDEX idx_commande_boutique_ligne_cmd TO IDX_39DC774182EA2E54');
        $this->addSql('ALTER TABLE commande_boutique_ligne RENAME INDEX idx_commande_boutique_ligne_goodie TO IDX_39DC7741388135BB');
        $this->addSql('ALTER TABLE cours CHANGE capacite_max capacite_max INT DEFAULT 25 NOT NULL');
        $this->addSql('ALTER TABLE demande_fusion_foyer CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE used_at used_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_demande_fusion_source TO IDX_2EB4EC307DAAFA65');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_demande_fusion_target TO IDX_2EB4EC30FD18ED62');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_demande_fusion_demandeur TO IDX_2EB4EC3095A6EE59');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_demande_fusion_accepte TO IDX_2EB4EC30ECC92243');
        $this->addSql('ALTER TABLE inscription CHANGE last_pieces_reminder_sent_at last_pieces_reminder_sent_at DATETIME DEFAULT NULL, CHANGE last_payment_reminder_sent_at last_payment_reminder_sent_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE invitation_coparent RENAME INDEX idx_invitation_coparent_danseur TO IDX_EAE0FD4C5942A4C7');
        $this->addSql('ALTER TABLE invitation_coparent RENAME INDEX idx_invitation_coparent_accepted_by TO IDX_EAE0FD4C20F699D9');
        $this->addSql('ALTER TABLE ldc_document CHANGE uploaded_at uploaded_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE ldc_document RENAME INDEX idx_ldc_document_uploaded_by TO IDX_9CF06697A2B28FE8');
        $this->addSql('ALTER TABLE page_legale CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE paiement CHANGE last_reminder_sent_at last_reminder_sent_at DATETIME DEFAULT NULL, CHANGE date_declaration date_declaration DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation_costume RENAME INDEX idx_res_costume_foyer TO IDX_5C9B085E2B919A58');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account_activation_token CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE account_activation_token RENAME INDEX idx_d7523ebfa76ed395 TO IDX_AAT_USER');
        $this->addSql('ALTER TABLE account_activation_token RENAME INDEX uniq_d7523ebfb3bc57da TO UNIQ_AAT_TOKEN_HASH');
        $this->addSql('ALTER TABLE achat_goodie RENAME INDEX idx_de54df882b919a58 TO IDX_ACHAT_FOYER');
        $this->addSql('ALTER TABLE achat_goodie RENAME INDEX idx_de54df88388135bb TO IDX_ACHAT_GOODIE');
        $this->addSql('ALTER TABLE actualite CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commande_boutique CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE commande_boutique RENAME INDEX idx_770224ce2b919a58 TO IDX_COMMANDE_BOUTIQUE_FOYER');
        $this->addSql('ALTER TABLE commande_boutique RENAME INDEX idx_770224cea76ed395 TO IDX_COMMANDE_BOUTIQUE_USER');
        $this->addSql('ALTER TABLE commande_boutique_ligne RENAME INDEX idx_39dc774182ea2e54 TO IDX_COMMANDE_BOUTIQUE_LIGNE_CMD');
        $this->addSql('ALTER TABLE commande_boutique_ligne RENAME INDEX idx_39dc7741388135bb TO IDX_COMMANDE_BOUTIQUE_LIGNE_GOODIE');
        $this->addSql('ALTER TABLE cours CHANGE capacite_max capacite_max INT NOT NULL');
        $this->addSql('ALTER TABLE demande_fusion_foyer CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE used_at used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_2eb4ec30ecc92243 TO IDX_DEMANDE_FUSION_ACCEPTE');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_2eb4ec3095a6ee59 TO IDX_DEMANDE_FUSION_DEMANDEUR');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_2eb4ec307daafa65 TO IDX_DEMANDE_FUSION_SOURCE');
        $this->addSql('ALTER TABLE demande_fusion_foyer RENAME INDEX idx_2eb4ec30fd18ed62 TO IDX_DEMANDE_FUSION_TARGET');
        $this->addSql('ALTER TABLE inscription CHANGE last_pieces_reminder_sent_at last_pieces_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_payment_reminder_sent_at last_payment_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE invitation_coparent RENAME INDEX idx_eae0fd4c20f699d9 TO IDX_INVITATION_COPARENT_ACCEPTED_BY');
        $this->addSql('ALTER TABLE invitation_coparent RENAME INDEX idx_eae0fd4c5942a4c7 TO IDX_INVITATION_COPARENT_DANSEUR');
        $this->addSql('ALTER TABLE ldc_document CHANGE uploaded_at uploaded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ldc_document RENAME INDEX idx_9cf06697a2b28fe8 TO IDX_LDC_DOCUMENT_UPLOADED_BY');
        $this->addSql('ALTER TABLE page_legale CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE paiement CHANGE last_reminder_sent_at last_reminder_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_declaration date_declaration DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE reservation_costume RENAME INDEX idx_5c9b085e2b919a58 TO IDX_RES_COSTUME_FOYER');
    }
}
