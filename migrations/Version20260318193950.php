<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260318193950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE channel_read_state (id UUID NOT NULL, channel_id UUID NOT NULL, owner_id UUID NOT NULL, last_read_message_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_4ADAFEB972F5A1AA ON channel_read_state (channel_id)');
        $this->addSql('CREATE INDEX IDX_4ADAFEB97E3C61F9 ON channel_read_state (owner_id)');
        $this->addSql('CREATE INDEX IDX_4ADAFEB9384BCFBF ON channel_read_state (last_read_message_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_channel_read_state ON channel_read_state (owner_id, channel_id)');
        $this->addSql('ALTER TABLE channel_read_state ADD CONSTRAINT FK_4ADAFEB972F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_read_state ADD CONSTRAINT FK_4ADAFEB97E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_read_state ADD CONSTRAINT FK_4ADAFEB9384BCFBF FOREIGN KEY (last_read_message_id) REFERENCES message (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_read_state DROP CONSTRAINT FK_4ADAFEB972F5A1AA');
        $this->addSql('ALTER TABLE channel_read_state DROP CONSTRAINT FK_4ADAFEB97E3C61F9');
        $this->addSql('ALTER TABLE channel_read_state DROP CONSTRAINT FK_4ADAFEB9384BCFBF');
        $this->addSql('DROP TABLE channel_read_state');
    }
}
