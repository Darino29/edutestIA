<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425175631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classe_teachers (classe_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D7C9D7588F5EA509 (classe_id), INDEX IDX_D7C9D758A76ED395 (user_id), PRIMARY KEY(classe_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE classe_teachers ADD CONSTRAINT FK_D7C9D7588F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_teachers ADD CONSTRAINT FK_D7C9D758A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classe_teachers DROP FOREIGN KEY FK_D7C9D7588F5EA509');
        $this->addSql('ALTER TABLE classe_teachers DROP FOREIGN KEY FK_D7C9D758A76ED395');
        $this->addSql('DROP TABLE classe_teachers');
    }
}
