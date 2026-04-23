<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create farm_notifications table for farm approval and rejection alerts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE farm_notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            farm_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX IDX_3D37A4B2A76ED395 (user_id),
            INDEX IDX_3D37A4B25A4D57AF (farm_id),
            INDEX idx_farm_notification_read (user_id, is_read),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE farm_notifications ADD CONSTRAINT FK_3D37A4B2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE farm_notifications ADD CONSTRAINT FK_3D37A4B25A4D57AF FOREIGN KEY (farm_id) REFERENCES farms (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE farm_notifications');
    }
}
