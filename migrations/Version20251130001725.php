<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130001725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE channel (id UUID NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, server_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A2F98E471844E6B7 ON channel (server_id)');
        $this->addSql('ALTER TABLE channel ADD CONSTRAINT FK_A2F98E471844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel DROP CONSTRAINT FK_A2F98E471844E6B7');
        $this->addSql('DROP TABLE channel');
    }
}
