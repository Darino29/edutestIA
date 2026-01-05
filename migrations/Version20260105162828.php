<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260105162828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make exam.teacher_id NOT NULL safely (drop FK, modify column, re-add FK)';
    }

    public function up(Schema $schema): void
    {
        // 1) Drop FK if exists
        $this->addSql("
            SET @fk_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND CONSTRAINT_NAME = 'FK_38BBA6C641807E1D'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            );
        ");
        $this->addSql("
            SET @sql := IF(@fk_exists = 1,
                'ALTER TABLE exam DROP FOREIGN KEY FK_38BBA6C641807E1D',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");

        // 2) Modify column (what Doctrine wanted to do)
        $this->addSql('ALTER TABLE exam CHANGE teacher_id teacher_id INT NOT NULL');

        // 3) Recreate FK if missing
        $this->addSql("
            SET @fk_exists2 := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND CONSTRAINT_NAME = 'FK_38BBA6C641807E1D'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            );
        ");
        $this->addSql("
            SET @sql2 := IF(@fk_exists2 = 0,
                'ALTER TABLE exam ADD CONSTRAINT FK_38BBA6C641807E1D FOREIGN KEY (teacher_id) REFERENCES `user` (id)',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt2 FROM @sql2");
        $this->addSql("EXECUTE stmt2");
        $this->addSql("DEALLOCATE PREPARE stmt2");
    }

    public function down(Schema $schema): void
    {
        // 1) Drop FK if exists
        $this->addSql("
            SET @fk_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND CONSTRAINT_NAME = 'FK_38BBA6C641807E1D'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            );
        ");
        $this->addSql("
            SET @sql := IF(@fk_exists = 1,
                'ALTER TABLE exam DROP FOREIGN KEY FK_38BBA6C641807E1D',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE dstmt FROM @sql");
        $this->addSql("EXECUTE dstmt");
        $this->addSql("DEALLOCATE PREPARE dstmt");

        // 2) Revert column
        $this->addSql('ALTER TABLE exam CHANGE teacher_id teacher_id INT DEFAULT NULL');

        // 3) Recreate FK
        $this->addSql("
            SET @sql2 := 'ALTER TABLE exam ADD CONSTRAINT FK_38BBA6C641807E1D FOREIGN KEY (teacher_id) REFERENCES `user` (id)'
        ");
        $this->addSql("PREPARE dstmt2 FROM @sql2");
        $this->addSql("EXECUTE dstmt2");
        $this->addSql("DEALLOCATE PREPARE dstmt2");
    }
}
