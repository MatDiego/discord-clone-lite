<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312001527 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE friend_invitation (
              id UUID NOT NULL,
              status VARCHAR(20) NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              sender_id UUID NOT NULL,
              recipient_id UUID NOT NULL,
              PRIMARY KEY (id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_5F5A95A6F624B39D ON friend_invitation (sender_id)');
        $this->addSql('CREATE INDEX IDX_5F5A95A6E92F8F78 ON friend_invitation (recipient_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              friend_invitation
            ADD
              CONSTRAINT FK_5F5A95A6F624B39D FOREIGN KEY (sender_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE
              friend_invitation
            ADD
              CONSTRAINT FK_5F5A95A6E92F8F78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE
        SQL);
        $this->addSql('ALTER TABLE notification ADD friend_invitation_id UUID DEFAULT NULL');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              notification
            ADD
              CONSTRAINT FK_BF5476CA3C3C2208 FOREIGN KEY (friend_invitation_id) REFERENCES friend_invitation (id) ON DELETE
            SET
              NULL NOT DEFERRABLE
        SQL);
        $this->addSql('CREATE INDEX IDX_BF5476CA3C3C2208 ON notification (friend_invitation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE friend_invitation DROP CONSTRAINT FK_5F5A95A6F624B39D');
        $this->addSql('ALTER TABLE friend_invitation DROP CONSTRAINT FK_5F5A95A6E92F8F78');
        $this->addSql('DROP TABLE friend_invitation');
        $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CA3C3C2208');
        $this->addSql('DROP INDEX IDX_BF5476CA3C3C2208');
        $this->addSql('ALTER TABLE notification DROP friend_invitation_id');
    }
}
