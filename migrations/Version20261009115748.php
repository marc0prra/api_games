<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009115748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video_game ADD editor_id INT NOT NULL');
        $this->addSql('ALTER TABLE video_game ADD CONSTRAINT FK_24BC6C506995AC4C FOREIGN KEY (editor_id) REFERENCES editor (id)');
        $this->addSql('CREATE INDEX IDX_24BC6C506995AC4C ON video_game (editor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video_game DROP FOREIGN KEY FK_24BC6C506995AC4C');
        $this->addSql('DROP INDEX IDX_24BC6C506995AC4C ON video_game');
        $this->addSql('ALTER TABLE video_game DROP editor_id');
    }
}
