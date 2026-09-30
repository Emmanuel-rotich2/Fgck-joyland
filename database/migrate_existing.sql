USE fgck_joyland;

-- Existing-installation migration: store Pastor time adjustments on the appointment itself.
SET @db = DATABASE();

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='appointments' AND COLUMN_NAME='adjusted_start_time')=0,
  'ALTER TABLE appointments ADD COLUMN adjusted_start_time TIME NULL AFTER completed_at',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='appointments' AND COLUMN_NAME='adjusted_end_time')=0,
  'ALTER TABLE appointments ADD COLUMN adjusted_end_time TIME NULL AFTER adjusted_start_time',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The active booking is protected by the application transaction; historical appointments may reuse a slot.
SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='appointments' AND INDEX_NAME='uq_slot')>0,
  'ALTER TABLE appointments DROP INDEX uq_slot',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='appointments' AND INDEX_NAME='idx_slot_status')=0,
  'ALTER TABLE appointments ADD KEY idx_slot_status(slot_id,status)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='appointments' AND INDEX_NAME='idx_member_status')=0,
  'ALTER TABLE appointments ADD KEY idx_member_status(member_id,status)',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO settings(setting_key,setting_value) VALUES

('office_status','available'),
('booking_notice','Please arrive a few minutes before your appointment.')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

DELETE FROM settings WHERE setting_key='arrival_time';

-- Fixed appointment window: pastor controls opening time; closing time is always 3:00 PM.
INSERT INTO settings(setting_key,setting_value)
VALUES ('closing_time','15:00')
ON DUPLICATE KEY UPDATE setting_value='15:00';

-- Keep the legacy setting aligned for existing installations.
UPDATE settings SET setting_value='15:00' WHERE setting_key='closing_time';
