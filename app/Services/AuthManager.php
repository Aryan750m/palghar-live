<?php

// app/Services/AuthManager.php

namespace App\Services;

use App\Database;
use App\Services\Logger;

class AuthManager
{
    /**
     * Authenticate an admin or editor user
     */
    public static function login(string $username, string $password): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $config = require __DIR__ . '/../Config/security.php';
        $lockoutConfig = $config['lockout'] ?? [];
        $maxAttempts = $lockoutConfig['max_attempts'] ?? 5;
        $lockoutDuration = $lockoutConfig['duration'] ?? 900;

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Check for active lockout
        if (self::isLockedOut($username, $ip, $lockoutDuration)) {
            Logger::security("Blocked login attempt due to active lockout", ['username' => $username, 'ip' => $ip]);
            return false;
        }

        // Retrieve user from DB
        $user = Database::fetch("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);

        if (!$user || $user['status'] !== 'enabled') {
            self::registerFailedAttempt($username, $ip);
            Logger::security("Failed login attempt (user not found or disabled)", ['username' => $username, 'ip' => $ip]);
            return false;
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            self::registerFailedAttempt($username, $ip);
            Logger::security("Failed login attempt (incorrect password)", ['username' => $username, 'ip' => $ip]);
            return false;
        }

        // Password needs rehash?
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        if (password_needs_rehash($user['password'], $algo)) {
            $newHash = password_hash($password, $algo);
            Database::execute("UPDATE users SET password = ? WHERE id = ?", [$newHash, $user['id']]);
            Logger::info("User password rehashed to modern standard", ['username' => $username]);
        }

        // Regenerate session to prevent fixation
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // Legacy session variables for backward compatibility
        $_SESSION['palghar_live_admin_session'] = true;
        $_SESSION['palghar_live_admin_username'] = $user['username'];
        $_SESSION['palghar_live_admin_role'] = $user['role'];
        $_SESSION['palghar_live_admin_id'] = $user['id'];

        if ($user['role'] === 'editor') {
            $perms = Database::query("SELECT section_id FROM user_permissions WHERE user_id = ?", [$user['id']]);
            $_SESSION['palghar_live_admin_permissions'] = array_column($perms, 'section_id');
        } else {
            $_SESSION['palghar_live_admin_permissions'] = [];
        }

        // Clear failed attempts upon success
        self::clearFailedAttempts($username, $ip);

        Logger::security("Successful administrator login", ['username' => $username, 'role' => $user['role'], 'ip' => $ip]);
        return true;
    }

    /**
     * Log user out
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = $_SESSION['username'] ?? 'unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        Logger::security("User logged out", ['username' => $username, 'ip' => $ip]);

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Verify if the user session is active and valid
     */
    public static function checkSession(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
            return false;
        }

        $config = require __DIR__ . '/../Config/security.php';
        $timeout = $config['session']['lifetime'] ?? 1800;

        // Idle session timeout check
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            Logger::security("Session timed out due to inactivity", ['username' => $_SESSION['username']]);
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Verify if user/IP is currently locked out
     */
    private static function isLockedOut(string $username, string $ip, int $duration): bool
    {
        $key = 'lockout_' . md5($username . '_' . $ip);
        if (isset($_SESSION[$key])) {
            if (time() < $_SESSION[$key]) {
                return true;
            }
            // Expiry passed, clean up
            unset($_SESSION[$key]);
            unset($_SESSION[$key . '_attempts']);
        }
        return false;
    }

    /**
     * Log a failed login attempt
     */
    private static function registerFailedAttempt(string $username, string $ip): void
    {
        $key = 'lockout_' . md5($username . '_' . $ip);

        if (!isset($_SESSION[$key . '_attempts'])) {
            $_SESSION[$key . '_attempts'] = 0;
        }

        $_SESSION[$key . '_attempts']++;

        $config = require __DIR__ . '/../Config/security.php';
        $maxAttempts = $config['lockout']['max_attempts'] ?? 5;
        $lockoutDuration = $config['lockout']['duration'] ?? 900;

        if ($_SESSION[$key . '_attempts'] >= $maxAttempts) {
            $_SESSION[$key] = time() + $lockoutDuration;
            Logger::security("IP locked out due to excessive failed attempts", [
                'username' => $username,
                'ip' => $ip,
                'duration_seconds' => $lockoutDuration
            ]);
        }
    }

    /**
     * Reset login history logs
     */
    private static function clearFailedAttempts(string $username, string $ip): void
    {
        $key = 'lockout_' . md5($username . '_' . $ip);
        unset($_SESSION[$key]);
        unset($_SESSION[$key . '_attempts']);
    }
}
