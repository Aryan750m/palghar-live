<?php

// App Configuration: app/Config/app.php

return [
    'name' => 'Palghar LIVE',
    'slogan' => 'The Strong Voice of the Common People',
    'timezone' => 'Asia/Kolkata',
    'debug' => getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1',
    'env' => getenv('APP_ENV') ?: 'production',
    'url' => getenv('APP_URL') ?: (
        isset($_SERVER['HTTP_HOST'])
        ? (
            (
                (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                ? 'https' : 'http'
            )
            . '://' . $_SERVER['HTTP_HOST'] . (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/news-channel') ? '/news-channel' : '')
          )
        : 'https://palghar-live.onrender.com'
    ),

    // Core directories
    'paths' => [
        'root' => dirname(__DIR__, 2),
        'uploads' => dirname(__DIR__, 2) . '/uploads',
        'cache' => dirname(__DIR__, 2) . '/cache',
        'logs' => dirname(__DIR__, 2) . '/logs',
    ],
];
