
-- =====================================================================
-- Voter List Management Website
-- MySQL Schema File
-- Version: 1.0
-- Target: MySQL 8.0+
-- =====================================================================
-- Notes
-- 1. This schema is designed for a PHP + MySQL voter list management site.
-- 2. It supports:
--      - User roles (Administrator, Moderator)
--      - PDF import tracking
--      - Geographic hierarchy (State > District > City > Constituency > Part)
--      - Voter search by multiple criteria
--      - Audit logs and notification logs
--      - Dark/light theme preference at user level
-- 3. OCR / PDF text extraction happens in the application layer, not in SQL.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `civicscan`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `civicscan`;

-- =====================================================================
-- TABLE: users
-- =====================================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('administrator', 'moderator') NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `profile_image_path` VARCHAR(255) NULL,
  `address_line_1` VARCHAR(255) NULL,
  `address_line_2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `theme_preference` ENUM('dark', 'light', 'system') NOT NULL DEFAULT 'dark',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `email_verified_at` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `last_password_changed_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_name` (`name`),
  KEY `idx_users_created_by` (`created_by`),
  KEY `idx_users_updated_by` (`updated_by`),
  KEY `idx_users_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_users_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: password_reset_tokens
-- =====================================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_tokens_hash` (`token_hash`),
  KEY `idx_password_reset_tokens_user_id` (`user_id`),
  KEY `idx_password_reset_tokens_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: states
-- =====================================================================
CREATE TABLE IF NOT EXISTS `states` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_states_name` (`name`),
  UNIQUE KEY `uq_states_code` (`code`),
  KEY `idx_states_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: districts
-- =====================================================================
CREATE TABLE IF NOT EXISTS `districts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `state_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(20) NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_districts_state_name` (`state_id`, `name`),
  UNIQUE KEY `uq_districts_state_code` (`state_id`, `code`),
  KEY `idx_districts_state_id` (`state_id`),
  KEY `idx_districts_status` (`status`),
  CONSTRAINT `fk_districts_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: cities
-- =====================================================================
CREATE TABLE IF NOT EXISTS `cities` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `state_id` BIGINT UNSIGNED NOT NULL,
  `district_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cities_district_name` (`district_id`, `name`),
  KEY `idx_cities_state_id` (`state_id`),
  KEY `idx_cities_district_id` (`district_id`),
  KEY `idx_cities_status` (`status`),
  CONSTRAINT `fk_cities_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cities_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: constituencies
-- =====================================================================
CREATE TABLE IF NOT EXISTS `constituencies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `state_id` BIGINT UNSIGNED NOT NULL,
  `district_id` BIGINT UNSIGNED NOT NULL,
  `city_id` BIGINT UNSIGNED NULL,
  `constituency_type` ENUM('assembly', 'parliament', 'local') NOT NULL DEFAULT 'assembly',
  `constituency_number` VARCHAR(50) NULL,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_constituencies_location_name` (`state_id`, `district_id`, `name`),
  UNIQUE KEY `uq_constituencies_code` (`code`),
  KEY `idx_constituencies_state_id` (`state_id`),
  KEY `idx_constituencies_district_id` (`district_id`),
  KEY `idx_constituencies_city_id` (`city_id`),
  KEY `idx_constituencies_status` (`status`),
  KEY `idx_constituencies_created_by` (`created_by`),
  KEY `idx_constituencies_updated_by` (`updated_by`),
  KEY `idx_constituencies_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_constituencies_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_constituencies_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_constituencies_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_constituencies_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_constituencies_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_constituencies_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: constituency_parts
-- =====================================================================
CREATE TABLE IF NOT EXISTS `constituency_parts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `constituency_id` BIGINT UNSIGNED NOT NULL,
  `part_number` VARCHAR(50) NOT NULL,
  `part_name` VARCHAR(150) NULL,
  `polling_station_name` VARCHAR(255) NULL,
  `polling_station_address` VARCHAR(255) NULL,
  `locality` VARCHAR(255) NULL,
  `total_electors` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_male` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_female` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_other` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_constituency_parts_constituency_part` (`constituency_id`, `part_number`),
  KEY `idx_constituency_parts_status` (`status`),
  KEY `idx_constituency_parts_created_by` (`created_by`),
  KEY `idx_constituency_parts_updated_by` (`updated_by`),
  KEY `idx_constituency_parts_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_constituency_parts_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_constituency_parts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_constituency_parts_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_constituency_parts_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: voter_import_batches
