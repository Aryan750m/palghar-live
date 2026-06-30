<?php
// app/Traits/Loggable.php

namespace App\Traits;

use App\Services\Logger;

trait Loggable {
    /**
     * Log a debug message
     */
    protected function logDebug(string $message, array $context = []): void {
        Logger::debug($message, $context);
    }

    /**
     * Log an info message
     */
    protected function logInfo(string $message, array $context = []): void {
        Logger::info($message, $context);
    }

    /**
     * Log a warning message
     */
    protected function logWarning(string $message, array $context = []): void {
        Logger::warning($message, $context);
    }

    /**
     * Log an error message
     */
    protected function logError(string $message, array $context = []): void {
        Logger::error($message, $context);
    }

    /**
     * Log a security alert
     */
    protected function logSecurity(string $message, array $context = []): void {
        Logger::security($message, $context);
    }
}
