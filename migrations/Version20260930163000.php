<?php

// file generated with AI assistance: Claude Code - 2026-09-30 16:28:47 UTC

declare(strict_types=1);

namespace Dmstr\AppSettings\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dmstr_app_setting (settings sections of dmstr/symfony-app-settings-bundle)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE dmstr_app_setting (id UUID NOT NULL, section_key VARCHAR(100) NOT NULL, values_json JSONB NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_dmstr_app_setting_section_key ON dmstr_app_setting (section_key)');
        $this->addSql("COMMENT ON COLUMN dmstr_app_setting.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN dmstr_app_setting.created_at IS '(DC2Type:datetimetz_immutable)'");
        $this->addSql("COMMENT ON COLUMN dmstr_app_setting.updated_at IS '(DC2Type:datetimetz_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE dmstr_app_setting');
    }
}
