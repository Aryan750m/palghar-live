<?php
// app/Middleware/CSRFCheck.php

namespace App\Middleware;

use App\Services\Logger;

class CSRFCheck {
    /**
     * Generate a new CSRF token and save it to the session
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $config = require __DIR__ . '/../Config/security.php';
        $expiryTime = $config['csrf']['expiry'] ?? 3600;
        
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry']) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_expiry'] = time() + $expiryTime;
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate an incoming CSRF token
     */
    public static function validateToken(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token) || empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expiry'])) {
            Logger::security("CSRF validation failed: Missing token in session or request");
            return false;
        }
        
        // Check token expiry
        if (time() > $_SESSION['csrf_token_expiry']) {
            Logger::security("CSRF validation failed: Expired token");
            self::clearToken();
            return false;
        }
        
        // Strict cryptographic hash check
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            Logger::security("CSRF validation failed: Token mismatch");
            return false;
        }
        
        return true;
    }

    /**
     * Clear active token from session
     */
    public static function clearToken(): void {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_expiry']);
    }

    /**
     * Render hidden HTML form input containing the token
     */
    public static function getInputField(): string {
        $token = self::generateToken();
        $config = require __DIR__ . '/../Config/security.php';
        $name = $config['csrf']['token_name'] ?? 'csrf_token';
        return sprintf('<input type="hidden" name="%s" value="%s">', htmlspecialchars($name), htmlspecialchars($token));
    }

    /**
     * Get the current token value (alias for generateToken for use in URL params)
     */
    public static function getToken(): string {
        return self::generateToken();
    }
}
