<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200813130056 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reply DROP CONSTRAINT fk_fda8c6e01f55203d');
        $this->addSql('DROP SEQUENCE topic_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE thread_id_seq INCREMENT BY 1 MINVALUE 1');
        $this->addSql('CREATE TABLE thread (id INT NOT NULL, board_id INT NOT NULL, user_id INT DEFAULT NULL, ts_created TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO  thread (id, board_id, user_id, ts_created) SELECT id, board_id, user_id, ts_created FROM topic');
        $this->addSql("SELECT SETVAL('thread_id_seq', (SELECT MAX(id) FROM topic))");
        $this->addSql('CREATE INDEX IDX_31204C83E7EC5785 ON thread (board_id)');
        $this->addSql('CREATE INDEX IDX_31204C83A76ED395 ON thread (user_id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C83E7EC5785 FOREIGN KEY (board_id) REFERENCES board (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C83A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP INDEX idx_fda8c6e01f55203d');
        $this->addSql('ALTER TABLE reply RENAME COLUMN topic_id TO thread_id');
        $this->addSql('ALTER TABLE reply ADD CONSTRAINT FK_FDA8C6E0E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FDA8C6E0E2904019 ON reply (thread_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reply DROP CONSTRAINT FK_FDA8C6E0E2904019');
        $this->addSql('DROP SEQUENCE thread_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE topic_id_seq INCREMENT BY 1 MINVALUE 1');
        $this->addSql('CREATE TABLE topic (id INT NOT NULL, board_id INT NOT NULL, user_id INT DEFAULT NULL, ts_created TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO  topic (id, board_id, user_id, ts_created) SELECT id, board_id, user_id, ts_created FROM thread');
        $this->addSql("SELECT SETVAL('topic_id_seq', (SELECT MAX(id) FROM thread))");
        $this->addSql('CREATE INDEX idx_9d40de1be7ec5785 ON topic (board_id)');
        $this->addSql('CREATE INDEX idx_9d40de1ba76ed395 ON topic (user_id)');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT fk_9d40de1be7ec5785 FOREIGN KEY (board_id) REFERENCES board (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT fk_9d40de1ba76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP TABLE thread');
        $this->addSql('DROP INDEX IDX_FDA8C6E0E2904019');
        $this->addSql('ALTER TABLE reply RENAME COLUMN thread_id TO topic_id');
        $this->addSql('ALTER TABLE reply ADD CONSTRAINT fk_fda8c6e01f55203d FOREIGN KEY (topic_id) REFERENCES topic (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_fda8c6e01f55203d ON reply (topic_id)');
    }
}
