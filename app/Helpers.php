<?php

// app/Helpers.php

namespace App;

class Helpers
{
    /**
     * Prevent XSS by recursively sanitising output data
     */
    public static function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        if (is_string($data)) {
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    /**
     * Format database timestamps into readable date format
     */
    public static function formatDate(?string $dateString): string
    {
        if (empty($dateString)) {
            return '';
        }
        $timestamp = strtotime($dateString);
        return date("M d, Y, h:i A", $timestamp);
    }

    /**
     * Map category code to human readable title
     */
    public static function getCategoryLabel(string $catId): string
    {
        $mapping = [
            'local' => 'Palghar Local',
            'state' => 'Maharashtra',
            'national' => 'National',
            'sports' => 'Sports',
            'business' => 'Business',
            'culture' => 'Art & Culture'
        ];
        return $mapping[$catId] ?? ucfirst($catId);
    }

    /**
     * Map category code to theme/color badges
     */
    public static function getCategoryColor(string $catId): string
    {
        $mapping = [
            'local' => 'var(--primary)',
            'state' => '#f59e0b',     // Amber
            'national' => '#0ea5e9',  // Sky Blue
            'sports' => 'var(--secondary)',
            'business' => '#10b981',  // Emerald Green
            'culture' => '#8b5cf6'    // Violet Purple
        ];
        return $mapping[$catId] ?? 'var(--text-muted)';
    }

    /**
     * Retrieve dynamic weather value from Open-Meteo API (with 30-min file caching fallback)
     */
    public static function getWeatherTemp(): string
    {
        $cacheFile = dirname(__DIR__) . '/logs/weather_cache.json';
        $cacheTime = 1800; // 30 minutes
        $fallbackTemp = "29°C";

        // Check if cached file is valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            $cachedData = json_decode(@file_get_contents($cacheFile), true);
            if (!empty($cachedData['temp'])) {
                return $cachedData['temp'];
            }
        }

        // Fetch fresh weather from API
        $url = 'https://api.open-meteo.com/v1/forecast?latitude=19.6969&longitude=72.7654&current=temperature_2m';
        
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3, // 3 seconds timeout
                'header' => "User-Agent: PalgharLiveNews/1.0\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['current']['temperature_2m'])) {
                $tempValue = round($data['current']['temperature_2m']) . "°C";
                // Write to cache file
                @file_put_contents($cacheFile, json_encode(['temp' => $tempValue, 'updated_at' => time()]));
                return $tempValue;
            }
        }

        // If API fails but we have stale cache, return stale cache as secondary fallback
        if (file_exists($cacheFile)) {
            $cachedData = json_decode(@file_get_contents($cacheFile), true);
            if (!empty($cachedData['temp'])) {
                return $cachedData['temp'];
            }
        }

        return $fallbackTemp;
    }

    /**
     * Compute estimated reading time based on standard 200 words-per-minute
     */
    public static function getReadingTime(string $text): string
    {
        $wordCount = str_word_count(strip_tags($text));
        $minutes = ceil($wordCount / 200);
        return $minutes . ' min read';
    }

    /**
     * Generate URL slug helper
     */
    public static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'article' : $text;
    }

    /**
     * Get absolute clean news article URL
     */
    public static function getNewsUrl(int|string $id, string $title): string
    {
        $configApp = require __DIR__ . '/Config/app.php';
        return rtrim($configApp['url'] ?? '', '/') . '/news/' . $id . '/' . self::slugify($title);
    }

    /**
     * Get absolute clean category URL
     */
    public static function getCategoryUrl(string $catId): string
    {
        $configApp = require __DIR__ . '/Config/app.php';
        return rtrim($configApp['url'] ?? '', '/') . '/category/' . urlencode($catId);
    }
}
