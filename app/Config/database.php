<?php
// Database Configuration: app/Config/database.php

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'database' => getenv('DB_NAME') ?: 'palghar_live_db',
    'port' => getenv('DB_PORT') ?: 3306,
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
    // Slow query threshold in milliseconds
    'slow_query_threshold' => 100.0,
];
