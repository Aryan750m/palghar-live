<?php
// app/sitemap.php
// Dynamic XML sitemap generator compliant with Google News & Google Images guidelines

require_once __DIR__ . '/app/autoload.php';

// Disable error display to avoid corrupting XML output
ini_set('display_errors', 0);

$configApp = require __DIR__ . '/app/Config/app.php';
$baseUrl = rtrim($configApp['url'] ?? 'https://palghar-live.onrender.com', '/');

try {
    $sections = \App\Database::query("SELECT id FROM sections ORDER BY id ASC");
    $articles = \App\Database::query("SELECT id, title, image_path, date_published FROM news ORDER BY date_published DESC LIMIT 100");
} catch (Exception $e) {
    \App\Services\Logger::error("Failed generating sitemap: " . $e->getMessage());
    $sections = [];
    $articles = [];
}

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
echo '        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

// 1. Static Home URL
echo "  <url>\n";
echo "    <loc>" . htmlspecialchars($baseUrl) . "/</loc>\n";
echo "    <changefreq>always</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "  </url>\n";

// 2. Section category URLs
foreach ($sections as $sec) {
    $secUrl = $baseUrl . '/category/' . urlencode($sec['id']);
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($secUrl) . "</loc>\n";
    echo "    <changefreq>hourly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

// 3. News Article URLs (News & Image sitemap mappings)
foreach ($articles as $art) {
    $artSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $art['title']), '-'));
    $artUrl = $baseUrl . '/news/' . $art['id'] . '/' . urlencode($artSlug);
    
    $pubDate = date('c', strtotime($art['date_published']));
    
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($artUrl) . "</loc>\n";
    echo "    <lastmod>" . $pubDate . "</lastmod>\n";
    
    // Google News Schema tags
    echo "    <news:news>\n";
    echo "      <news:publication>\n";
    echo "        <news:name>Palghar LIVE</news:name>\n";
    echo "        <news:language>en</news:language>\n";
    echo "      </news:publication>\n";
    echo "      <news:publication_date>" . $pubDate . "</news:publication_date>\n";
    echo "      <news:title>" . htmlspecialchars($art['title']) . "</news:title>\n";
    echo "    </news:news>\n";
    
    // Google Images tag checks
    if (!empty($art['image_path'])) {
        $imgUrl = str_starts_with($art['image_path'], 'http') ? $art['image_path'] : $baseUrl . '/' . ltrim($art['image_path'], '/');
        echo "    <image:image>\n";
        echo "      <image:loc>" . htmlspecialchars($imgUrl) . "</image:loc>\n";
        echo "      <image:title>" . htmlspecialchars($art['title']) . "</image:title>\n";
        echo "    </image:image>\n";
    }
    
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
