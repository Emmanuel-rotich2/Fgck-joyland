USE fgck_joyland;

ALTER TABLE users
    ADD COLUMN phone VARCHAR(30) NULL AFTER email;

CREATE INDEX idx_users_phone ON users(phone);
