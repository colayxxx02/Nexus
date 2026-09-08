CREATE TABLE IF NOT EXISTS app_meta (
    meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
    meta_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO app_meta (meta_key, meta_value)
VALUES ('app_name', 'NEXUS');

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    notes TEXT NULL,
    task_date DATE NOT NULL,
    start_time TIME NULL,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    category VARCHAR(80) NOT NULL DEFAULT 'General',
    status ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tasks_date_status (task_date, status),
    INDEX idx_tasks_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
