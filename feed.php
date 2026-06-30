<?php
// app/feed.php
// Dynamic RSS 2.0 Feed Generator

require_once __DIR__ . '/app/autoload.php';

ini_set('display_errors', 0);

$configApp = require __DIR__ . '/app/Config/app.php';
$baseUrl = rtrim($configApp['url'] ?? 'https://palghar-live.onrender.com', '/');

try {
    $articles = \App\Database::query("SELECT id, title, summary, date_published FROM news ORDER BY date_published DESC LIMIT 20");
} catch (Exception $e) {
    \App\Services\Logger::error("Failed generating RSS feed: " . $e->getMessage());
    $articles = [];
}

header("Content-Type: application/rss+xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
echo '  <channel>' . "\n";
echo '    <title>Palghar LIVE News</title>' . "\n";
echo '    <link>' . htmlspecialchars($baseUrl) . '/</link>' . "\n";
echo '    <description>The Strong Voice of the Common People - Live news updates from Palghar District</description>' . "\n";
echo '    <language>en-us</language>' . "\n";
echo '    <lastBuildDate>' . date(DATE_RSS) . '</lastBuildDate>' . "\n";
echo '    <atom:link href="' . htmlspecialchars($baseUrl) . '/feed.xml" rel="self" type="application/rss+xml" />' . "\n";

foreach ($articles as $art) {
    $artSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $art['title']), '-'));
    $artUrl = $baseUrl . '/news/' . $art['id'] . '/' . urlencode($artSlug);
    
    $pubDate = date(DATE_RSS, strtotime($art['date_published']));
    
    echo "    <item>\n";
    echo "      <title>" . htmlspecialchars($art['title']) . "</title>\n";
    echo "      <link>" . htmlspecialchars($artUrl) . "</link>\n";
    echo "      <guid isPermaLink=\"true\">" . htmlspecialchars($artUrl) . "</guid>\n";
    echo "      <pubDate>" . $pubDate . "</pubDate>\n";
    echo "      <description>" . htmlspecialchars($art['summary']) . "</description>\n";
    echo "    </item>\n";
}

echo '  </channel>' . "\n";
echo '</rss>' . "\n";
