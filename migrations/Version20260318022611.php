<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260318022611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE server_ban (id UUID NOT NULL, banned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id UUID NOT NULL, server_id UUID NOT NULL, banned_by_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5C37DABFA76ED395 ON server_ban (user_id)');
        $this->addSql('CREATE INDEX IDX_5C37DABF1844E6B7 ON server_ban (server_id)');
        $this->addSql('CREATE INDEX IDX_5C37DABF386B8E7 ON server_ban (banned_by_id)');
        $this->addSql('ALTER TABLE server_ban ADD CONSTRAINT FK_5C37DABFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_ban ADD CONSTRAINT FK_5C37DABF1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_ban ADD CONSTRAINT FK_5C37DABF386B8E7 FOREIGN KEY (banned_by_id) REFERENCES "user" (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE server_ban DROP CONSTRAINT FK_5C37DABFA76ED395');
        $this->addSql('ALTER TABLE server_ban DROP CONSTRAINT FK_5C37DABF1844E6B7');
        $this->addSql('ALTER TABLE server_ban DROP CONSTRAINT FK_5C37DABF386B8E7');
        $this->addSql('DROP TABLE server_ban');
    }
}
