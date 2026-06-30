<?php

// app/Services/PerformanceProfiler.php

namespace App\Services;

use App\Database;
use App\Services\Logger;

class PerformanceProfiler
{
    private static float $startTime = 0.0;
    private static int $startMemory = 0;

    /**
     * Start timing and memory profiling
     */
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$startMemory = memory_get_usage();
    }

    /**
     * Stop profiling and output metrics
     */
    public static function stop(): array
    {
        if (self::$startTime === 0.0) {
            return [];
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $duration = ($endTime - self::$startTime) * 1000.0; // in milliseconds
        $memoryDelta = $endMemory - self::$startMemory;

        $queries = Database::getQueryLog();
        $queryCount = count($queries);
        $totalSqlTime = array_sum(array_column($queries, 'duration_ms'));

        $metrics = [
            'execution_time_ms' => $duration,
            'memory_used_bytes' => $memoryDelta,
            'memory_peak_bytes' => memory_get_peak_usage(),
            'query_count'       => $queryCount,
            'total_sql_time_ms' => $totalSqlTime,
            'ttfb_estimate_ms'  => $duration // For server-rendered, execution duration maps directly to TTFB
        ];

        // Log slow request (> 500ms)
        if ($duration > 500.0) {
            Logger::performance(sprintf("SLOW PAGE LOAD (%.2fms) queries: %d (%.2fms)", $duration, $queryCount, $totalSqlTime), [
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ]);
        }

        return $metrics;
    }

    /**
     * Get size details of core asset folders for admin diagnostics
     */
    public static function getAssetMetrics(): array
    {
        $config = require __DIR__ . '/../Config/app.php';

        $cssFile = $config['paths']['root'] . '/assets/css/style.min.css';
        $jsFile = $config['paths']['root'] . '/assets/js/app.min.js';

        return [
            'css_size_bytes' => file_exists($cssFile) ? filesize($cssFile) : 0,
            'js_size_bytes'  => file_exists($jsFile) ? filesize($jsFile) : 0,
            'uploads_size'   => self::getDirSize($config['paths']['uploads']),
            'cache_size'     => self::getDirSize($config['paths']['cache']),
        ];
    }

    /**
     * Compute directory size recursively
     */
    private static function getDirSize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