-- =====================================================================
CREATE TABLE IF NOT EXISTS `voter_import_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_uuid` CHAR(36) NULL,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `state_id` BIGINT UNSIGNED NULL,
  `district_id` BIGINT UNSIGNED NULL,
  `city_id` BIGINT UNSIGNED NULL,
  `constituency_id` BIGINT UNSIGNED NULL,
  `part_id` BIGINT UNSIGNED NULL,
  `original_file_name` VARCHAR(255) NOT NULL,
  `stored_file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `mime_type` VARCHAR(100) NULL,
  `file_checksum_sha256` CHAR(64) NULL,
  `source_year` YEAR NULL,
  `source_language` VARCHAR(50) NULL,
  `extraction_engine` ENUM('text', 'ocr', 'hybrid', 'manual') NOT NULL DEFAULT 'text',
  `import_status` ENUM('queued', 'processing', 'completed', 'completed_with_errors', 'failed') NOT NULL DEFAULT 'queued',
  `total_pages` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_records_detected` INT UNSIGNED NOT NULL DEFAULT 0,
  `inserted_records` INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_records` INT UNSIGNED NOT NULL DEFAULT 0,
  `skipped_records` INT UNSIGNED NOT NULL DEFAULT 0,
  `failed_records` INT UNSIGNED NOT NULL DEFAULT 0,
  `started_at` DATETIME NULL,
  `finished_at` DATETIME NULL,
  `remarks` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voter_import_batches_uuid` (`file_uuid`),
  KEY `idx_voter_import_batches_uploaded_by` (`uploaded_by`),
  KEY `idx_voter_import_batches_state_id` (`state_id`),
  KEY `idx_voter_import_batches_district_id` (`district_id`),
  KEY `idx_voter_import_batches_constituency_id` (`constituency_id`),
  KEY `idx_voter_import_batches_part_id` (`part_id`),
  KEY `idx_voter_import_batches_status` (`import_status`),
  KEY `idx_voter_import_batches_checksum` (`file_checksum_sha256`),
  CONSTRAINT `fk_voter_import_batches_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_voter_import_batches_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voter_import_batches_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voter_import_batches_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voter_import_batches_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voter_import_batches_part` FOREIGN KEY (`part_id`) REFERENCES `constituency_parts` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: voter_import_errors
-- =====================================================================
CREATE TABLE IF NOT EXISTS `voter_import_errors` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `page_number` INT UNSIGNED NULL,
  `row_number` INT UNSIGNED NULL,
  `error_code` VARCHAR(50) NULL,
  `error_message` VARCHAR(500) NOT NULL,
  `raw_excerpt` LONGTEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voter_import_errors_batch_id` (`batch_id`),
  KEY `idx_voter_import_errors_page_row` (`page_number`, `row_number`),
  CONSTRAINT `fk_voter_import_errors_batch` FOREIGN KEY (`batch_id`) REFERENCES `voter_import_batches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: voters
-- =====================================================================
CREATE TABLE IF NOT EXISTS `voters` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `state_id` BIGINT UNSIGNED NOT NULL,
  `district_id` BIGINT UNSIGNED NOT NULL,
  `city_id` BIGINT UNSIGNED NULL,
  `constituency_id` BIGINT UNSIGNED NOT NULL,
  `part_id` BIGINT UNSIGNED NOT NULL,
  `import_batch_id` BIGINT UNSIGNED NULL,
  `serial_number` VARCHAR(50) NOT NULL,
  `voter_card_number` VARCHAR(50) NULL,
  `elector_name` VARCHAR(150) NOT NULL,
  `relation_type` ENUM('father', 'mother', 'husband', 'guardian', 'other', 'unknown') NOT NULL DEFAULT 'unknown',
  `relation_name` VARCHAR(150) NULL,
  `house_number` VARCHAR(100) NULL,
  `age` SMALLINT UNSIGNED NULL,
  `gender` ENUM('male', 'female', 'other', 'unknown') NOT NULL DEFAULT 'unknown',
  `locality` VARCHAR(255) NULL,
  `address_line_1` VARCHAR(255) NULL,
  `address_line_2` VARCHAR(255) NULL,
  `section_name` VARCHAR(150) NULL,
  `section_number` VARCHAR(50) NULL,
  `polling_station_name` VARCHAR(255) NULL,
  `polling_station_address` VARCHAR(255) NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `raw_payload` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voters_part_serial_number` (`part_id`, `serial_number`),
  KEY `idx_voters_state_id` (`state_id`),
  KEY `idx_voters_district_id` (`district_id`),
  KEY `idx_voters_city_id` (`city_id`),
  KEY `idx_voters_constituency_id` (`constituency_id`),
  KEY `idx_voters_part_id` (`part_id`),
  KEY `idx_voters_import_batch_id` (`import_batch_id`),
  KEY `idx_voters_serial_number` (`serial_number`),
  KEY `idx_voters_voter_card_number` (`voter_card_number`),
  KEY `idx_voters_elector_name` (`elector_name`),
  KEY `idx_voters_relation_name` (`relation_name`),
  KEY `idx_voters_house_number` (`house_number`),
  KEY `idx_voters_locality` (`locality`),
  KEY `idx_voters_status` (`status`),
  KEY `idx_voters_deleted_by` (`deleted_by`),
  FULLTEXT KEY `ft_voters_search` (`elector_name`, `relation_name`, `house_number`, `voter_card_number`, `locality`, `address_line_1`, `polling_station_name`),
  CONSTRAINT `fk_voters_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_voters_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_voters_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voters_constituency` FOREIGN KEY (`constituency_id`) REFERENCES `constituencies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_voters_part` FOREIGN KEY (`part_id`) REFERENCES `constituency_parts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_voters_import_batch` FOREIGN KEY (`import_batch_id`) REFERENCES `voter_import_batches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voters_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: notification_logs
