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
}