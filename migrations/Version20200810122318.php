<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200810122318 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE board_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE reply_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE topic_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE board (id INT NOT NULL, name VARCHAR(255) NOT NULL, ts_created TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reply (id INT NOT NULL, topic_id INT NOT NULL, content TEXT DEFAULT NULL, ts_created TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FDA8C6E01F55203D ON reply (topic_id)');
        $this->addSql('CREATE TABLE reply_reply (reply_source INT NOT NULL, reply_target INT NOT NULL, PRIMARY KEY(reply_source, reply_target))');
        $this->addSql('CREATE INDEX IDX_3242FDEC56F3D963 ON reply_reply (reply_source)');
        $this->addSql('CREATE INDEX IDX_3242FDEC4F1689EC ON reply_reply (reply_target)');
        $this->addSql('CREATE TABLE topic (id INT NOT NULL, ts_created TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE reply ADD CONSTRAINT FK_FDA8C6E01F55203D FOREIGN KEY (topic_id) REFERENCES topic (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reply_reply ADD CONSTRAINT FK_3242FDEC56F3D963 FOREIGN KEY (reply_source) REFERENCES reply (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reply_reply ADD CONSTRAINT FK_3242FDEC4F1689EC FOREIGN KEY (reply_target) REFERENCES reply (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reply_reply DROP CONSTRAINT FK_3242FDEC56F3D963');
        $this->addSql('ALTER TABLE reply_reply DROP CONSTRAINT FK_3242FDEC4F1689EC');
        $this->addSql('ALTER TABLE reply DROP CONSTRAINT FK_FDA8C6E01F55203D');
        $this->addSql('DROP SEQUENCE board_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE reply_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE topic_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP TABLE board');
        $this->addSql('DROP TABLE reply');
        $this->addSql('DROP TABLE reply_reply');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE "user"');
    }
}
