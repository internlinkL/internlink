-- ═══════════════════════════════════════════════════════════════════════════
-- internLink — Admin Module SQL Upgrade
-- Run this in phpMyAdmin AFTER internlink_upgrade.sql
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 1. Create the first admin account ────────────────────────────────────────
-- Default credentials: admin@internlink.com / Admin@1234
-- CHANGE THE PASSWORD after first login via the Security tab.
INSERT IGNORE INTO `users` (first_name, last_name, email, password, role)
VALUES (
    'Admin',
    'internLink',
    'admin@internlink.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin'
);

-- ► IMPORTANT: After importing, log in and change the password immediately.
-- The hash above is bcrypt of "password". Replace it with:
--   php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"

-- ── 2. Ensure internships has is_active column (from internlink_upgrade.sql) ─
-- Already added there, this is a safety net:
ALTER TABLE `internships`
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1;

UPDATE `internships` SET `is_active` = 1 WHERE `status` = 'open';
UPDATE `internships` SET `is_active` = 0 WHERE `status` = 'closed';

-- ── 3. Ensure applications has match_percent ──────────────────────────────────
ALTER TABLE `applications`
  ADD COLUMN IF NOT EXISTS `match_percent` INT UNSIGNED DEFAULT 0 AFTER `cover_letter`;

-- Done!
SELECT 'Admin module upgrade complete. Login: admin@internlink.com / password' AS result;
