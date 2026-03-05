<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212014041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Intentionally empty — original CREATE TABLE migration was deleted.
        // Operations on channel_override, member_role, role_permission, user_role
        // reference tables not yet created at this point.
        // Operations on channel and server_member (ON DELETE CASCADE)
        // are handled correctly by Version20260304114927.
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty — symmetric with up().
    }
}
