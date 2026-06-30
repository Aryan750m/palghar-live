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
     * Retrieve mock dynamic weather value based on hour of the day
     */
    public static function getWeatherTemp(): string
    {
        return (28 + (date('H') % 5)) . "°C";
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
}
