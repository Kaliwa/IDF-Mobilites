<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subscription_dossier and contract tables for the backoffice feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE subscription_dossier (
                id SERIAL NOT NULL,
                user_id INT NOT NULL,
                reviewed_by_id INT DEFAULT NULL,
                type VARCHAR(32) NOT NULL,
                document_type VARCHAR(40) NOT NULL,
                document_ref VARCHAR(160) DEFAULT NULL,
                ocr_data JSON NOT NULL,
                ocr_score DOUBLE PRECISION NOT NULL,
                ocr_flags JSON NOT NULL,
                status VARCHAR(16) NOT NULL,
                agent_note TEXT DEFAULT NULL,
                reviewed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('ALTER TABLE subscription_dossier ADD CONSTRAINT fk_dossier_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE subscription_dossier ADD CONSTRAINT fk_dossier_reviewed_by FOREIGN KEY (reviewed_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_dossier_user ON subscription_dossier (user_id)');
        $this->addSql('CREATE INDEX idx_dossier_status ON subscription_dossier (status)');

        $this->addSql(<<<'SQL'
            CREATE TABLE contract (
                id SERIAL NOT NULL,
                user_id INT NOT NULL,
                line_id INT DEFAULT NULL,
                payer_name VARCHAR(180) DEFAULT NULL,
                payer_email VARCHAR(180) DEFAULT NULL,
                status VARCHAR(16) NOT NULL,
                suspended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                suspended_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                suspension_reason TEXT DEFAULT NULL,
                cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT fk_contract_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE contract ADD CONSTRAINT fk_contract_line FOREIGN KEY (line_id) REFERENCES transit_line (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_contract_user ON contract (user_id)');
        $this->addSql('CREATE INDEX idx_contract_status ON contract (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription_dossier DROP CONSTRAINT fk_dossier_user');
        $this->addSql('ALTER TABLE subscription_dossier DROP CONSTRAINT fk_dossier_reviewed_by');
        $this->addSql('DROP TABLE subscription_dossier');
        $this->addSql('ALTER TABLE contract DROP CONSTRAINT fk_contract_user');
        $this->addSql('ALTER TABLE contract DROP CONSTRAINT fk_contract_line');
        $this->addSql('DROP TABLE contract');
    }
}
