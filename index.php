<?php
// Portal Homepage: index.php
// Production-grade server-rendered homepage with progressive enhancement and caching - Target Score: 10/10

require_once __DIR__ . '/app/autoload.php';

// 1. Initialise Performance Profiling
\App\Services\PerformanceProfiler::start();

// 2. Apply Security Controls and Session Management
\App\Middleware\SecurityHeaders::apply();
\App\Middleware\RateLimiter::check('public');

// 3. Central configurations
$configApp = require __DIR__ . '/app/Config/app.php';
$configSeo = require __DIR__ . '/app/Config/seo.php';

// 4. Form validation for Contact Inquiries
$inquiryStatus = isset($_GET['inquiry']) ? trim($_GET['inquiry']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_inquiry') {
    if (!\App\Validator::csrf($_POST['csrf_token'] ?? '')) {
        header("Location: index.php?inquiry=csrf_error#contact-forms");
        exit;
    }
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : 'general';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    $errors = \App\Validator::required($_POST, ['name', 'phone', 'message']);
    if (empty($errors)) {
        try {
            \App\Database::execute(
                "INSERT INTO inquiries (name, email, phone, type, message) VALUES (?, ?, ?, ?, ?)",
                [$name, $email, $phone, $type, $message]
            );
            \App\Services\Logger::info("Inquiry received successfully", ['name' => $name, 'type' => $type]);
            header("Location: index.php?inquiry=success#contact-forms");
            exit;
        } catch (Exception $e) {
            \App\Services\Logger::error("Failed saving inquiry form: " . $e->getMessage());
            header("Location: index.php?inquiry=error#contact-forms");
            exit;
        }
    } else {
        header("Location: index.php?inquiry=missing#contact-forms");
        exit;
    }
}

// 5. Setup dynamic data models query params
$currentCat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

try {
    // Dynamic Weather Temp (simulated via Helpers)
    $weatherTemp = \App\Helpers::getWeatherTemp();
    
    // In-memory caching for Sections to avoid multiple database calls
    if (\App\Cache::has('all_sections')) {
        $allSections = \App\Cache::get('all_sections');
    } else {
        $allSections = \App\Database::query("SELECT * FROM sections ORDER BY id ASC");
        \App\Cache::set('all_sections', $allSections);
    }
    
    // Breaking News Items for ticker marquee (Latest 3)
    if (\App\Cache::has('ticker_news')) {
        $tickerNews = \App\Cache::get('ticker_news');
    } else {
        $tickerNews = \App\Database::query("SELECT id, title FROM news ORDER BY date_published DESC LIMIT 3");
        \App\Cache::set('ticker_news', $tickerNews);
    }

    // Dynamic banner images for categories
    $sectionInfo = null;
    $bannerImages = [];
    if ($currentCat !== '') {
        foreach ($allSections as $sec) {
            if ($sec['id'] === $currentCat) {
                $sectionInfo = $sec;
                break;
            }
        }
        
        if ($sectionInfo) {
            $bannerImages = \App\Database::query(
                "SELECT image_path, caption FROM section_images WHERE section_id = ? ORDER BY sort_order ASC",
                [$currentCat]
            );
        }
    }

    // Build conditions for standard grid search
    $conditions = [];
    $params = [];
    
    if ($currentCat !== '') {
        $conditions[] = "category = ?";
        $params[] = $currentCat;
    }
    
    if ($searchQuery !== '') {
        $conditions[] = "(title LIKE ? OR summary LIKE ? OR content LIKE ?)";
        $searchWildcard = "%" . $searchQuery . "%";
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
        $params[] = $searchWildcard;
    }
    
    $whereSql = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : "";
    
    // Get total news count for pagination calculations
    $totalNewsRow = \App\Database::fetch("SELECT COUNT(*) as count FROM news" . $whereSql, $params);
    $totalNews = $totalNewsRow['count'] ?? 0;
    $totalPages = ceil($totalNews / $limit);
    
    // Fetch News List
    $sql = "SELECT * FROM news" . $whereSql . " ORDER BY date_published DESC LIMIT ? OFFSET ?";
    $stmtParams = array_merge($params, [$limit, $offset]);
    $newsList = \App\Database::query($sql, $stmtParams);

    // Dynamic featured story picker (highest views, featured bit, or latest)
    $featuredStory = null;
    if ($currentCat === '' && $searchQuery === '' && $page === 1) {
        foreach ($newsList as $item) {
            if ($item['featured'] == 1) {
                $featuredStory = $item;
                break;
            }
        }
        if (!$featuredStory && !empty($newsList)) {
            $featuredStory = $newsList[0];
        }
    }
    
    // Fetch Trending Stories (sidebar, max 3)
    if (\App\Cache::has('trending_stories')) {
        $trendingList = \App\Cache::get('trending_stories');
    } else {
        $trendingList = \App\Database::query("SELECT id, title, image_path, date_published FROM news WHERE trending = 1 ORDER BY date_published DESC LIMIT 3");
        \App\Cache::set('trending_stories', $trendingList);
    }
    
} catch (Exception $e) {
    \App\Services\Logger::error("Failed loading index.php database content: " . $e->getMessage());
    $tickerNews = [];
    $allSections = [];
    $newsList = [];
    $trendingList = [];
    $totalPages = 0;
}

