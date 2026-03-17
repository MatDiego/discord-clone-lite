<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add actor_name column to notification table to preserve username when invitation is deleted';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification ADD actor_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP COLUMN actor_name');
    }
}
