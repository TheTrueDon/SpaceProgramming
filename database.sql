CREATE TABLE IF NOT EXISTS space_ag_state (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    state_json LONGTEXT NOT NULL,
    last_tick_at DOUBLE NOT NULL,
    tick_seconds DOUBLE NOT NULL DEFAULT 1.5,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