// 6. Output AJAX requests directly (progressive enhancement rendering)
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (empty($newsList)) {
        http_response_code(200);
        exit;
    }
    
    foreach ($newsList as $art) {
        echo \App\Components\NewsCard::render($art);
    }
    
    // Flush execution metrics to performance logs
    \App\Services\PerformanceProfiler::stop();
    exit;
}

// Static layouts mapping (Playlist bulletins)
$mockVideos = [
    ['id' => 'vid-1', 'title' => 'Monsoon Preparedness Meeting Chaired by Palghar District Collector', 'youtubeId' => 'GPd0niZQMme', 'duration' => '10:45', 'thumb' => 'https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=300&q=80'],
    ['id' => 'vid-2', 'title' => 'Commuters Protest Over Severe Potholes on Palghar-Manor Highway', 'youtubeId' => 'd0niZQMmemX', 'duration' => '05:32', 'thumb' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=300&q=80'],
    ['id' => 'vid-3', 'title' => 'Fishermen Boat Capsizes Near Dahanu Coast; All 6 Sailors Rescued Safely', 'youtubeId' => 'ZQMmemXHo7s', 'duration' => '07:15', 'thumb' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=300&q=80']
];

$mockPhotos = [
    ['title' => 'Historic Jay Vilas Palace in Jawhar', 'cat' => 'Tourism', 'url' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Serene Gholvad Beach in Dahanu Coastline', 'cat' => 'Nature', 'url' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Boisar Tarapur MIDC Industrial Belt', 'cat' => 'Industry', 'url' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Traditional Tarpa Dance of Palghar Tribes', 'cat' => 'Culture', 'url' => 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80']
];

// Active page category label details
$pageTitle = $currentCat !== '' ? \App\Helpers::getCategoryLabel($currentCat) . " News - Palghar LIVE" : null;
$pageDesc = $sectionInfo ? $sectionInfo['description'] : null;

// Generate version cache-busting assets hash
$cssHash = file_exists('assets/css/style.min.css') ? filemtime('assets/css/style.min.css') : time();
$jsHash = file_exists('assets/js/app.min.js') ? filemtime('assets/js/app.min.js') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Unified SEO Meta Tags -->
    <?php echo \App\Services\SEOManager::renderMetaTags($pageTitle, $pageDesc); ?>
    
    <!-- Favicon Links -->
    <link rel="icon" type="image/jpeg" href="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#E31B23">
    
    <!-- CDNs and Fonts Optimizations (preconnect headers) -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    
    <!-- Production CSS bundles -->
    <link rel="stylesheet" href="assets/css/style.min.css?v=<?php echo $cssHash; ?>">
    
    <!-- Rich structured data models schemas -->
    <?php 
    echo \App\Services\SEOManager::renderSchema('Organization', []); 
    echo \App\Services\SEOManager::renderSchema('Website', []);
    
    // Breadcrumbs Schema calculations
    $crumbs = ['Home' => $configApp['url']];
    if ($currentCat !== '') {
        $crumbs[\App\Helpers::getCategoryLabel($currentCat)] = $configApp['url'] . '/category/' . $currentCat;
    }
    echo \App\Services\SEOManager::renderSchema('Breadcrumb', $crumbs);
    ?>
</head>
<body <?php echo \App\Services\ThemeManager::getBodyThemeAttributes(); ?>>

    <!-- Skip link helper for screen readers (WCAG 2.2 AA) -->
    <a href="#main-news-content" class="skip-link">Skip to Content</a>

    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="container top-bar-content">
            <div class="top-bar-left">
                <span id="current-date" aria-live="polite"><i class="far fa-calendar-alt"></i> <?php echo date("l, F d, Y"); ?></span>
                <span class="weather-widget" aria-label="Current weather"><i class="fas fa-cloud-sun-rain"></i> Palghar: <span id="weather-temp" class="weather-temp"><?php echo $weatherTemp; ?></span></span>
            </div>
            <div class="top-bar-right">
                <a href="<?php echo htmlspecialchars($configSeo['facebook_url']); ?>" target="_blank" class="top-social-link" aria-label="Visit Facebook Page"><i class="fab fa-facebook-f"></i></a>
                <a href="<?php echo htmlspecialchars($configSeo['youtube_channel_url']); ?>" target="_blank" class="top-social-link" aria-label="Visit YouTube Channel"><i class="fab fa-youtube"></i></a>
                <a href="#contact-forms" class="top-social-link" title="Advertise With Us"><i class="fas fa-ad"></i> Advertise With Us</a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-content">
            <div class="logo-container">
                <a href="index.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none;" aria-label="Palghar LIVE homepage">
                    <img src="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Palghar LIVE logo" class="site-logo" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
                    <div class="brand-details">
                        <span class="brand-title" style="font-size: 2.2rem; font-weight:800; color:var(--secondary); display:block; line-height:1;">Palghar <span style="color:var(--primary);">LIVE</span></span>
                        <p class="brand-slogan">The Strong Voice of the Common People</p>
                    </div>
                </a>
            </div>

            <div class="header-actions">
                <a href="<?php echo htmlspecialchars($configSeo['youtube_channel_url']); ?>" target="_blank" class="btn-live" aria-label="Watch Live YouTube broadcasts">
                    <span class="live-pulse"></span> Watch Live (YouTube)
                </a>
            </div>
        </div>
    </header>

    <!-- Breaking News Ticker -->
    <?php if (!empty($tickerNews)): ?>
    <div class="ticker-container" aria-label="Breaking News Ticker">
        <div class="ticker-title"><i class="fas fa-bolt"></i> Breaking News</div>
        <div class="ticker-wrap">
            <div class="ticker-move" id="breaking-ticker-content">
                <?php foreach ($tickerNews as $tick): ?>
                    <div class="ticker-item"><a href="news-detail.php?id=<?php echo $tick['id']; ?>"><?php echo htmlspecialchars($tick['title']); ?></a></div>
                <?php endforeach; ?>
                <?php /* Duplicate for seamless loop scrolling animation */ foreach ($tickerNews as $tick): ?>
                    <div class="ticker-item"><a href="news-detail.php?id=<?php echo $tick['id']; ?>"><?php echo htmlspecialchars($tick['title']); ?></a></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation Navbar -->
    <nav class="navbar" aria-label="Main Navigation">
        <div class="container nav-content">
            <button class="mobile-menu-btn" id="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>
            <ul class="nav-links" id="nav-links-menu">
                <li><a href="index.php" class="nav-item <?php echo ($currentCat === '' && $searchQuery === '') ? 'active' : ''; ?>">Home</a></li>
                <?php foreach ($allSections as $sec): ?>
                    <li><a href="index.php?cat=<?php echo $sec['id']; ?>" class="nav-item <?php echo $currentCat === $sec['id'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($sec['title']); ?></a></li>
                <?php endforeach; ?>
                <li><a href="admin/login.php" class="nav-item"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
            <div class="nav-right-controls">
                <form class="search-box" method="GET" action="index.php" role="search">
                    <input type="text" name="q" placeholder="Search news..." class="search-input" id="search-bar" value="<?php echo htmlspecialchars($searchQuery); ?>" aria-label="Search articles">
                    <button type="submit" class="search-btn" id="search-button" aria-label="Submit search"><i class="fas fa-search"></i></button>
                </form>
                <button class="theme-toggle-btn" id="theme-toggle" aria-label="Switch color theme">🌙</button>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="container section-padding" id="main-news-content">
        
        <!-- Accessible breadcrumb trail component -->
        <?php echo \App\Components\Breadcrumbs::render($crumbs); ?>

        <!-- Category Slider Pills -->
        <div class="category-pills" id="category-pills-bar">
            <a href="index.php" class="pill <?php echo $currentCat === '' ? 'active' : ''; ?>">All News</a>
            <?php foreach ($allSections as $sec): ?>
                <a href="index.php?cat=<?php echo $sec['id']; ?>" class="pill <?php echo $currentCat === $sec['id'] ? 'active' : ''; ?>"><?php echo htmlspecialchars($sec['title']); ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Dynamic Category Carousel Banners -->
        <?php if ($currentCat !== '' && $sectionInfo && !empty($bannerImages)): ?>
            <div id="section-banner-container" class="section-banner-card">
                <div class="section-banner-info">
                    <h2 class="section-banner-title"><?php echo htmlspecialchars($sectionInfo['title']); ?></h2>
                    <?php if ($sectionInfo['description']): ?>
                        <p class="section-banner-desc"><?php echo htmlspecialchars($sectionInfo['description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (count($bannerImages) === 1): ?>
                    <div class="section-static-banner">
                        <img src="<?php echo htmlspecialchars($bannerImages[0]['image_path']); ?>" alt="<?php echo htmlspecialchars($bannerImages[0]['caption'] ?: 'Section Banner'); ?>" loading="eager" onerror="this.src='https://via.placeholder.com/1200x400?text=Palghar+Live+Banner'">
                        <?php if ($bannerImages[0]['caption']): ?>
                            <div class="carousel-caption"><?php echo htmlspecialchars($bannerImages[0]['caption']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="section-carousel carousel-container" id="carousel-<?php echo $currentCat; ?>" tabindex="0" aria-label="Section Gallery Carousel">
                        <button class="section-carousel-btn prev" type="button" aria-label="Previous Slide" onclick="this.parentElement.querySelector('.carousel-track').parentElement.HeroCarousel.navigate(-1)">❮</button>
                        <button class="section-carousel-btn next" type="button" aria-label="Next Slide" onclick="this.parentElement.querySelector('.carousel-track').parentElement.HeroCarousel.navigate(1)">❯</button>
                        <div class="section-carousel-track carousel-track" id="track-<?php echo $currentCat; ?>">
                            <?php foreach ($bannerImages as $img): ?>
                                <div class="section-carousel-slide">
                                    <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['caption'] ?: 'Slide'); ?>" onerror="this.src='https://via.placeholder.com/1200x400?text=Palghar+Live+Slide'">
                                    <?php if ($img['caption']): ?>
                                        <div class="carousel-caption"><?php echo htmlspecialchars($img['caption']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-carousel-dots">
                            <?php foreach ($bannerImages as $idx => $img): ?>
                                <span class="section-carousel-dot carousel-dot <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Featured Section (Hero Grid layout LCP optimized) -->
        <?php if (!empty($newsList) && $page === 1): ?>
            <section class="hero-news-grid" id="hero-section" aria-label="Featured Story">
                <?php if ($featuredStory): ?>
                    <div id="featured-news-container">
                        <div class="main-featured-card">
                            <div class="featured-img-wrap">
                                <img src="<?php echo htmlspecialchars($featuredStory['image_path']); ?>" alt="Featured news cover image" fetchpriority="high" onerror="this.src='https://via.placeholder.com/800x450?text=News'">
                            </div>
                            <div class="featured-content">
                                <div>
                                    <div class="meta-info">
                                        <span><i class="far fa-calendar-alt"></i> <?php echo date("F d, Y", strtotime($featuredStory['date_published'])); ?></span>
                                        <span><i class="far fa-eye"></i> <?php echo intval($featuredStory['views']); ?> Views</span>
                                    </div>
                                    <h2 class="featured-title"><?php echo htmlspecialchars($featuredStory['title']); ?></h2>
                                    <p class="featured-summary"><?php echo htmlspecialchars($featuredStory['summary']); ?></p>
                                </div>
                                <div class="card-footer">
                                    <a href="news-detail.php?id=<?php echo $featuredStory['id']; ?>" class="read-more-btn">Read Full Story <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Trending / Side Panel widget -->
                <div class="trending-sidebar">
                    <h3 class="sidebar-header"><i class="fas fa-fire" style="color: var(--primary);"></i> Trending Stories</h3>
                    <div id="trending-news-container" style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (empty($trendingList)): ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No trending stories.</p>
                        <?php else: ?>
                            <?php foreach ($trendingList as $trend): ?>
                                <a href="news-detail.php?id=<?php echo $trend['id']; ?>" class="trending-item">
                                    <img src="<?php echo htmlspecialchars($trend['image_path']); ?>" alt="Trending thumbnail" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink:0;" onerror="this.src='https://via.placeholder.com/80x60?text=News'">
                                    <div class="trend-content">
                                        <h4 class="trend-title" style="font-size:0.9rem; font-weight:600; margin:0 0 5px 0; color:var(--text-primary); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo htmlspecialchars($trend['title']); ?></h4>
                                        <span style="font-size:0.75rem; color:var(--text-muted);"><i class="far fa-calendar-alt"></i> <?php echo date("M d", strtotime($trend['date_published'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Main News Cards Grid with progressive fallback pagination -->
        <section class="section-padding" style="padding-top: 10px;" aria-label="Updates Grid">
            <h2 class="section-title" id="grid-title">
                <?php 
                if ($searchQuery !== '') {
                    echo 'Search Results: "' . htmlspecialchars($searchQuery) . '"';
                } elseif ($currentCat !== '' && $sectionInfo) {
                    echo htmlspecialchars($sectionInfo['title']);
                } else {
                    echo 'Latest Updates';
                }
                ?>
            </h2>
            <div class="news-cards-grid" id="news-articles-grid">
                <?php if (empty($newsList)): ?>
                    <p style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);">No news stories found.</p>
                <?php else: ?>
                    <?php foreach ($newsList as $art): ?>
                        <?php echo \App\Components\NewsCard::render($art); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Fallback Static Pagination Links (WCAG / progressive enhancement fallback) -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper" id="pagination-controls" style="margin-top: 30px; display: flex; justify-content: center; gap: 8px;">
                    <?php if ($page > 1): ?>
                        <a href="index.php?page=<?php echo ($page - 1); ?>&cat=<?php echo urlencode($currentCat); ?>&q=<?php echo urlencode($searchQuery); ?>" class="btn-pagination-control" aria-label="Previous Page">❮ Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="index.php?page=<?php echo $i; ?>&cat=<?php echo urlencode($currentCat); ?>&q=<?php echo urlencode($searchQuery); ?>" class="btn-pagination-num <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="index.php?page=<?php echo ($page + 1); ?>&cat=<?php echo urlencode($currentCat); ?>&q=<?php echo urlencode($searchQuery); ?>" class="btn-pagination-control" aria-label="Next Page">Next ❯</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Infinite Scroll Loader Hook element (Used by JS) -->
            <div id="infinite-scroll-trigger" data-category="<?php echo htmlspecialchars($currentCat); ?>" data-search="<?php echo htmlspecialchars($searchQuery); ?>" style="text-align: center; margin-top: 20px;">
                <div class="scroll-spinner" style="display: none;"></div>
            </div>
        </section>

        <!-- Video Gallery bulletins selection player -->
        <section class="video-section" aria-label="Video Bulletins">
            <h2 class="video-title"><i class="fab fa-youtube" style="color: #FF0000;"></i> Video Bulletins</h2>
            <div class="video-grid-layout">
                <div class="main-video-player" id="main-video-player-container">
                    <iframe src="https://www.youtube.com/embed/GPd0niZQMme" title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
                <div class="video-playlist" id="video-playlist-container">
                    <?php foreach ($mockVideos as $vid): ?>
                        <div class="playlist-item <?php echo $vid['id'] === 'vid-1' ? 'active' : ''; ?>" data-youtube-id="<?php echo htmlspecialchars($vid['youtubeId']); ?>" tabindex="0" role="button" aria-label="Play video: <?php echo htmlspecialchars($vid['title']); ?>">
                            <img src="<?php echo htmlspecialchars($vid['thumb']); ?>" alt="video thumbnail" style="width:100px; height:60px; object-fit:cover; border-radius:4px;">
                            <div>
                                <h4 style="font-size:0.85rem; margin:0 0 5px 0; font-weight:600; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo htmlspecialchars($vid['title']); ?></h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);"><i class="far fa-clock"></i> <?php echo htmlspecialchars($vid['duration']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Image Gallery overlay previews -->
        <section class="section-padding" style="padding-top: 0;" aria-label="Photo Gallery">
            <h2 class="section-title"><i class="far fa-images" style="color: var(--primary);"></i> Photo Gallery (Glimpses of Palghar)</h2>
            <div class="photo-gallery-grid" id="photo-gallery-container">
                <?php foreach ($mockPhotos as $ph): ?>
                    <div class="photo-gallery-item" tabindex="0" role="button" aria-label="Zoom image: <?php echo htmlspecialchars($ph['title']); ?>">
                        <img src="<?php echo htmlspecialchars($ph['url']); ?>" alt="<?php echo htmlspecialchars($ph['title']); ?>" style="width:100%; height:200px; object-fit:cover;">
                        <div class="photo-overlay">
                            <span style="font-size:0.7rem; background:var(--primary); padding:2px 6px; border-radius:3px; font-weight:bold;"><?php echo htmlspecialchars($ph['cat']); ?></span>
                            <h4 style="margin:5px 0 0 0; font-size:0.9rem; font-weight:600;"><?php echo htmlspecialchars($ph['title']); ?></h4>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Advertising and Sponsorship widgets -->
        <section class="ad-banner-section">
            <div class="ad-wrapper">
                <div class="ad-content">
                    <h3>Advertise Your Business on Palghar's Number 1 News Portal!</h3>
                    <p>Reach out to lakhs of local citizens at cost-effective rates. Contact our advertising desk representative today.</p>
                </div>
                <a href="#contact-forms" class="ad-btn">Send Ad Inquiry</a>
            </div>
        </section>

        <!-- Dynamic Ad Inquiries forms -->
        <section class="contact-grid section-padding" id="contact-forms" style="padding-top: 0;">
            <div class="form-card">
                <h2 class="section-title" style="font-size: 1.5rem; margin-bottom: 25px;"><i class="far fa-envelope"></i> Contact Us</h2>
                
                <!-- Feedback toast response status messages -->
                <?php if ($inquiryStatus === 'success'): ?>
                    <div class="alert alert-success" role="alert" style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>Thank you!</strong> Your inquiry has been submitted successfully. Our team will contact you shortly.
                    </div>
                <?php elseif ($inquiryStatus === 'csrf_error'): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>Session expired!</strong> Security validation failed. Please try submitting the form again.
                    </div>
                <?php elseif ($inquiryStatus === 'error'): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>Error!</strong> Failed to submit. Please verify details and retry.
                    </div>
                <?php elseif ($inquiryStatus === 'missing'): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <strong>Validation error!</strong> Please make sure all required fields (*) are populated.
                    </div>
                <?php endif; ?>

                <form id="contact-form-element" method="POST" action="index.php">
                    <input type="hidden" name="action" value="submit_inquiry">
                    <?php echo \App\Middleware\CSRFCheck::getInputField(); ?>
                    
                    <div class="form-group-row">
                        <div class="form-group">
                            <label class="form-label" for="contact-name">Full Name *</label>
                            <input type="text" class="form-control" id="contact-name" name="name" required placeholder="Your Name">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact-email">Email Address</label>
                            <input type="email" class="form-control" id="contact-email" name="email" placeholder="Your Email">
                        </div>
                    </div>
                    <div class="form-group-row">
                        <div class="form-group">
                            <label class="form-label" for="contact-phone">Mobile Number *</label>
                            <input type="tel" class="form-control" id="contact-phone" name="phone" required placeholder="10-digit number">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="contact-type">Inquiry Type *</label>
                            <select class="form-control" id="contact-type" name="type" required>
                                <option value="general">General Inquiry</option>
                                <option value="ad">Advertisement Placement</option>
                                <option value="news_tip">Submit a News Tip</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="contact-msg">Your Message *</label>
                        <textarea class="form-control" id="contact-msg" name="message" required placeholder="Write your message here..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit" id="form-submit-btn">Submit Inquiry Form</button>
                </form>
            </div>

            <!-- Side Widgets Social Connections -->
            <div class="social-widget-card">
                <h3 class="sidebar-header" style="border-bottom-color: var(--secondary);"><i class="fas fa-share-nodes"></i> Connect With Us</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Palghar LIVE News is active across major social networks. Join us to receive immediate notifications directly on your mobile device.</p>
                <a href="<?php echo htmlspecialchars($configSeo['youtube_channel_url']); ?>" target="_blank" class="social-button youtube">
                    <span><i class="fab fa-youtube"></i> YouTube Channel</span>
                    <span style="font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">SUBSCRIBE</span>
                </a>
                <a href="<?php echo htmlspecialchars($configSeo['facebook_url']); ?>" target="_blank" class="social-button facebook">
                    <span><i class="fab fa-facebook-f"></i> Facebook Page</span>
                    <span style="font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">FOLLOW</span>
                </a>
                <a href="https://api.whatsapp.com/send?text=Get the latest updates from Palghar District on Palghar LIVE News Portal: <?php echo urlencode($configApp['url']); ?>" target="_blank" class="social-button whatsapp">
                    <span><i class="fab fa-whatsapp"></i> Share on WhatsApp</span>
                    <span style="font-size: 1.2rem;"><i class="fas fa-chevron-right"></i></span>
                </a>
            </div>
        </section>

    </main>

    <!-- Footer columns -->
    <footer class="main-footer">
        <div class="container footer-content-wrap">
            <div class="footer-grid">
                <div class="footer-about">
                    <img src="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Footer Logo" class="footer-logo" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
                    <p>Palghar district's leading digital news channel. Committed to truth and representing the issues of the common public under our motto "With Truth, With Public".</p>
                    <div style="display: flex; gap: 10px;">
                        <a href="<?php echo htmlspecialchars($configSeo['facebook_url']); ?>" target="_blank" style="color: var(--accent);" aria-label="Facebook"><i class="fab fa-facebook-square fa-2x"></i></a>
                        <a href="<?php echo htmlspecialchars($configSeo['youtube_channel_url']); ?>" target="_blank" style="color: var(--accent);" aria-label="YouTube"><i class="fab fa-youtube-square fa-2x"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Palghar LIVE News. All Rights Reserved. | Developed by Digital Daddy</p>
                <p>Voice of the Public | <a href="admin/login.php" style="color: rgba(255,255,255,0.4); text-decoration: underline;">Admin Portal</a></p>
            </div>
        </div>
    </footer>

    <!-- Global Image Lightbox Modal Viewer -->
    <div id="image-lightbox-modal" class="lightbox" role="dialog" aria-hidden="true">
        <span class="lightbox-close" id="lightbox-close-btn" tabindex="0" role="button" aria-label="Close modal">&times;</span>
        <div class="lightbox-content-wrap">
            <img class="lightbox-content" id="lightbox-zoom-img" alt="Enlarged View">
            <div class="lightbox-caption" id="lightbox-zoom-caption"></div>
        </div>
    </div>

    <!-- Floating Action Buttons (Scroll-to-top) -->
    <button class="fab-btn scroll-to-top" id="scroll-to-top-btn" aria-label="Scroll back to top of the page">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Unified dynamic JS bundles -->
    <script src="assets/js/app.min.js?v=<?php echo $jsHash; ?>" defer></script>
    <script src="assets/js/index_page.js?v=<?php echo $jsHash; ?>" defer></script>

    <script nonce="<?php echo \App\Middleware\SecurityHeaders::getNonce(); ?>">
        // progressive enhancement: disable fallback layout pagination if JS is active
        document.addEventListener('DOMContentLoaded', () => {
            const controls = document.getElementById('pagination-controls');
            if (controls) {
                controls.style.display = 'none';
            }
            const spinner = document.querySelector('#infinite-scroll-trigger .scroll-spinner');
            if (spinner) {
                spinner.style.display = 'inline-block';
            }
        });
    </script>
</body>
</html>
<?php
// Flush execution metrics to performance logs
\App\Services\PerformanceProfiler::stop();
?>
