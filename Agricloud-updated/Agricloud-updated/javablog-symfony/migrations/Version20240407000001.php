<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240407000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create posts, comments, and users tables for JavaBlog';
    }

    public function up(Schema $schema): void
    {
        // Users table
        $this->addSql('CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT NOT NULL,
            username VARCHAR(180) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB');

        // Posts table
        $this->addSql('CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            author VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB');

        // Comments table
        $this->addSql('CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            author VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_5F9E962A4B89032C (post_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_5F9E962A4B89032C FOREIGN KEY (post_id)
                REFERENCES posts (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS comments');
        $this->addSql('DROP TABLE IF EXISTS posts');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}
