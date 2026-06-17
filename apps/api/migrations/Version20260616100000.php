<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create journey table to store user routes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('journey');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('label', 'string', ['length' => 120]);
        $table->addColumn('origin_name', 'string', ['length' => 255]);
        $table->addColumn('origin_lat', 'float');
        $table->addColumn('origin_lng', 'float');
        $table->addColumn('destination_name', 'string', ['length' => 255]);
        $table->addColumn('destination_lat', 'float');
        $table->addColumn('destination_lng', 'float');
        $table->addColumn('lines', 'json', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime_immutable');
        $table->addColumn('updated_at', 'datetime_immutable');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'idx_journey_user');
        $table->addForeignKeyConstraint('user', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_journey_user');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('journey');
    }
}

