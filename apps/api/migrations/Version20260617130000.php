<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add primRef to transit_line and notifiedDisruptionIds to line_subscription for PRIM integration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transit_line ADD prim_ref VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE line_subscription ADD notified_disruption_ids JSON NOT NULL DEFAULT \'[]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transit_line DROP COLUMN prim_ref');
        $this->addSql('ALTER TABLE line_subscription DROP COLUMN notified_disruption_ids');
    }
}
