<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260105161120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add proctoring_report and is_flagged to assignment; add teacher_id FK to exam (safe if already exists)';
    }

    public function up(Schema $schema): void
    {
        // ---------- assignment.proctoring_report (if not exists)
        $this->addSql("
            SET @exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'assignment'
                  AND COLUMN_NAME = 'proctoring_report'
            );
        ");
        $this->addSql("
            SET @sql := IF(@exists = 0,
                'ALTER TABLE assignment ADD proctoring_report JSON DEFAULT NULL',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");

        // ---------- assignment.is_flagged (if not exists)
        $this->addSql("
            SET @exists2 := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'assignment'
                  AND COLUMN_NAME = 'is_flagged'
            );
        ");
        $this->addSql("
            SET @sql2 := IF(@exists2 = 0,
                'ALTER TABLE assignment ADD is_flagged TINYINT(1) NOT NULL DEFAULT 0',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt2 FROM @sql2");
        $this->addSql("EXECUTE stmt2");
        $this->addSql("DEALLOCATE PREPARE stmt2");

        // ---------- exam.teacher_id (if not exists)
        $this->addSql("
            SET @col_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND COLUMN_NAME = 'teacher_id'
            );
        ");
        $this->addSql("
            SET @sql3 := IF(@col_exists = 0,
                'ALTER TABLE exam ADD teacher_id INT NOT NULL',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt3 FROM @sql3");
        $this->addSql("EXECUTE stmt3");
        $this->addSql("DEALLOCATE PREPARE stmt3");

        // ---------- FK exam.teacher_id -> user.id (if not exists)
        // Check constraint existence by name
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
            SET @sql4 := IF(@fk_exists = 0,
                'ALTER TABLE exam ADD CONSTRAINT FK_38BBA6C641807E1D FOREIGN KEY (teacher_id) REFERENCES `user` (id)',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt4 FROM @sql4");
        $this->addSql("EXECUTE stmt4");
        $this->addSql("DEALLOCATE PREPARE stmt4");

        // ---------- INDEX on exam.teacher_id (if not exists)
        $this->addSql("
            SET @idx_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND INDEX_NAME = 'IDX_38BBA6C641807E1D'
            );
        ");
        $this->addSql("
            SET @sql5 := IF(@idx_exists = 0,
                'CREATE INDEX IDX_38BBA6C641807E1D ON exam (teacher_id)',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE stmt5 FROM @sql5");
        $this->addSql("EXECUTE stmt5");
        $this->addSql("DEALLOCATE PREPARE stmt5");
    }

    public function down(Schema $schema): void
    {
        // Index (if exists)
        $this->addSql("
            SET @idx_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND INDEX_NAME = 'IDX_38BBA6C641807E1D'
            );
        ");
        $this->addSql("
            SET @sql1 := IF(@idx_exists = 1,
                'DROP INDEX IDX_38BBA6C641807E1D ON exam',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE dstmt1 FROM @sql1");
        $this->addSql("EXECUTE dstmt1");
        $this->addSql("DEALLOCATE PREPARE dstmt1");

        // FK (if exists)
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
            SET @sql2 := IF(@fk_exists = 1,
                'ALTER TABLE exam DROP FOREIGN KEY FK_38BBA6C641807E1D',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE dstmt2 FROM @sql2");
        $this->addSql("EXECUTE dstmt2");
        $this->addSql("DEALLOCATE PREPARE dstmt2");

        // teacher_id column (if exists)
        $this->addSql("
            SET @col_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'exam'
                  AND COLUMN_NAME = 'teacher_id'
            );
        ");
        $this->addSql("
            SET @sql3 := IF(@col_exists = 1,
                'ALTER TABLE exam DROP teacher_id',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE dstmt3 FROM @sql3");
        $this->addSql("EXECUTE dstmt3");
        $this->addSql("DEALLOCATE PREPARE dstmt3");

        // assignment columns (if exist)
        $this->addSql("
            SET @p_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'assignment'
                  AND COLUMN_NAME = 'proctoring_report'
            );
        ");
        $this->addSql("
            SET @sql4 := IF(@p_exists = 1,
                'ALTER TABLE assignment DROP proctoring_report',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE dstmt4 FROM @sql4");
        $this->addSql("EXECUTE dstmt4");
        $this->addSql("DEALLOCATE PREPARE dstmt4");

        $this->addSql("
            SET @f_exists := (
                SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'assignment'
                  AND COLUMN_NAME = 'is_flagged'
            );
        ");
        $this->addSql("
            SET @sql5 := IF(@f_exists = 1,
                'ALTER TABLE assignment DROP is_flagged',
                'SELECT 1'
            );
        ");
        $this->addSql("PREPARE dstmt5 FROM @sql5");
        $this->addSql("EXECUTE dstmt5");
        $this->addSql("DEALLOCATE PREPARE dstmt5");
    }
}
