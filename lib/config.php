<?php
declare(strict_types=1);

function space_config(): array {
    static $config = null;
    if ($config !== null) return $config;

    $defaults = [
        'db_host' => 'localhost',
        'db_port' => 3306,
        'db_name' => '',
        'db_user' => '',
        'db_pass' => '',
        'admin_password_hash' => '',
        'max_teams' => 30,
        'max_catchup_ticks' => 8,
        'default_tick_seconds' => 1.5,
        'session_name' => 'SPACEAGADMIN',
    ];
    $localFile = dirname(__DIR__) . '/config.local.php';
    $local = is_file($localFile) ? require $localFile : [];
    if (!is_array($local)) $local = [];
    $config = array_replace($defaults, $local);
    return $config;
}

function space_is_configured(): bool {
    $c = space_config();
    return $c['db_name'] !== '' && $c['db_user'] !== '' && $c['admin_password_hash'] !== '';
}
