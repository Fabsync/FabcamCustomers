<?php

/**
 * Brute-force protection for the login form (tables from migrate_security.sql).
 *  - 5 wrong passwords on an account -> locked 15 minutes
 *  - 20 failed attempts from one IP in 15 minutes -> IP blocked for the window
 *  - every attempt is logged in security_login_attempts
 *
 * Time checks run in MySQL (NOW()) so PHP/MySQL timezone differences don't matter.
 * If the migration hasn't been run yet, guard calls log the error and let the
 * login continue (fail-open) instead of locking everyone out.
 */
class LoginGuard {

    const MAX_FAILS     = 5;
    const LOCK_MINUTES  = 15;
    const IP_MAX_FAILS  = 20;
    const IP_WINDOW_MIN = 15;

    // Used to run password_verify() when the email doesn't exist, so response
    // time doesn't reveal which emails are real. Hash of a random throwaway value.
    const DUMMY_HASH = '$2y$12$u23QueM7SY71y5NlLKGOSOJg0pKAgG3GUTOni6pYxQ39FMXiw3qVi';

    public static function clientIp(): string {
        return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
    }

    public static function normalize(string $identifier): string {
        return substr(strtolower(trim($identifier)), 0, 190);
    }

    /** Too many failures from this IP recently? */
    public static function ipBlocked(string $ip): bool {
        return self::safe(function (PDO $db) use ($ip) {
            $s = $db->prepare(
                'SELECT COUNT(*) FROM security_login_attempts
                 WHERE ip_address = ? AND success = 0
                   AND created_at > NOW() - INTERVAL ' . self::IP_WINDOW_MIN . ' MINUTE'
            );
            $s->execute([$ip]);
            return (int) $s->fetchColumn() >= self::IP_MAX_FAILS;
        }, false);
    }

    /**
     * Is this login locked? For a real user this checks users.locked_until; for an
     * unknown email it checks recent failures in the log, so both cases return the
     * same "too many attempts" message (no hint about which emails exist).
     */
    public static function isLocked(?int $userId, string $identifier): bool {
        return self::safe(function (PDO $db) use ($userId, $identifier) {
            if ($userId !== null) {
                $s = $db->prepare('SELECT (locked_until IS NOT NULL AND locked_until > NOW()) FROM users WHERE id = ?');
                $s->execute([$userId]);
                return (bool) $s->fetchColumn();
            }
            $s = $db->prepare(
                'SELECT COUNT(*) FROM security_login_attempts
                 WHERE identifier = ? AND success = 0
                   AND created_at > NOW() - INTERVAL ' . self::LOCK_MINUTES . ' MINUTE'
            );
            $s->execute([self::normalize($identifier)]);
            return (int) $s->fetchColumn() >= self::MAX_FAILS;
        }, false);
    }

    /** Wrong password: count it; lock after MAX_FAILS (counter resets when locking). */
    public static function fail(int $userId): void {
        self::safe(function (PDO $db) use ($userId) {
            $max = self::MAX_FAILS;
            $db->prepare(
                "UPDATE users SET
                   locked_until       = IF(failed_login_count + 1 >= $max, NOW() + INTERVAL " . self::LOCK_MINUTES . " MINUTE, locked_until),
                   failed_login_count = IF(failed_login_count + 1 >= $max, 0, failed_login_count + 1)
                 WHERE id = ?"
            )->execute([$userId]);
        }, null);
    }

    /** Correct password: clear counters, record login IP. */
    public static function success(int $userId, string $ip): void {
        self::safe(function (PDO $db) use ($userId, $ip) {
            $db->prepare(
                'UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_ip = ? WHERE id = ?'
            )->execute([$ip, $userId]);
        }, null);
    }

    public static function log(string $identifier, string $ip, bool $ok): void {
        self::safe(function (PDO $db) use ($identifier, $ip, $ok) {
            $db->prepare(
                'INSERT INTO security_login_attempts (identifier, ip_address, success, user_agent)
                 VALUES (?, ?, ?, ?)'
            )->execute([
                self::normalize($identifier),
                $ip,
                $ok ? 1 : 0,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        }, null);
    }

    private static function safe(callable $fn, $fallback) {
        try {
            return $fn(Database::getInstance());
        } catch (PDOException $e) {
            error_log('LoginGuard: ' . $e->getMessage() . ' (has migrate_security.sql been run?)');
            return $fallback;
        }
    }
}
