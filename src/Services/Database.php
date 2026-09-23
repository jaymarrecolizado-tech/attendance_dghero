<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;
    private static bool $migrationsApplied = false;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $name = getenv('DB_NAME') ?: 'event_db';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $charset = 'utf8mb4';

        try {
            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo = $pdo;
            self::maybeAutoMigrate($pdo);
            return $pdo;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                $dsn = "mysql:host={$host};charset={$charset}";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
                $pdo->exec("USE `{$name}`");
                self::$pdo = $pdo;
                self::maybeAutoMigrate($pdo);
                return $pdo;
            }
            throw $e;
        }
    }

    public static function migrate(): void
    {
        $pdo = self::pdo();
        self::runMigrations($pdo);
        self::$migrationsApplied = true;
    }

    private static function maybeAutoMigrate(PDO $pdo): void
    {
        if (self::$migrationsApplied) {
            return;
        }
        if (self::shouldAutoMigrate()) {
            self::runMigrations($pdo);
            self::$migrationsApplied = true;
        }
    }

    private static function shouldAutoMigrate(): bool
    {
        $value = getenv('DB_AUTO_MIGRATE');
        if ($value === false) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private static function runMigrations(PDO $pdo): void
    {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR;
        // 001_init
        if (!self::tableExists($pdo, 'participants') || !self::tableExists($pdo, 'attendance') || !self::tableExists($pdo, 'admins')) {
            self::executeSqlFile($pdo, $base . '001_init.sql');
        }
        // 002_action_logs
        if (!self::tableExists($pdo, 'action_logs')) {
            self::executeSqlFile($pdo, $base . '002_action_logs.sql');
        }
        // 003_events (includes index)
        if (!self::tableExists($pdo, 'events')) {
            self::executeSqlFile($pdo, $base . '003_events.sql');
        } else if (!self::indexExists($pdo, 'attendance', 'idx_attendance_pid_date')) {
            // Apply only the index addition if missing
            $pdo->exec('ALTER TABLE attendance ADD INDEX idx_attendance_pid_date (participant_id, attendance_date)');
        }
        // 004_report_templates
        if (!self::tableExists($pdo, 'report_templates')) {
            self::executeSqlFile($pdo, $base . '004_report_templates.sql');
        }
        // 005_performance_security
        if (!self::tableExists($pdo, 'rate_limits')) {
            self::executeSqlFile($pdo, $base . '005_performance_security.sql');
        } else {
            if (!self::indexExists($pdo, 'participants', 'idx_participants_agency')) {
                $pdo->exec('ALTER TABLE participants ADD INDEX idx_participants_agency (agency(100))');
            }
            if (!self::indexExists($pdo, 'attendance', 'idx_attendance_date')) {
                $pdo->exec('ALTER TABLE attendance ADD INDEX idx_attendance_date (attendance_date)');
            }
        }
        // 006_attendance_status
        if (self::tableExists($pdo, 'attendance') && !self::columnExists($pdo, 'attendance', 'status')) {
            self::executeSqlFile($pdo, $base . '006_attendance_status.sql');
        }
        // 007_rbac_roles
        if (self::tableExists($pdo, 'admins') && !self::columnExists($pdo, 'admins', 'role')) {
            self::executeSqlFile($pdo, $base . '007_rbac_roles.sql');
        }
        // 008_participant_vip
        if (self::tableExists($pdo, 'participants') && !self::columnExists($pdo, 'participants', 'is_vip')) {
            self::executeSqlFile($pdo, $base . '008_participant_vip.sql');
        }
        // 009_multi_event
        if (self::tableExists($pdo, 'events') && (!self::tableExists($pdo, 'event_assignments') || !self::columnExists($pdo, 'events', 'slug') || !self::columnExists($pdo, 'participants', 'event_id'))) {
            self::executeSqlFileTolerant($pdo, $base . '009_multi_event.sql');
        }
        // 010_multi_event_hardening (conditional: only when backfill left no gaps)
        if (self::tableExists($pdo, 'participants') && self::columnExists($pdo, 'participants', 'event_id')) {
            self::migrate010($pdo);
        }
        // 011_event_theme (add branding columns only if missing; no foreign keys)
        if (self::tableExists($pdo, 'events') && (
            !self::columnExists($pdo, 'events', 'theme_primary')
            || !self::columnExists($pdo, 'events', 'theme_accent')
            || !self::columnExists($pdo, 'events', 'welcome_text')
            || !self::columnExists($pdo, 'events', 'logo_path')
            || !self::columnExists($pdo, 'events', 'banner_path')
        )) {
            self::executeSqlFileTolerant($pdo, $base . '011_event_theme.sql');
        }
        // 012_event_gate (add theme_layout + enable the gate on Hack for Gov 5)
        if (self::tableExists($pdo, 'events') && !self::columnExists($pdo, 'events', 'theme_layout')) {
            self::executeSqlFileTolerant($pdo, $base . '012_event_gate.sql');
        }
        // 013_event_coa (per-event Certificate of Appearance settings)
        if (self::tableExists($pdo, 'events') && !self::columnExists($pdo, 'events', 'coa_enabled')) {
            self::executeSqlFileTolerant($pdo, $base . '013_event_coa.sql');
        }
        // 014_coa_monitor (batches, send rows, templates)
        if (!self::tableExists($pdo, 'coa_batches') || !self::tableExists($pdo, 'coa_sends') || !self::tableExists($pdo, 'coa_templates')) {
            self::executeSqlFileTolerant($pdo, $base . '014_coa_monitor.sql');
        }
        // 015_coa_facility. Not gated on coa_templates: Events queries
        // coa_signatories even when the template tables were never created.
        self::ensureCoaFacility($pdo);
    }

    /**
     * Create the signatory library and the columns Events / Certificates read.
     * Safe to call on every request. Missing parent tables are skipped.
     */
    public static function ensureCoaFacility(PDO $pdo): void
    {
        try {
            if (!self::tableExists($pdo, 'coa_signatories')) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS coa_signatories (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(120) NOT NULL,
                    title VARCHAR(120) NULL,
                    signature_path VARCHAR(255) NULL,
                    created_at DATETIME NULL,
                    updated_at DATETIME NULL,
                    PRIMARY KEY (id)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4");
            }
            // Older live table used full_name and had no updated_at.
            if (self::columnExists($pdo, 'coa_signatories', 'full_name') && !self::columnExists($pdo, 'coa_signatories', 'name')) {
                $pdo->exec('ALTER TABLE `coa_signatories` ADD COLUMN `name` VARCHAR(120) NULL');
                $pdo->exec('UPDATE `coa_signatories` SET `name` = `full_name` WHERE `name` IS NULL OR `name` = \'\'');
            }
            self::addColumnIfMissing($pdo, 'coa_signatories', 'name', 'VARCHAR(120) NULL');
            self::addColumnIfMissing($pdo, 'coa_signatories', 'updated_at', 'DATETIME NULL');
            self::addColumnIfMissing($pdo, 'events', 'coa_signatory_id', 'INT UNSIGNED NULL');
            self::addColumnIfMissing($pdo, 'coa_templates', 'signatory_id', 'INT UNSIGNED NULL');
            self::addColumnIfMissing($pdo, 'coa_sends', 'send_at', 'DATETIME NULL');
            self::addColumnIfMissing($pdo, 'coa_batches', 'template_id', 'INT UNSIGNED NULL');
            self::addColumnIfMissing($pdo, 'events', 'coa_date_from', 'DATE NULL');
            self::addColumnIfMissing($pdo, 'events', 'coa_date_to', 'DATE NULL');
            self::addColumnIfMissing($pdo, 'events', 'coa_issue_date', 'DATE NULL');
            self::addColumnIfMissing($pdo, 'coa_templates', 'date_from', 'DATE NULL');
            self::addColumnIfMissing($pdo, 'coa_templates', 'date_to', 'DATE NULL');
            self::addColumnIfMissing($pdo, 'coa_templates', 'issue_date', 'DATE NULL');
            $hadDefault = self::columnExists($pdo, 'coa_templates', 'is_default');
            self::addColumnIfMissing($pdo, 'coa_templates', 'is_default', 'TINYINT(1) NOT NULL DEFAULT 0');
            if (!$hadDefault && self::columnExists($pdo, 'coa_templates', 'is_default')) {
                $named = $pdo->query("SELECT id FROM coa_templates WHERE name = 'Certificate of Appearance' ORDER BY id ASC LIMIT 1")->fetchColumn();
                if ($named) {
                    $pdo->prepare('UPDATE coa_templates SET is_default = 1 WHERE id = ?')->execute([(int)$named]);
                }
            }
        } catch (\Throwable $e) {
            // Leave the caller to fall back to an empty list.
        }
    }

    private static function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!self::tableExists($pdo, $table) || self::columnExists($pdo, $table, $column)) {
            return;
        }
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }

    private static function migrate010(PDO $pdo): void
    {
        try {
            $nullInfo = $pdo->query("SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'participants' AND column_name = 'event_id'")->fetch();
            $nullable = $nullInfo && (($nullInfo['IS_NULLABLE'] ?? '') === 'YES');
            if ($nullable && (int)$pdo->query('SELECT COUNT(*) FROM participants WHERE event_id IS NULL')->fetchColumn() === 0) {
                try {
                    $pdo->exec('ALTER TABLE participants MODIFY event_id INT NOT NULL');
                } catch (\PDOException $e) {
                    // Leave nullable; per-event checks in the app remain the rule.
                }
            }
        } catch (\Throwable $e) {
            // Read-only fallback: keep 009 schema.
        }
        try {
            $allEnforce = (int)$pdo->query('SELECT COUNT(*) FROM events WHERE enforce_single_time_in <> 1')->fetchColumn() === 0;
            if ($allEnforce && !self::indexExists($pdo, 'attendance', 'uq_attendance_participant_event_date')) {
                try {
                    $pdo->exec('ALTER TABLE attendance ADD UNIQUE KEY uq_attendance_participant_event_date (participant_id, event_id, attendance_date)');
                } catch (\PDOException $e) {
                    // Duplicate legacy rows: app-level enforce check remains the rule.
                }
            }
        } catch (\Throwable $e) {
            // Read-only fallback: keep 009 schema.
        }
    }

    private static function executeSqlFileTolerant(PDO $pdo, string $path): void
    {
        if (!is_file($path)) return;
        $sql = (string)file_get_contents($path);
        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/', $sql))) as $stmt) {
            if ($stmt === '') continue;
            try {
                $pdo->exec($stmt);
            } catch (\PDOException $e) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'duplicate') || str_contains($msg, 'already exists') || str_contains($msg, 'exists')) {
                    continue;
                }
                throw $e;
            }
        }
    }

    private static function executeSqlFile(PDO $pdo, string $path): void
    {
        if (!is_file($path)) return;
        $sql = file_get_contents($path);
        foreach (array_filter(array_map('trim', preg_split('/;\s*\n/',$sql))) as $stmt) {
            if ($stmt !== '') $pdo->exec($stmt);
        }
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    private static function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?');
        $stmt->execute([$table, $index]);
        return (bool)$stmt->fetchColumn();
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }
}