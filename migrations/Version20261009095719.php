<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009095719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE video_game_category (video_game_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_A672CAD716230A8 (video_game_id), INDEX IDX_A672CAD712469DE2 (category_id), PRIMARY KEY (video_game_id, category_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE video_game_category ADD CONSTRAINT FK_A672CAD716230A8 FOREIGN KEY (video_game_id) REFERENCES video_game (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_game_category ADD CONSTRAINT FK_A672CAD712469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE video_game_category DROP FOREIGN KEY FK_A672CAD716230A8');
        $this->addSql('ALTER TABLE video_game_category DROP FOREIGN KEY FK_A672CAD712469DE2');
        $this->addSql('DROP TABLE video_game_category');
    }
}
