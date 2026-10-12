<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function space_pdo(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $c = space_config();
    if (!space_is_configured()) throw new RuntimeException('SPACE//AG ist noch nicht eingerichtet. Bitte install.php aufrufen.');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], (int)$c['db_port'], $c['db_name']);
    $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function space_schema_sql(): string {
    return <<<'SQL'
CREATE TABLE IF NOT EXISTS space_ag_state (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    state_json LONGTEXT NOT NULL,
    last_tick_at DOUBLE NOT NULL,
    tick_seconds DOUBLE NOT NULL DEFAULT 1.5,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}

function space_install_schema(PDO $pdo): void {
    $pdo->exec(space_schema_sql());
    require_once __DIR__.'/accounts.php';
    $pdo->exec(space_accounts_schema_sql());
}
