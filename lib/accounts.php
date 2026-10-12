<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';

function space_accounts_schema_sql(): string {
    return <<<'SQL'
CREATE TABLE IF NOT EXISTS space_ag_team_accounts (
    team_id VARCHAR(24) NOT NULL PRIMARY KEY,
    universe_seed BIGINT NOT NULL,
    team_name VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    auth_token VARCHAR(160) NOT NULL,
    saved_code MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_space_team_name (universe_seed, team_name),
    KEY idx_space_team_seed (universe_seed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
}

function space_account_create(int $seed, string $teamId, string $name, string $password, string $token, string $code=''): void {
    if (strlen($password) < 4) throw new InvalidArgumentException('Das Team-Passwort muss mindestens 4 Zeichen lang sein.');
    $pdo=space_pdo();
    $stmt=$pdo->prepare('INSERT INTO space_ag_team_accounts (team_id,universe_seed,team_name,password_hash,auth_token,saved_code) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$teamId,$seed,$name,password_hash($password,PASSWORD_DEFAULT),$token,$code]);
}

function space_account_login(int $seed, string $name, string $password): ?array {
    $pdo=space_pdo();
    $stmt=$pdo->prepare('SELECT team_id,team_name,password_hash,auth_token,saved_code FROM space_ag_team_accounts WHERE universe_seed=? AND team_name=? LIMIT 1');
    $stmt->execute([$seed,$name]);
    $row=$stmt->fetch();
    if(!$row || !password_verify($password,(string)$row['password_hash'])) return null;
    return $row;
}

function space_account_verify(int $seed, string $teamId, string $token): ?array {
    $pdo=space_pdo();
    $stmt=$pdo->prepare('SELECT team_id,team_name,auth_token,saved_code FROM space_ag_team_accounts WHERE universe_seed=? AND team_id=? LIMIT 1');
    $stmt->execute([$seed,$teamId]);
    $row=$stmt->fetch();
    if(!$row || !hash_equals((string)$row['auth_token'],$token)) return null;
    return $row;
}

function space_account_save_code(int $seed, string $teamId, string $token, string $code): bool {
    if(strlen($code)>250000) throw new InvalidArgumentException('Der Programmcode ist zu groß.');
    $pdo=space_pdo();
    $stmt=$pdo->prepare('UPDATE space_ag_team_accounts SET saved_code=? WHERE universe_seed=? AND team_id=? AND auth_token=?');
    $stmt->execute([$code,$seed,$teamId,$token]);
    return $stmt->rowCount()>=0;
}

function space_accounts_clear(): void {
    space_pdo()->exec('DELETE FROM space_ag_team_accounts');
}
