<?php
// app/Services/Logger.php

namespace App\Services;

class Logger {
    private static string $logDir = '';

    private static function init(): void {
        if (self::$logDir === '') {
            $config = require __DIR__ . '/../Config/app.php';
            self::$logDir = $config['paths']['logs'] ?? dirname(__DIR__, 2) . '/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }

    private static function write(string $channel, string $level, string $message, array $context = []): void {
        self::init();
        
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $fileName = self::$logDir . '/' . $channel . '_' . $date . '.log';
        
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $logLine = sprintf("[%s] [%s] %s%s\n", $time, strtoupper($level), $message, $contextStr);
        
        // Write or append to log file safely
        file_put_contents($fileName, $logLine, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $message, array $context = []): void {
        self::write('application', 'debug', $message, $context);
    }

    public static function info(string $message, array $context = []): void {
        self::write('application', 'info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void {
        self::write('application', 'warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void {
        self::write('application', 'error', $message, $context);
    }

    public static function security(string $message, array $context = []): void {
        self::write('security', 'alert', $message, $context);
    }

    public static function performance(string $message, array $context = []): void {
        self::write('performance', 'info', $message, $context);
    }

    public static function sql(string $message, array $context = []): void {
        self::write('sql', 'info', $message, $context);
    }

    public static function upload(string $message, array $context = []): void {
        self::write('uploads', 'info', $message, $context);
    }
}
