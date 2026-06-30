<?php
// app/Database.php

namespace App;

use PDO;
use PDOException;
use App\Services\Logger;

class Database {
    private static ?PDO $pdo = null;
    private static array $queryLog = [];

    /**
     * Get the active database connection
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/Config/database.php';
            
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            
            try {
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (PDOException $e) {
                Logger::error("Database connection failure: " . $e->getMessage());
                // Handle 500 error page if connection fails completely
                http_response_code(500);
                if (file_exists(dirname(__DIR__) . '/errors/500.php')) {
                    include dirname(__DIR__) . '/errors/500.php';
                } else {
                    echo "<h1>500 Internal Server Error</h1><p>Database connection failure. Please try again later.</p>";
                }
                exit;
            }
        }
        
        return self::$pdo;
    }

    /**
     * Execute a prepared query with profiling
     */
    public static function query(string $sql, array $params = []): array {
        $pdo = self::getConnection();
        $startTime = microtime(true);
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            
            $duration = (microtime(true) - $startTime) * 1000.0; // In milliseconds
            self::logQuery($sql, $params, $duration);
            
            return $results;
        } catch (PDOException $e) {
            Logger::error("Database query failure: " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    /**
     * Execute a single record query with profiling
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $pdo = self::getConnection();
        $startTime = microtime(true);
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            $duration = (microtime(true) - $startTime) * 1000.0;
            self::logQuery($sql, $params, $duration);
            
            return $result ?: null;
        } catch (PDOException $e) {
            Logger::error("Database fetch failure: " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    /**
     * Execute write queries (insert, update, delete)
     */
    public static function execute(string $sql, array $params = []): int {
        $pdo = self::getConnection();
        $startTime = microtime(true);
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            
            $duration = (microtime(true) - $startTime) * 1000.0;
            self::logQuery($sql, $params, $duration);
            
            return $affected;
        } catch (PDOException $e) {
            Logger::error("Database execution failure: " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            throw $e;
        }
    }

    /**
     * Retrieve the last inserted ID
     */
    public static function lastInsertId(): string {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Start a transaction
     */
    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    /**
     * Rollback a transaction
     */
    public static function rollBack(): bool {
        return self::getConnection()->rollBack();
    }

    /**
     * Log and profile the query
     */
    private static function logQuery(string $sql, array $params, float $durationMs): void {
        $config = require __DIR__ . '/Config/database.php';
        $threshold = $config['slow_query_threshold'] ?? 100.0;
        
        $logData = [
            'sql' => $sql,
            'params' => $params,
            'duration_ms' => $durationMs
        ];
        
        self::$queryLog[] = $logData;
        
        if ($durationMs >= $threshold) {
            Logger::sql(sprintf("SLOW QUERY (%.2fms): %s", $durationMs, $sql), ['params' => $params]);
        } else {
            Logger::sql(sprintf("Query completed in %.2fms: %s", $durationMs, $sql), ['params' => $params]);
        }
    }

    /**
     * Get all executed queries for profiling
     */
    public static function getQueryLog(): array {
        return self::$queryLog;
    }
}
