<?php
// app/Services/ThemeManager.php

namespace App\Services;

class ThemeManager {
    /**
     * Get the active theme string from cookies, defaulting to 'system'
     */
    public static function getActiveTheme(): string {
        $allowed = ['light', 'dark', 'system'];
        $theme = $_COOKIE['theme_preference'] ?? 'system';
        return in_array($theme, $allowed) ? $theme : 'system';
    }

    /**
     * Return body layout HTML attribute class based on active theme
     */
    public static function getBodyThemeAttributes(): string {
        $theme = self::getActiveTheme();
        if ($theme === 'system') {
            return 'data-theme-mode="system"';
        }
        return sprintf('data-theme="%s" data-theme-mode="fixed"', htmlspecialchars($theme));
    }
}
