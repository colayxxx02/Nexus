CREATE TABLE IF NOT EXISTS app_meta (
    meta_key VARCHAR(100) NOT NULL PRIMARY KEY,
    meta_value TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO app_meta (meta_key, meta_value)
VALUES ('app_name', 'NEXUS');
