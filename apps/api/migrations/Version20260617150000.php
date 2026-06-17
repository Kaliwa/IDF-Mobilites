<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create support_account_request table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE support_account_request (
                id SERIAL PRIMARY KEY,
                email VARCHAR(180) NOT NULL,
                hashed_password VARCHAR(255) NOT NULL,
                status VARCHAR(16) NOT NULL DEFAULT 'pending',
                reviewer_note TEXT DEFAULT NULL,
                reviewed_by_id INT DEFAULT NULL,
                reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                CONSTRAINT fk_sar_reviewed_by FOREIGN KEY (reviewed_by_id) REFERENCES "user"(id) ON DELETE SET NULL
            )
        SQL);

        $this->addSql('CREATE INDEX idx_sar_status ON support_account_request (status)');
        $this->addSql('CREATE INDEX idx_sar_email ON support_account_request (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS support_account_request');
    }
}
