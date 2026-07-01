<?php
// search.php
// Production-grade JSON search API endpoint for Command Palette - Zero SQLi

require_once __DIR__ . '/app/autoload.php';

// Apply security and rate limits
\App\Middleware\SecurityHeaders::apply();
\App\Middleware\RateLimiter::check('public');

header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query === '') {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT id, title FROM news WHERE title LIKE ? OR summary LIKE ? ORDER BY date_published DESC LIMIT 10";
    $searchWildcard = "%" . $query . "%";
    $results = \App\Database::query($sql, [$searchWildcard, $searchWildcard]);
    echo json_encode($results);
} catch (Exception $e) {
    \App\Services\Logger::error("Search endpoint failure: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search operation failed.']);
}
