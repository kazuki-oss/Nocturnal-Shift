<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251126041020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD is_all_day BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE tenant ADD business_hours JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE tenant ADD business_days JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP is_all_day');
        $this->addSql('ALTER TABLE tenant DROP business_hours');
        $this->addSql('ALTER TABLE tenant DROP business_days');
    }
}
