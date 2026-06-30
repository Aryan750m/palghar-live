<?php
// app/Services/SEOManager.php

namespace App\Services;

class SEOManager {
    private static array $config = [];

    private static function init(): void {
        if (empty(self::$config)) {
            self::$config = require __DIR__ . '/../Config/seo.php';
        }
    }

    /**
     * Get the current absolute canonical URL
     */
    public static function getCanonicalUrl(): string {
        self::init();
        $configApp = require __DIR__ . '/../Config/app.php';
        $baseUrl = $configApp['url'] ?? 'https://palghar-live.onrender.com';
        
        $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        
        // Clean routing parameter mappings for canonical URLs
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', 'news-detail.php') && isset($_GET['id'])) {
            $requestUri = '/news/' . intval($_GET['id']);
            $queryString = '';
        } elseif (str_contains($_SERVER['REQUEST_URI'] ?? '', 'index.php') && isset($_GET['cat'])) {
            $requestUri = '/category/' . htmlspecialchars($_GET['cat']);
            $queryString = '';
        }
        
        $url = rtrim($baseUrl, '/') . '/' . ltrim($requestUri, '/');
        
        if (!empty($queryString)) {
            $url .= '?' . $queryString;
        }
        
        return $url;
    }

    /**
     * Generate HTML layout string of SEO meta headers
     */
    public static function renderMetaTags(?string $title = null, ?string $description = null, ?string $keywords = null, ?string $ogImage = null): string {
        self::init();
        
        $title = $title ?: self::$config['default_title'];
        $description = $description ?: self::$config['default_description'];
        $keywords = $keywords ?: self::$config['default_keywords'];
        $ogImage = $ogImage ?: self::$config['default_og_image'];
        
        // Absolute OG Image URL check
        if (!str_starts_with($ogImage, 'http')) {
            $configApp = require __DIR__ . '/../Config/app.php';
            $ogImage = rtrim($configApp['url'] ?? 'https://palghar-live.onrender.com', '/') . '/' . ltrim($ogImage, '/');
        }
        
        $canonical = self::getCanonicalUrl();
        
        $html = [];
        $html[] = sprintf('<title>%s</title>', htmlspecialchars($title));
        $html[] = sprintf('<meta name="description" content="%s">', htmlspecialchars($description));
        $html[] = sprintf('<meta name="keywords" content="%s">', htmlspecialchars($keywords));
        $html[] = sprintf('<link rel="canonical" href="%s">', htmlspecialchars($canonical));
        
        // Hreflang support template (multilingual placeholders)
        $html[] = sprintf('<link rel="alternate" hreflang="en" href="%s">', htmlspecialchars($canonical));
        $html[] = sprintf('<link rel="alternate" hreflang="x-default" href="%s">', htmlspecialchars($canonical));
        
        // OpenGraph meta tags
        $html[] = sprintf('<meta property="og:title" content="%s">', htmlspecialchars($title));
        $html[] = sprintf('<meta property="og:description" content="%s">', htmlspecialchars($description));
        $html[] = sprintf('<meta property="og:image" content="%s">', htmlspecialchars($ogImage));
        $html[] = sprintf('<meta property="og:url" content="%s">', htmlspecialchars($canonical));
        $html[] = sprintf('<meta property="og:type" content="website">');
        $html[] = sprintf('<meta property="og:site_name" content="%s">', htmlspecialchars(self::$config['default_title']));
        
        // Twitter cards meta tags
        $html[] = sprintf('<meta name="twitter:card" content="summary_large_image">');
        $html[] = sprintf('<meta name="twitter:title" content="%s">', htmlspecialchars($title));
        $html[] = sprintf('<meta name="twitter:description" content="%s">', htmlspecialchars($description));
        $html[] = sprintf('<meta name="twitter:image" content="%s">', htmlspecialchars($ogImage));
        
        // Search Crawler instructions
        $html[] = '<meta name="robots" content="index, follow, max-image-preview:large">';
        
        return implode("\n    ", $html);
    }

    /**
     * Render structural schema markup (JSON-LD)
     */
    public static function renderSchema(string $type, array $data): string {
        self::init();
        $schema = ['@context' => 'https://schema.org'];
        
        switch ($type) {
            case 'Organization':
                $schema = array_merge($schema, [
                    '@type' => 'Organization',
                    'name' => self::$config['organisation']['name'],
                    'url' => self::$config['organisation']['url'],
                    'logo' => self::$config['organisation']['logo'],
                    'sameAs' => self::$config['organisation']['same_as'] ?? []
                ]);
                break;
                
            case 'Website':
                $configApp = require __DIR__ . '/../Config/app.php';
                $schema = array_merge($schema, [
                    '@type' => 'WebSite',
                    'name' => self::$config['organisation']['name'],
                    'url' => $configApp['url'] ?? 'https://palghar-live.onrender.com',
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ($configApp['url'] ?? 'https://palghar-live.onrender.com') . '/index.php?q={search_term_string}',
                        'query-input' => 'required name=search_term_string'
                    ]
                ]);
                break;
                
            case 'Breadcrumb':
                $items = [];
                $position = 1;
                foreach ($data as $name => $url) {
                    $items[] = [
                        '@type' => 'ListItem',
                        'position' => $position++,
                        'name' => $name,
                        'item' => $url
                    ];
                }
                $schema = array_merge($schema, [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => $items
                ]);
                break;
                
            case 'NewsArticle':
                $configApp = require __DIR__ . '/../Config/app.php';
                $baseUrl = $configApp['url'] ?? 'https://palghar-live.onrender.com';
                $imageUrl = str_starts_with($data['image'], 'http') ? $data['image'] : rtrim($baseUrl, '/') . '/' . ltrim($data['image'], '/');
                
                $schema = array_merge($schema, [
                    '@type' => 'NewsArticle',
                    'headline' => $data['title'],
                    'image' => [$imageUrl],
                    'datePublished' => date('c', strtotime($data['date_published'])),
                    'dateModified' => date('c', strtotime($data['date_published'])),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $data['author'],
                        'jobTitle' => 'Journalist'
                    ],
                    'publisher' => [
                        '@type' => 'Organization',
                        'name' => self::$config['organisation']['name'],
                        'logo' => [
                            '@type' => 'ImageObject',
                            'url' => self::$config['organisation']['logo']
                        ]
                    ],
                    'description' => $data['summary']
                ]);
                break;
        }
        
        return sprintf('<script type="application/ld+json">%s</script>', json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
