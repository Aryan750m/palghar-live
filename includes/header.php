<?php
// Shared View: includes/header.php
// Production-ready header template with dynamic date/weather details and SQL news ticker

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$db = getDatabaseConnection();
try {
    // Fetch latest 3 news items for the marquee ticker
    $tickerStmt = $db->query("SELECT id, title FROM news ORDER BY date_published DESC LIMIT 3");
    $tickerNews = $tickerStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in header ticker query: " . $e->getMessage());
    $tickerNews = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Palghar LIVE - The Strong Voice of the Common People | Palghar Live News Portal</title>
    <!-- SEO Meta Tags -->
    <meta name="description" content="Palghar district's fastest and most trusted digital news portal. Get local updates, monsoon forecasts, sports championships, and political news live.">
    <meta name="keywords" content="Palghar News, Palghar Live, Palghar Updates, Jawhar, Dahanu, Wada, Boisar, Vasai, Local News Maharashtra">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="<?php echo (defined('IN_ADMIN_DIR') ? '../' : ''); ?>assets/css/style.css?v=2">
</head>
<body>

    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="container top-bar-content">
            <div class="top-bar-left">
                <span id="current-date"><i class="far fa-calendar-alt"></i> <?php echo date("l, F d, Y"); ?></span>
                <span class="weather-widget"><i class="fas fa-cloud-sun-rain"></i> Palghar: <span id="weather-temp" class="weather-temp"><?php echo getWeatherTemp(); ?></span></span>
            </div>
            <div class="top-bar-right">
                <a href="https://www.facebook.com/share/1DzefDPcC2/" target="_blank" class="top-social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="https://youtube.com/@palgharlivenews/community?si=GPd0niZQMmemXHo7" target="_blank" class="top-social-link"><i class="fab fa-youtube"></i></a>
                <a href="<?php echo (defined('IN_ADMIN_DIR') ? '../' : ''); ?>index.php#contact-forms" class="top-social-link" title="Advertise With Us"><i class="fas fa-ad"></i> Advertise With Us</a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-content">
            <div class="logo-container">
                <a href="<?php echo (defined('IN_ADMIN_DIR') ? '../' : ''); ?>index.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none;">
                    <img src="<?php echo (defined('IN_ADMIN_DIR') ? '../' : ''); ?>assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Palghar LIVE Logo" class="site-logo" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
                    <div class="brand-details">
                        <h1 class="brand-title">Palghar <span>LIVE</span></h1>
                        <p class="brand-slogan">The Strong Voice of the Common People</p>
                    </div>
                </a>
            </div>

            <div class="header-actions">
                <a href="https://www.youtube.com/channel/UCk9j0UpuC0RQqp2MW9c-Y6Q" target="_blank" class="btn-live">
                    <span class="live-pulse"></span> Watch Live (YouTube)
                </a>
            </div>
        </div>
    </header>

    <!-- Breaking News Ticker -->
    <?php if (!empty($tickerNews)): ?>
    <div class="ticker-container">
        <div class="ticker-title"><i class="fas fa-bolt"></i> Breaking News</div>
        <div class="ticker-wrap">
            <div class="ticker-move" id="breaking-ticker-content">
                <?php foreach ($tickerNews as $tick): ?>
                    <div class="ticker-item"><a href="<?php echo (defined('IN_ADMIN_DIR') ? '../' : ''); ?>news-detail.php?id=<?php echo $tick['id']; ?>" style="color:#fff; text-decoration:none;"><?php echo sanitize($tick['title']); ?></a></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
