<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create reviews table for product ratings and comments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reviews (
            id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            rating INT NOT NULL,
            comment LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_6970EB0FF347EFB (product_id),
            INDEX IDX_6970EB0A76ED395 (user_id),
            UNIQUE INDEX uniq_review_user_product (product_id, user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0FF347EFB FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reviews');
    }
}
