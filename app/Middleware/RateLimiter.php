<?php

// app/Middleware/RateLimiter.php

namespace App\Middleware;

use App\Services\Logger;

class RateLimiter
{
    /**
     * Enforce rate limiting based on current session and request action
     */
    public static function check(string $action = 'public'): void
    {
        $config = require __DIR__ . '/../Config/security.php';
        $rateConfig = $config['rate_limiting'] ?? [];

        if (!($rateConfig['enabled'] ?? true)) {
            return;
        }

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $currentTime = time();
        $window = $rateConfig['window'] ?? 60;
        $limit = ($action === 'admin' || $action === 'login')
            ? ($rateConfig['admin_max_requests'] ?? 15)
            : ($rateConfig['max_requests'] ?? 60);

        // Initialise rate limiting array structure in session
        if (!isset($_SESSION['rate_limit_hits'][$action])) {
            $_SESSION['rate_limit_hits'][$action] = [];
        }

        // Clean up hits outside current time window
        $_SESSION['rate_limit_hits'][$action] = array_filter(
            $_SESSION['rate_limit_hits'][$action],
            fn($timestamp) => $timestamp > ($currentTime - $window)
        );

        // Add current hit
        $_SESSION['rate_limit_hits'][$action][] = $currentTime;

        // Verify limits
        if (count($_SESSION['rate_limit_hits'][$action]) > $limit) {
            Logger::security("Rate limit exceeded", [
                'action' => $action,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'hits' => count($_SESSION['rate_limit_hits'][$action]),
                'limit' => $limit
            ]);

            http_response_code(429);
            header("Retry-After: " . $window);
            echo "<h1>429 Too Many Requests</h1><p>You have exceeded the rate limit. Please try again in a minute.</p>";
            exit;
        }
    }
}
