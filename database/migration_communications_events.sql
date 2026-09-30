USE fgck_joyland;

CREATE TABLE IF NOT EXISTS announcements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('announcement','prayer','pastoral','urgent','reminder') NOT NULL DEFAULT 'announcement',
    priority ENUM('normal','important','urgent') NOT NULL DEFAULT 'normal',
    audience ENUM('all','appointment_members') NOT NULL DEFAULT 'all',
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_announcement_status(status,published_at),
    KEY idx_announcement_user(user_id),
    CONSTRAINT fk_announcement_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS announcement_reads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id BIGINT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_announcement_read(announcement_id,member_id),
    KEY idx_announcement_member(member_id,read_at),
    CONSTRAINT fk_ar_announcement FOREIGN KEY(announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    CONSTRAINT fk_ar_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS church_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    category ENUM('worship','bible_study','prayer','youth','outreach','conference','fellowship','special_service','other') NOT NULL DEFAULT 'other',
    event_date DATE NOT NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    venue VARCHAR(180) NULL,
    registration_required TINYINT(1) NOT NULL DEFAULT 0,
    registration_deadline DATETIME NULL,
    status ENUM('draft','published','cancelled','completed') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_event_date(event_date,status),
    KEY idx_event_category(category),
    CONSTRAINT fk_event_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event_rsvps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    status ENUM('going','not_going','maybe') NOT NULL DEFAULT 'going',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_event_member(event_id,member_id),
    KEY idx_rsvp_event(event_id,status),
    CONSTRAINT fk_rsvp_event FOREIGN KEY(event_id) REFERENCES church_events(id) ON DELETE CASCADE,
    CONSTRAINT fk_rsvp_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;
