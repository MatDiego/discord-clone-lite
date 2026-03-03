<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206043607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT fk_f5b3754cfed90cca');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT fk_f5b3754cd60322ac');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754CFED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754CD60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754C72F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT FK_F5B3754CB78289D8 FOREIGN KEY (server_member_id) REFERENCES server_member (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE member_role DROP CONSTRAINT fk_7847c57fd60322ac');
        $this->addSql('ALTER TABLE member_role ADD CONSTRAINT FK_ABE1A636B78289D8 FOREIGN KEY (server_member_id) REFERENCES server_member (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE member_role ADD CONSTRAINT FK_ABE1A636D60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT fk_6f7df886fed90cca');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT fk_6f7df886d60322ac');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754CD60322AC');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754CFED90CCA');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754C72F5A1AA');
        $this->addSql('ALTER TABLE channel_override DROP CONSTRAINT FK_F5B3754CB78289D8');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT fk_f5b3754cd60322ac FOREIGN KEY (role_id) REFERENCES user_role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE channel_override ADD CONSTRAINT fk_f5b3754cfed90cca FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE member_role DROP CONSTRAINT FK_ABE1A636B78289D8');
        $this->addSql('ALTER TABLE member_role DROP CONSTRAINT FK_ABE1A636D60322AC');
        $this->addSql('ALTER TABLE member_role ADD CONSTRAINT fk_7847c57fd60322ac FOREIGN KEY (role_id) REFERENCES user_role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP CONSTRAINT FK_6F7DF886FED90CCA');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT fk_6f7df886d60322ac FOREIGN KEY (role_id) REFERENCES user_role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT fk_6f7df886fed90cca FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
