USE fgck_joyland;

ALTER TABLE users
    ADD COLUMN reset_code_hash VARCHAR(255) NULL AFTER password_hash,
    ADD COLUMN reset_code_expires_at DATETIME NULL AFTER reset_code_hash,
    ADD COLUMN reset_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER reset_code_expires_at;