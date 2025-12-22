<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129010524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE server (id UUID NOT NULL, name VARCHAR(100) NOT NULL, owner_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5A6DD5F67E3C61F9 ON server (owner_id)');
        $this->addSql('CREATE TABLE server_member (id UUID NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, server_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_998C3BEA76ED395 ON server_member (user_id)');
        $this->addSql('CREATE INDEX IDX_998C3BE1844E6B7 ON server_member (server_id)');
        $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F67E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_member ADD CONSTRAINT FK_998C3BEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_member ADD CONSTRAINT FK_998C3BE1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE server DROP CONSTRAINT FK_5A6DD5F67E3C61F9');
        $this->addSql('ALTER TABLE server_member DROP CONSTRAINT FK_998C3BEA76ED395');
        $this->addSql('ALTER TABLE server_member DROP CONSTRAINT FK_998C3BE1844E6B7');
        $this->addSql('DROP TABLE server');
        $this->addSql('DROP TABLE server_member');
    }
}
