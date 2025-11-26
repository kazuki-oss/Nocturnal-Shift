<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251122100322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendances ADD last_modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE attendances ADD modification_reason TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE attendances ADD last_modified_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE attendances ADD CONSTRAINT FK_9C6B8FD4F703974A FOREIGN KEY (last_modified_by_id) REFERENCES "user" (id)');
        $this->addSql('CREATE INDEX IDX_9C6B8FD4F703974A ON attendances (last_modified_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendances DROP CONSTRAINT FK_9C6B8FD4F703974A');
        $this->addSql('DROP INDEX IDX_9C6B8FD4F703974A');
        $this->addSql('ALTER TABLE attendances DROP last_modified_at');
        $this->addSql('ALTER TABLE attendances DROP modification_reason');
        $this->addSql('ALTER TABLE attendances DROP last_modified_by_id');
    }
}
