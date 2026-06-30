<?php
// Config: config/database.php
// Production-ready PDO connection configured for Hostinger Shared Hosting

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'palghar_live_db');
define('DB_CHAR', 'utf8mb4');

function getDatabaseConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Enforce true prepared statements for SQLi security
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error privately, do not expose internal DB schemas or paths to client
            error_log("Database connection failure: " . $e->getMessage());
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode([
                "success" => false,
                "error" => "Database connection failure. Please verify database configurations."
            ]);
            exit;
        }
    }
    
    return $pdo;
}
