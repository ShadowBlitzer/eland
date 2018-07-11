<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * schema "migration" needs to be created manually beforehand.
 */

final class Version20180711143155 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA e');
        $this->addSql('CREATE SCHEMA c');
        $this->addSql('CREATE SEQUENCE e.event_sequence_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE c.queue_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE c.log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE e.event (sequence_id BIGINT NOT NULL, agg_id UUID NOT NULL, agg_version INT NOT NULL, data JSONB NOT NULL, PRIMARY KEY(sequence_id))');
        $this->addSql('CREATE INDEX agg_idx ON e.event (agg_id)');
        $this->addSql('CREATE UNIQUE INDEX agg_version_unique_idx ON e.event (agg_id, agg_version)');
        $this->addSql('COMMENT ON COLUMN e.event.data IS \'(DC2Type:json_array)\'');
        $this->addSql('CREATE TABLE c.agg (id UUID NOT NULL, agg_version INT NOT NULL, system_id UUID DEFAULT NULL, agg_type VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, data JSONB NOT NULL, meta JSONB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX agg_type_system_idx ON c.agg (agg_type, system_id)');
        $this->addSql('COMMENT ON COLUMN c.agg.data IS \'(DC2Type:json_array)\'');
        $this->addSql('COMMENT ON COLUMN c.agg.meta IS \'(DC2Type:json_array)\'');
        $this->addSql('CREATE TABLE c.cache (id VARCHAR(255) NOT NULL, data JSONB NOT NULL, ts TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN c.cache.data IS \'(DC2Type:json_array)\'');
        $this->addSql('CREATE TABLE c.queue (id BIGINT NOT NULL, topic VARCHAR(255) NOT NULL, ts TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, priority INT DEFAULT 0 NOT NULL, data JSONB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX topic_idx ON c.queue (topic)');
        $this->addSql('COMMENT ON COLUMN c.queue.data IS \'(DC2Type:json_array)\'');
        $this->addSql('CREATE TABLE c.log (id BIGINT NOT NULL, ts TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, system_id UUID DEFAULT NULL, data JSONB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX system_idx ON c.log (system_id)');
        $this->addSql('COMMENT ON COLUMN c.log.data IS \'(DC2Type:json_array)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP SEQUENCE e.event_sequence_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE c.queue_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE c.log_id_seq CASCADE');
        $this->addSql('DROP TABLE e.event');
        $this->addSql('DROP TABLE c.agg');
        $this->addSql('DROP TABLE c.cache');
        $this->addSql('DROP TABLE c.queue');
        $this->addSql('DROP TABLE c.log');
    }
}