-- =====================================================================
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `channel` ENUM('email', 'sms', 'system') NOT NULL DEFAULT 'email',
  `event_type` VARCHAR(100) NOT NULL,
  `recipient` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(255) NULL,
  `body` LONGTEXT NULL,
  `status` ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
  `error_message` TEXT NULL,
  `sent_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_logs_user_id` (`user_id`),
  KEY `idx_notification_logs_status` (`status`),
  KEY `idx_notification_logs_event_type` (`event_type`),
  CONSTRAINT `fk_notification_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: audit_logs
-- =====================================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(100) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `table_name` VARCHAR(100) NOT NULL,
  `record_id` VARCHAR(100) NOT NULL,
  `old_values` JSON NULL,
  `new_values` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_actor_user_id` (`actor_user_id`),
  KEY `idx_audit_logs_module` (`module`),
  KEY `idx_audit_logs_action` (`action`),
  KEY `idx_audit_logs_table_record` (`table_name`, `record_id`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  CONSTRAINT `fk_audit_logs_actor_user` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- TABLE: system_settings
-- =====================================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(150) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `value_type` ENUM('string', 'number', 'boolean', 'json', 'text') NOT NULL DEFAULT 'string',
  `is_public` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_system_settings_setting_key` (`setting_key`),
  KEY `idx_system_settings_is_public` (`is_public`),
  CONSTRAINT `fk_system_settings_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- VIEW: vw_voter_directory
-- Helpful for voter search screens and exports
-- =====================================================================
CREATE OR REPLACE VIEW `vw_voter_directory` AS
SELECT
  v.id AS voter_id,
  s.name AS state_name,
  d.name AS district_name,
  cty.name AS city_name,
  cons.name AS constituency_name,
  cons.constituency_number,
  cp.part_number,
  cp.part_name,
  v.serial_number,
  v.voter_card_number,
  v.elector_name,
  v.relation_type,
  v.relation_name,
  v.house_number,
  v.age,
  v.gender,
  v.locality,
  v.address_line_1,
  v.address_line_2,
  v.section_name,
  v.section_number,
  v.polling_station_name,
  v.polling_station_address,
  v.status,
  v.created_at,
  v.updated_at
FROM `voters` v
INNER JOIN `states` s ON s.id = v.state_id
INNER JOIN `districts` d ON d.id = v.district_id
LEFT JOIN `cities` cty ON cty.id = v.city_id
INNER JOIN `constituencies` cons ON cons.id = v.constituency_id
INNER JOIN `constituency_parts` cp ON cp.id = v.part_id
WHERE v.deleted_at IS NULL;

-- =====================================================================
-- SEED SETTINGS (OPTIONAL)
-- =====================================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `value_type`, `is_public`)
VALUES
  ('site_name', 'Voter List Management', 'string', 1),
  ('default_theme', 'dark', 'string', 1),
  ('allow_moderator_pdf_import', 'true', 'boolean', 0)
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`),
  `value_type` = VALUES(`value_type`),
  `is_public` = VALUES(`is_public`);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- FIRST ADMIN ACCOUNT
-- =====================================================================
INSERT INTO `users`
  (`role`, `name`, `email`, `phone`, `password_hash`, `status`, `created_at`, `updated_at`)
VALUES
  ('administrator', 'admin', 'subhro.pramanik7@gmail.com', '', '$2y$12$0i5udR55sq9A3X75zwPYmeSMbSdy/3Lr8cbD6kkPR/gUnhern4qNC', 'active', NOW(), NOW());
-- =====================================================================
