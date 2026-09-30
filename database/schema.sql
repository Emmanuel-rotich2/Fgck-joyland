CREATE DATABASE IF NOT EXISTS fgck_joyland CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fgck_joyland;
CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,username VARCHAR(80) NOT NULL UNIQUE,full_name VARCHAR(150) NOT NULL,email VARCHAR(150) NOT NULL UNIQUE,phone VARCHAR(30) NULL,password_hash VARCHAR(255) NOT NULL,reset_code_hash VARCHAR(255) NULL,reset_code_expires_at DATETIME NULL,reset_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,role ENUM('pastor','admin') NOT NULL DEFAULT 'pastor',status ENUM('active','inactive') NOT NULL DEFAULT 'active',last_login_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE members (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,membership_no VARCHAR(30) NOT NULL UNIQUE,full_name VARCHAR(150) NOT NULL,phone VARCHAR(30) NOT NULL UNIQUE,email VARCHAR(150) NOT NULL UNIQUE,gender VARCHAR(20),password_hash VARCHAR(255) NOT NULL,status ENUM('active','inactive') NOT NULL DEFAULT 'active',last_login_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE settings (setting_key VARCHAR(80) PRIMARY KEY,setting_value TEXT,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
INSERT INTO settings VALUES ('church_name','FGCK Joyland',NOW()),('appointment_day','Wednesday',NOW()),('opening_time','09:00',NOW()),('closing_time','15:00',NOW()),('slot_minutes','30',NOW()),('office_status','available',NOW()),('booking_notice','Please arrive a few minutes before your appointment.',NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
CREATE TABLE appointment_slots (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,appointment_date DATE NOT NULL,start_time TIME NOT NULL,end_time TIME NOT NULL,availability ENUM('available','unavailable') NOT NULL DEFAULT 'available',pastor_note VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,UNIQUE KEY uq_slot(appointment_date,start_time),KEY idx_date(appointment_date,availability)) ENGINE=InnoDB;
CREATE TABLE appointments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,appointment_no VARCHAR(40) NOT NULL UNIQUE,member_id INT UNSIGNED NOT NULL,slot_id BIGINT UNSIGNED NOT NULL,purpose VARCHAR(120) NOT NULL,notes TEXT,status ENUM('pending','confirmed','completed','cancelled','declined','no_show') NOT NULL DEFAULT 'pending',pastor_note TEXT,cancelled_by ENUM('member','pastor','admin') NULL,cancelled_at DATETIME NULL,confirmed_at DATETIME NULL,completed_at DATETIME NULL,adjusted_start_time TIME NULL,adjusted_end_time TIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,KEY idx_slot_status(slot_id,status),KEY idx_member(member_id),KEY idx_member_status(member_id,status),CONSTRAINT fk_a_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,CONSTRAINT fk_a_slot FOREIGN KEY(slot_id) REFERENCES appointment_slots(id) ON DELETE RESTRICT) ENGINE=InnoDB;
CREATE TABLE notifications (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,member_id INT UNSIGNED NULL,user_id INT UNSIGNED NULL,title VARCHAR(160) NOT NULL,message TEXT NOT NULL,type ENUM('info','success','warning','danger') DEFAULT 'info',is_read TINYINT(1) DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,KEY idx_member(member_id,is_read),CONSTRAINT fk_n_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE CASCADE,CONSTRAINT fk_n_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE activity_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,member_id INT UNSIGNED NULL,user_id INT UNSIGNED NULL,action VARCHAR(100) NOT NULL,description VARCHAR(255),ip_address VARCHAR(45),user_agent VARCHAR(255),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,KEY idx_created(created_at),CONSTRAINT fk_l_member FOREIGN KEY(member_id) REFERENCES members(id) ON DELETE SET NULL,CONSTRAINT fk_l_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB;
-- Development staff: username pastor / password password. Change immediately.
INSERT INTO users(username,full_name,email,password_hash,role) VALUES('pastor','FGCK Joyland Pastor','pastor@fgckjoyland.local','$2y$12$qnU6Fj3cOL0Cx.3RWQgGwOReoXzpPYNI69UU.nR7A4zZQLA54Cljy','pastor') ON DUPLICATE KEY UPDATE username=username;
-- Appointment schedules are generated dynamically


-- Communications and church events

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
