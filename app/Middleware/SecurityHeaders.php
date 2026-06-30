<?php

// app/Middleware/SecurityHeaders.php

namespace App\Middleware;

class SecurityHeaders
{
    private static ?string $nonce = null;

    /**
     * Generate or retrieve a cryptographically secure random nonce for CSP
     */
    public static function getNonce(): string
    {
        if (self::$nonce === null) {
            self::$nonce = base64_encode(random_bytes(16));
        }
        return self::$nonce;
    }

    /**
     * Apply secure security response headers and start secure session
     */
    public static function apply(): void
    {
        $config = require __DIR__ . '/../Config/security.php';

        // Start secure session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            $sessionConfig = $config['session'] ?? [];

            // Shared hosting fallback: ensure secure is only enabled if HTTPS is active
            $secure = $sessionConfig['secure'] && (
                (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            );

            session_start([
                'cookie_lifetime' => $sessionConfig['lifetime'] ?? 1800,
                'cookie_path'     => '/',
                'cookie_secure'   => $secure,
                'cookie_httponly' => $sessionConfig['httponly'] ?? true,
                'cookie_samesite' => $sessionConfig['samesite'] ?? 'Lax',
                'use_strict_mode' => true
            ]);
        }

        // Anti-Clickjacking
        header("X-Frame-Options: DENY");

        // XSS Protection header (fallback for older browsers)
        header("X-XSS-Protection: 1; mode=block");

        // Prevent MIME sniffing
        header("X-Content-Type-Options: nosniff");

        // Referrer policy
        header("Referrer-Policy: no-referrer-when-downgrade");

        // Strict Transport Security (HSTS)
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");

        // Content Security Policy
        $cspConfig = $config['csp'] ?? [];
        $nonce = self::getNonce();

        $cspHeader = "default-src " . $cspConfig['default-src'] . "; ";

        // Inject nonce into script-src
        $scriptSrcDirectives = array_merge(["'nonce-" . $nonce . "'"], $cspConfig['script-src']);
        $cspHeader .= "script-src " . implode(" ", $scriptSrcDirectives) . "; ";

        // Styles directive
        $cspHeader .= "style-src " . implode(" ", $cspConfig['style-src']) . "; ";

        // Fonts directive
        $cspHeader .= "font-src " . implode(" ", $cspConfig['font-src']) . "; ";

        // Images directive
        $cspHeader .= "img-src " . implode(" ", $cspConfig['img-src']) . "; ";

        // Frames directive
        $cspHeader .= "frame-src " . implode(" ", $cspConfig['frame-src']) . ";";

        header("Content-Security-Policy: " . $cspHeader);
    }
}
