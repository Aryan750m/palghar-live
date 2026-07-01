<?php

// Security Configuration: app/Config/security.php

return [
    // CSRF Protection settings
    'csrf' => [
        'token_name' => 'csrf_token',
        'expiry' => 3600, // Token lifetime in seconds (1 hour)
    ],

    // Rate Limiting settings (Window in seconds -> Max requests)
    'rate_limiting' => [
        'enabled' => true,
        'window' => 60,       // 1 minute
        'max_requests' => 60, // Maximum 60 requests per minute for public endpoints
        'admin_max_requests' => 15, // Maximum 15 logins/modifications per minute
    ],

    // Admin lockout configurations
    'lockout' => [
        'max_attempts' => 5,
        'duration' => 900, // 15 minutes in seconds
    ],

    // Session configurations
    'session' => [
        'lifetime' => 1800, // 30 minutes in idle
        'secure' => true,   // True in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    // Content Security Policy Directives
    'csp' => [
        'default-src' => "'self'",
        'script-src'  => ["'self'", "https://cdnjs.cloudflare.com", "https://www.youtube.com", "https://s.ytimg.com"],
        'style-src'   => ["'self'", "'unsafe-inline'", "https://cdnjs.cloudflare.com", "https://fonts.googleapis.com"],
        'font-src'    => ["'self'", "https://cdnjs.cloudflare.com", "https://fonts.gstatic.com"],
        'img-src'     => ["'self'", "data:", "https://images.unsplash.com", "https://via.placeholder.com", "https://img.youtube.com", "https://i.ytimg.com"],
        'frame-src'   => ["'self'", "https://www.youtube.com", "https://youtube.com"],
    ],
];
