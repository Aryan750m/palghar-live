<?php
// app/Services/AssetCompiler.php

namespace App\Services;

class AssetCompiler {
    /**
     * Compile and minify all style and script assets
     */
    public static function compile(): void {
        $root = dirname(__DIR__, 2);
        
        // 1. Compile CSS
        $cssFile = $root . '/assets/css/style.css';
        $minCssFile = $root . '/assets/css/style.min.css';
        
        if (file_exists($cssFile)) {
            $cssContent = file_get_contents($cssFile);
            $minCss = self::minifyCss($cssContent);
            file_put_contents($minCssFile, $minCss);
        }

        // 2. Compile JS (combines core app elements, toast module, and command search palette)
        $jsFiles = [
            $root . '/assets/js/toast.js',
            $root . '/assets/js/command_palette.js',
            $root . '/assets/js/app_v2.js'
        ];
        
        $combinedJs = "";
        foreach ($jsFiles as $jsPath) {
            if (file_exists($jsPath)) {
                $combinedJs .= file_get_contents($jsPath) . "\n";
            }
        }
        
        $minJsFile = $root . '/assets/js/app.min.js';
        if (!empty($combinedJs)) {
            $minJs = self::minifyJs($combinedJs);
            file_put_contents($minJsFile, $minJs);
        }
    }

    /**
     * Simple CSS minification helper
     */
    private static function minifyCss(string $css): string {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/*][^*]*\*+)*/!', '', $css);
        // Remove spaces around brackets and colons
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        $css = preg_replace('/\s*([\{\}:;,])\s*/', '$1', $css);
        return trim($css);
    }

    /**
     * Simple JavaScript minification helper
     */
    private static function minifyJs(string $js): string {
        // Remove single line comments (but exclude URLs)
        $js = preg_replace('~(?<!:|/)/\*.*?\*/~s', '', $js); // multi-line
        $js = preg_replace('~^[ \t]*//.*$~m', '', $js);       // single line
        
        // Remove extra spaces and line feeds
        $js = preg_replace('/\s+/', ' ', $js);
        return trim($js);
    }
}
