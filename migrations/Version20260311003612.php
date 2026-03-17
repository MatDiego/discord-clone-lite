<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311003612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification (id UUID NOT NULL, type VARCHAR(50) NOT NULL, server_name VARCHAR(100) DEFAULT NULL, is_read BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, recipient_id UUID NOT NULL, invitation_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_BF5476CAE92F8F78 ON notification (recipient_id)');
        $this->addSql('CREATE INDEX IDX_BF5476CAA35D7AF0 ON notification (invitation_id)');
        $this->addSql('CREATE TABLE server_invitation (id UUID NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, server_id UUID NOT NULL, sender_id UUID NOT NULL, recipient_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_23D2F1051844E6B7 ON server_invitation (server_id)');
        $this->addSql('CREATE INDEX IDX_23D2F105F624B39D ON server_invitation (sender_id)');
        $this->addSql('CREATE INDEX IDX_23D2F105E92F8F78 ON server_invitation (recipient_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES server_invitation (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_invitation ADD CONSTRAINT FK_23D2F1051844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_invitation ADD CONSTRAINT FK_23D2F105F624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_invitation ADD CONSTRAINT FK_23D2F105E92F8F78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_read_state DROP CONSTRAINT fk_4adafeb972f5a1aa');
        $this->addSql('ALTER TABLE channel_read_state DROP CONSTRAINT fk_4adafeb9384bcfbf');
        $this->addSql('ALTER TABLE channel_read_state DROP CONSTRAINT fk_4adafeb97e3c61f9');
        $this->addSql('DROP TABLE channel_read_state');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE channel_read_state (id UUID NOT NULL, channel_id UUID NOT NULL, owner_id UUID NOT NULL, last_read_message_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_4adafeb97e3c61f9 ON channel_read_state (owner_id)');
        $this->addSql('CREATE INDEX idx_4adafeb972f5a1aa ON channel_read_state (channel_id)');
        $this->addSql('CREATE INDEX idx_4adafeb9384bcfbf ON channel_read_state (last_read_message_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_channel_read_state ON channel_read_state (owner_id, channel_id)');
        $this->addSql('ALTER TABLE channel_read_state ADD CONSTRAINT fk_4adafeb972f5a1aa FOREIGN KEY (channel_id) REFERENCES channel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE channel_read_state ADD CONSTRAINT fk_4adafeb9384bcfbf FOREIGN KEY (last_read_message_id) REFERENCES message (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE channel_read_state ADD CONSTRAINT fk_4adafeb97e3c61f9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAA35D7AF0');
        $this->addSql('ALTER TABLE server_invitation DROP CONSTRAINT FK_23D2F1051844E6B7');
        $this->addSql('ALTER TABLE server_invitation DROP CONSTRAINT FK_23D2F105F624B39D');
        $this->addSql('ALTER TABLE server_invitation DROP CONSTRAINT FK_23D2F105E92F8F78');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE server_invitation');
    }
}
