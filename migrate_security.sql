-- Fabcam Technologies — Login security (brute-force protection)
-- Run once against an existing database (db.sql already includes these for fresh installs).
-- Safe to run more than once. Existing data is not modified.
--
-- BEFORE RUNNING: phpMyAdmin -> select DB -> Export -> Quick -> Go (backup!)
-- HOW TO RUN   : phpMyAdmin -> select DB -> SQL tab -> paste all -> Go

-- 1. Every login attempt (used for per-IP / per-email rate limit and review)
CREATE TABLE IF NOT EXISTS security_login_attempts (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier  VARCHAR(190) NOT NULL,             -- email tried (lowercase)
  ip_address  VARCHAR(45)  NOT NULL,             -- fits IPv6
  success     TINYINT(1)   NOT NULL DEFAULT 0,
  user_agent  VARCHAR(255) NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ident_time (identifier, created_at),
  KEY idx_ip_time    (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Lockout columns on users (added only if missing)
DROP PROCEDURE IF EXISTS sec_add_col;
DELIMITER $$
CREATE PROCEDURE sec_add_col(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def VARCHAR(255))
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col)
  THEN
    SET @sec_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
    PREPARE sec_stmt FROM @sec_sql;
    EXECUTE sec_stmt;
    DEALLOCATE PREPARE sec_stmt;
  END IF;
END$$
DELIMITER ;

CALL sec_add_col('users', 'failed_login_count', 'INT UNSIGNED NOT NULL DEFAULT 0');
CALL sec_add_col('users', 'locked_until',       'DATETIME NULL');
CALL sec_add_col('users', 'last_login_ip',      'VARCHAR(45) NULL');

DROP PROCEDURE IF EXISTS sec_add_col;

-- 3. CHECK (read-only)
SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' ORDER BY ORDINAL_POSITION;

-- ---------------------------------------------------------------------
-- Useful queries (run manually when needed)
-- ---------------------------------------------------------------------
-- Failed logins in the last 24 hours, grouped by IP:
-- SELECT ip_address, COUNT(*) AS fails, MAX(created_at) AS last_try
-- FROM security_login_attempts
-- WHERE success = 0 AND created_at > NOW() - INTERVAL 1 DAY
-- GROUP BY ip_address ORDER BY fails DESC;
--
-- Unlock a user immediately:
-- UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE email = 'someone@fabcam.com';
--
-- Housekeeping (monthly): delete attempt rows older than 90 days
-- DELETE FROM security_login_attempts WHERE created_at < NOW() - INTERVAL 90 DAY;
