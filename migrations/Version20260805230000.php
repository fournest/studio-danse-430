<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Activation compte adhérents importés : isActivated, prénom/nom, jetons d\'activation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD is_activated TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE `user` ADD prenom VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD nom VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE password password VARCHAR(255) DEFAULT NULL');

        $this->addSql('CREATE TABLE account_activation_token (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_AAT_TOKEN_HASH (token_hash),
            INDEX IDX_AAT_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_activation_token ADD CONSTRAINT FK_AAT_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('UPDATE `user` SET is_activated = 1 WHERE password IS NOT NULL AND password != \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_activation_token DROP FOREIGN KEY FK_AAT_USER');
        $this->addSql('DROP TABLE account_activation_token');
        $this->addSql('ALTER TABLE `user` DROP is_activated, DROP prenom, DROP nom');
        $this->addSql('ALTER TABLE `user` CHANGE password password VARCHAR(255) NOT NULL');
    }
}
