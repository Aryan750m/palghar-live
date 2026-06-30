<?php

// app/Cache.php

namespace App;

class Cache
{
    private static array $store = [];

    /**
     * Store an item in the in-memory cache
     */
    public static function set(string $key, mixed $value): void
    {
        self::$store[$key] = $value;
    }

    /**
     * Retrieve an item from the in-memory cache
     */
    public static function get(string $key): mixed
    {
        return self::$store[$key] ?? null;
    }

    /**
     * Check if an item exists in the in-memory cache
     */
    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$store);
    }

    /**
     * Remove an item from the cache
     */
    public static function delete(string $key): void
    {
        unset(self::$store[$key]);
    }

    /**
     * Clear all cached items in memory
     */
    public static function clear(): void
    {
        self::$store = [];
    }
}
