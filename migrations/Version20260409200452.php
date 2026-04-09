<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409200452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE farm (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, latidude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, area DOUBLE PRECISION NOT NULL, farm_type VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE field (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, area DOUBLE PRECISION NOT NULL, soil_type VARCHAR(255) NOT NULL, crop_type VARCHAR(255) NOT NULL, coordinates DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, farm_id_id INT NOT NULL, INDEX IDX_5BF5455834C1E106 (farm_id_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE field ADD CONSTRAINT FK_5BF5455834C1E106 FOREIGN KEY (farm_id_id) REFERENCES farm (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE field DROP FOREIGN KEY FK_5BF5455834C1E106');
        $this->addSql('DROP TABLE farm');
        $this->addSql('DROP TABLE field');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
