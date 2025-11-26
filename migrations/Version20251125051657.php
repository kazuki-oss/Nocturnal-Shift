<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125051657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD event_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE event ADD recurrence_end_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD day_of_week INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD day_of_month INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event DROP event_type');
        $this->addSql('ALTER TABLE event DROP recurrence_end_date');
        $this->addSql('ALTER TABLE event DROP day_of_week');
        $this->addSql('ALTER TABLE event DROP day_of_month');
    }
}
