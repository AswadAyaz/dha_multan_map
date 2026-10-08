<?php
/**
 * DHA Multan Property Map - Database Connection Configuration
 * Supports MySQL (production) with automatic SQLite fallback for standalone local preview.
 */

// Production MySQL Credentials (configure these for cPanel / live server)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'dha_multan_map');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $sqlitePath = __DIR__ . '/../database/dha_multan.sqlite';

    // 1. Try MySQL connection first if configured
    $useMysql = getenv('USE_MYSQL') === 'true' || (getenv('DB_NAME') !== false && getenv('DB_NAME') !== '');
    if ($useMysql) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // If MySQL fails and SQLite exists or can be initialized, fall back gracefully
            error_log("MySQL connection error: " . $e->getMessage() . ". Falling back to SQLite.");
        }
    }

    // 2. SQLite Connection (Local preview / standalone setup)
    try {
        $dsn = "sqlite:" . $sqlitePath;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, null, null, $options);
        // Enable foreign keys in SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode([
            'success' => false,
            'error' => 'Database connection failed: ' . $e->getMessage()
        ]));
    }
}
