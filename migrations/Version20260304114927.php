<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304114927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE channel_override (id UUID NOT NULL, allow BOOLEAN NOT NULL, channel_id UUID NOT NULL, role_id UUID DEFAULT NULL, server_member_id UUID DEFAULT NULL, permission_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_F5B3754C72F5A1AA ON channel_override (channel_id)');
        $this->addSql('CREATE INDEX IDX_F5B3754CD60322AC ON channel_override (role_id)');
        $this->addSql('CREATE INDEX IDX_F5B3754CB78289D8 ON channel_override (server_member_id)');
        $this->addSql('CREATE INDEX IDX_F5B3754CFED90CCA ON channel_override (permission_id)');
        $this->addSql('CREATE TABLE member_role (id UUID NOT NULL, server_member_id UUID NOT NULL, role_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_ABE1A636B78289D8 ON member_role (server_member_id)');
        $this->addSql('CREATE INDEX IDX_ABE1A636D60322AC ON member_role (role_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_member_role ON member_role (server_member_id, role_id)');
        $this->addSql('CREATE TABLE permission (id UUID NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE role_permission (id UUID NOT NULL, role_id UUID NOT NULL, permission_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6F7DF886D60322AC ON role_permission (role_id)');
        $this->addSql('CREATE INDEX IDX_6F7DF886FED90CCA ON role_permission (permission_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_role_permission ON role_permission (role_id, permission_id)');
        $this->addSql('CREATE TABLE user_role (id UUID NOT NULL, name VARCHAR(30) NOT NULL, position INT NOT NULL, server_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2DE8C6A31844E6B7 ON user_role (server_id)');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754C72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754CD60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754CB78289D8 FOREIGN KEY (server_member_id) REFERENCES server_member (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754CFED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE member_role ADD CONSTRAINT FK_ABE1A636B78289D8 FOREIGN KEY (server_member_id) REFERENCES server_member (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE member_role ADD CONSTRAINT FK_ABE1A636D60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A31844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel DROP CONSTRAINT fk_a2f98e471844e6b7');
        $this->addSql('ALTER TABLE channel ALTER name TYPE VARCHAR(25)');
        $this->addSql('ALTER TABLE channel ADD CONSTRAINT FK_A2F98E471844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT fk_b6bd307f72f5a1aa');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_member DROP CONSTRAINT fk_998c3bea76ed395');
        $this->addSql('ALTER TABLE server_member DROP CONSTRAINT fk_998c3be1844e6b7');
        $this->addSql('ALTER TABLE server_member ADD CONSTRAINT FK_998C3BEA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE server_member ADD CONSTRAINT FK_998C3BE1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('DROP INDEX idx_75ea56e016ba31db');
        $this->addSql('DROP INDEX idx_75ea56e0e3bd61ce');
        $this->addSql('DROP INDEX idx_75ea56e0fb7336f0');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754C72F5A1AA');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754CD60322AC');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754CB78289D8');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754CFED90CCA');
        $this->addSql('ALTER TABLE member_role DROP CONSTRAINT FK_ABE1A636B78289D8');
        $this->addSql('ALTER TABLE member_role DROP CONSTRAINT FK_ABE1A636D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT FK_6F7DF886FED90CCA');
        $this->addSql('ALTER TABLE user_role DROP CONSTRAINT FK_2DE8C6A31844E6B7');
        $this->addSql('DROP TABLE channel_override');
        $this->addSql('DROP TABLE member_role');
        $this->addSql('DROP TABLE permission');
        $this->addSql('DROP TABLE role_permission');
        $this->addSql('DROP TABLE user_role');
        $this->addSql('ALTER TABLE channel DROP CONSTRAINT FK_A2F98E471844E6B7');
        $this->addSql('ALTER TABLE channel ALTER name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE channel ADD CONSTRAINT fk_a2f98e471844e6b7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE message DROP CONSTRAINT FK_B6BD307F72F5A1AA');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT fk_b6bd307f72f5a1aa FOREIGN KEY (channel_id) REFERENCES channel (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750');
        $this->addSql('CREATE INDEX idx_75ea56e016ba31db ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE server_member DROP CONSTRAINT FK_998C3BEA76ED395');
        $this->addSql('ALTER TABLE server_member DROP CONSTRAINT FK_998C3BE1844E6B7');
        $this->addSql('ALTER TABLE server_member ADD CONSTRAINT fk_998c3bea76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE server_member ADD CONSTRAINT fk_998c3be1844e6b7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
