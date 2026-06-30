<?php
// Portal Homepage: index.php
// Production-ready server-rendered homepage using PHP & MySQL - Phase 1

require_once 'config/db.php';
require_once 'includes/functions.php';

$db = getDatabaseConnection();

// =========================================================================
// 1. Post/Redirect/Get (PRG) Form Handler for Contact Inquiries
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_inquiry') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : 'general';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if ($name !== '' && $phone !== '' && $message !== '') {
        try {
            $stmt = $db->prepare("INSERT INTO inquiries (name, email, phone, type, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $type, $message]);
            
            // Redirect to prevent resubmission (PRG Pattern)
            header("Location: index.php?inquiry=success#contact-forms");
            exit;
        } catch (PDOException $e) {
            error_log("Failed saving inquiry: " . $e->getMessage());
            header("Location: index.php?inquiry=error#contact-forms");
            exit;
        }
    } else {
        header("Location: index.php?inquiry=missing#contact-forms");
        exit;
    }
}

// =========================================================================
// 2. Data Fetching
// =========================================================================
$currentCat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    // A. Fetch dynamic breaking news ticker items (latest 3)
    $tickerStmt = $db->query("SELECT id, title FROM news ORDER BY date_published DESC LIMIT 3");
    $tickerNews = $tickerStmt->fetchAll();
    
    // B. Fetch all category sections
    $secStmt = $db->query("SELECT * FROM sections ORDER BY id ASC");
    $allSections = $secStmt->fetchAll();
    
    // C. Fetch banner description and images list if category selected
    $sectionInfo = null;
    $bannerImages = [];
    if ($currentCat !== '') {
        $secInfoStmt = $db->prepare("SELECT * FROM sections WHERE id = ?");
        $secInfoStmt->execute([$currentCat]);
        $sectionInfo = $secInfoStmt->fetch();
        
        if ($sectionInfo) {
            $imgStmt = $db->prepare("SELECT image_path, caption FROM section_images WHERE section_id = ? ORDER BY sort_order ASC");
            $imgStmt->execute([$currentCat]);
            $bannerImages = $imgStmt->fetchAll();
        }
    }
    
    // D. Build news query parameters
    $query = "SELECT * FROM news";
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
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Order by latest published news
    $query .= " ORDER BY date_published DESC";
    
    $newsStmt = $db->prepare($query);
    $newsStmt->execute($params);
    $newsList = $newsStmt->fetchAll();
    
    // E. Extract featured story (First article flagged featured, or falls back to latest)
    $featuredStory = null;
    if ($currentCat === '' && $searchQuery === '') {
        foreach ($newsList as $item) {
            if ($item['featured'] == 1) {
                $featuredStory = $item;
                break;
            }
        }
    }
    if (!$featuredStory && !empty($newsList)) {
        $featuredStory = $newsList[0];
    }
    
    // F. Fetch trending stories (max 3)
    $trendStmt = $db->query("SELECT id, title, image_path, date_published FROM news WHERE trending = 1 ORDER BY date_published DESC LIMIT 3");
    $trendingList = $trendStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in index.php preload: " . $e->getMessage());
    $tickerNews = [];
    $allSections = [];
    $newsList = [];
    $trendingList = [];
}

// Mock Video playlist data
$mockVideos = [
    ['id' => 'vid-1', 'title' => 'Monsoon Preparedness Meeting Chaired by Palghar District Collector', 'youtubeId' => 'GPd0niZQMme', 'duration' => '10:45', 'thumb' => 'https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=300&q=80'],
    ['id' => 'vid-2', 'title' => 'Commuters Protest Over Severe Potholes on Palghar-Manor Highway', 'youtubeId' => 'd0niZQMmemX', 'duration' => '05:32', 'thumb' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=300&q=80'],
    ['id' => 'vid-3', 'title' => 'Fishermen Boat Capsizes Near Dahanu Coast; All 6 Sailors Rescued Safely', 'youtubeId' => 'ZQMmemXHo7s', 'duration' => '07:15', 'thumb' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=300&q=80']
];

// Mock Photo gallery data
$mockPhotos = [
    ['title' => 'Historic Jay Vilas Palace in Jawhar', 'cat' => 'Tourism', 'url' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Serene Gholvad Beach in Dahanu Coastline', 'cat' => 'Nature', 'url' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Boisar Tarapur MIDC Industrial Belt', 'cat' => 'Industry', 'url' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80'],
    ['title' => 'Traditional Tarpa Dance of Palghar Tribes', 'cat' => 'Culture', 'url' => 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80']
];
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
    <link rel="stylesheet" href="assets/css/style.css?v=2">
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
                <a href="#contact-forms" class="top-social-link" title="Advertise With Us"><i class="fas fa-ad"></i> Advertise With Us</a>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="container header-content">
            <div class="logo-container">
                <a href="index.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none;">
                    <img src="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Palghar LIVE Logo" class="site-logo" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
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
                    <div class="ticker-item"><a href="news-detail.php?id=<?php echo $tick['id']; ?>" style="color:#fff; text-decoration:none;"><?php echo sanitize($tick['title']); ?></a></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation Navbar -->
    <nav class="navbar">
        <div class="container nav-content">
            <button class="mobile-menu-btn" id="mobile-menu-toggle">☰</button>
            <ul class="nav-links" id="nav-links-menu">
                <li><a href="index.php" class="nav-item <?php echo ($currentCat === '' && $searchQuery === '') ? 'active' : ''; ?>">Home</a></li>
                <?php foreach ($allSections as $sec): ?>
                    <li><a href="index.php?cat=<?php echo $sec['id']; ?>" class="nav-item <?php echo $currentCat === $sec['id'] ? 'active' : ''; ?>"><?php echo sanitize($sec['title']); ?></a></li>
                <?php endforeach; ?>
                <li><a href="admin/login.php" class="nav-item"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
            <div class="nav-right-controls">
                <form class="search-box" method="GET" action="index.php">
                    <input type="text" name="q" placeholder="Search news..." class="search-input" id="search-bar" value="<?php echo sanitize($searchQuery); ?>">
                    <button type="submit" class="search-btn" id="search-button"><i class="fas fa-search"></i></button>
                </form>
                <button class="theme-toggle-btn" id="theme-toggle">🌙</button>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="container section-padding">

        <!-- Category Slider Pills -->
        <div class="category-pills" id="category-pills-bar">
            <a href="index.php" class="pill <?php echo $currentCat === '' ? 'active' : ''; ?>" style="text-decoration:none; display:inline-block; line-height:1.2;">All News</a>
            <?php foreach ($allSections as $sec): ?>
                <a href="index.php?cat=<?php echo $sec['id']; ?>" class="pill <?php echo $currentCat === $sec['id'] ? 'active' : ''; ?>" style="text-decoration:none; display:inline-block; line-height:1.2;"><?php echo sanitize($sec['title']); ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Section Banner Container (Dynamic Slider/Image) -->
        <?php if ($currentCat !== '' && $sectionInfo && !empty($bannerImages)): ?>
            <div id="section-banner-container" class="section-banner-card" style="display: block; margin-bottom: 25px;">
                <div class="section-banner-info">
                    <h2 class="section-banner-title"><?php echo sanitize($sectionInfo['title']); ?></h2>
                    <?php if ($sectionInfo['description']): ?>
                        <p class="section-banner-desc"><?php echo sanitize($sectionInfo['description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (count($bannerImages) === 1): ?>
                    <!-- Single Image Render -->
                    <div class="section-static-banner">
                        <img src="<?php echo $bannerImages[0]['image_path']; ?>" alt="<?php echo sanitize($bannerImages[0]['caption'] ?: 'Section Banner'); ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/1200x400?text=Palghar+Live+Banner'">
                        <?php if ($bannerImages[0]['caption']): ?>
                            <div class="carousel-caption"><?php echo sanitize($bannerImages[0]['caption']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Responsive Slider/Carousel Render (Interactive Slider class initialized in JS) -->
                    <div class="section-carousel" id="carousel-<?php echo $currentCat; ?>" tabindex="0">
                        <button class="section-carousel-btn prev" type="button" aria-label="Previous Slide">❮</button>
                        <button class="section-carousel-btn next" type="button" aria-label="Next Slide">❯</button>
                        <div class="section-carousel-track" id="track-<?php echo $currentCat; ?>">
                            <?php foreach ($bannerImages as $img): ?>
                                <div class="section-carousel-slide">
                                    <img src="<?php echo $img['image_path']; ?>" alt="<?php echo sanitize($img['caption'] ?: 'Slide'); ?>" loading="lazy" onerror="this.src='https://via.placeholder.com/1200x400?text=Palghar+Live+Slide'">
                                    <?php if ($img['caption']): ?>
                                        <div class="carousel-caption"><?php echo sanitize($img['caption']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="section-carousel-dots">
                            <?php foreach ($bannerImages as $idx => $img): ?>
                                <span class="section-carousel-dot <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $idx; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Featured Section (Hero Grid) -->
        <?php if (!empty($newsList)): ?>
            <section class="hero-news-grid" id="hero-section">
                <!-- Large Featured Card -->
                <?php if ($featuredStory): ?>
                    <div id="featured-news-container">
                        <div class="main-featured-card">
                            <div class="featured-img-wrap">
                                <img src="<?php echo $featuredStory['image_path']; ?>" alt="News Image" onerror="this.src='https://via.placeholder.com/800x450?text=News'">
                            </div>
                            <div class="featured-content">
                                <div>
                                    <div class="meta-info">
                                        <span><i class="far fa-calendar-alt"></i> <?php echo date("F d, Y", strtotime($featuredStory['date_published'])); ?></span>
                                        <span><i class="far fa-eye"></i> <?php echo $featuredStory['views']; ?> Readers</span>
                                    </div>
                                    <h2 class="featured-title"><?php echo sanitize($featuredStory['title']); ?></h2>
                                    <p class="featured-summary"><?php echo sanitize($featuredStory['summary']); ?></p>
                                </div>
                                <div class="card-footer">
                                    <a href="news-detail.php?id=<?php echo $featuredStory['id']; ?>" class="read-more-btn">Read Full Story <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Trending / Side Panel -->
                <div class="trending-sidebar">
                    <h3 class="sidebar-header"><i class="fas fa-fire" style="color: var(--primary);"></i> Trending Stories</h3>
                    <div id="trending-news-container" style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (empty($trendingList)): ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No trending stories.</p>
                        <?php else: ?>
                            <?php foreach ($trendingList as $trend): ?>
                                <a href="news-detail.php?id=<?php echo $trend['id']; ?>" class="trending-item" style="text-decoration:none; display:flex; gap:15px; align-items:center;">
                                    <img src="<?php echo $trend['image_path']; ?>" alt="thumb" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; flex-shrink:0;" onerror="this.src='https://via.placeholder.com/80x60?text=News'">
                                    <div class="trend-content">
                                        <h4 class="trend-title" style="font-size:0.9rem; font-weight:600; margin:0 0 5px 0; color:var(--text-primary); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo sanitize($trend['title']); ?></h4>
                                        <span style="font-size:0.75rem; color:var(--text-muted);"><i class="far fa-calendar-alt"></i> <?php echo date("M d", strtotime($trend['date_published'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Main News Cards Grid -->
        <section class="section-padding" style="padding-top: 10px;">
            <h2 class="section-title" id="grid-title">
                <?php 
                if ($searchQuery !== '') {
                    echo 'Search Results: "' . sanitize($searchQuery) . '"';
                } elseif ($currentCat !== '' && $sectionInfo) {
                    echo sanitize($sectionInfo['title']);
                } else {
                    echo 'Latest Updates';
                }
                ?>
            </h2>
            <div class="news-cards-grid" id="news-grid-container">
                <?php if (empty($newsList)): ?>
                    <p style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-muted);">No news stories found.</p>
                <?php else: ?>
                    <?php foreach ($newsList as $art): ?>
                        <div class="news-card">
                            <div class="card-img-wrap">
                                <img src="<?php echo $art['image_path']; ?>" alt="<?php echo sanitize($art['title']); ?>" onerror="this.src='https://via.placeholder.com/400x250?text=News'">
                            </div>
                            <div class="card-body">
                                <div class="meta-info">
                                    <span><i class="far fa-calendar-alt"></i> <?php echo date("M d, Y", strtotime($art['date_published'])); ?></span>
                                    <span><i class="far fa-eye"></i> <?php echo $art['views']; ?> Readers</span>
                                </div>
                                <h3 class="card-title" style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:42px;"><?php echo sanitize($art['title']); ?></h3>
                                <p class="card-summary" style="display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; min-height:54px;"><?php echo sanitize($art['summary']); ?></p>
                            </div>
                            <div class="card-footer">
                                <a href="news-detail.php?id=<?php echo $art['id']; ?>" class="read-more-btn">Read Full Story <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Video Gallery Section (Palghar Live Youtube) -->
        <section class="video-section">
            <h2 class="video-title"><i class="fab fa-youtube" style="color: #FF0000;"></i> Video Bulletins</h2>
            <div class="video-grid-layout">
                <!-- Video Player -->
                <div class="main-video-player" id="main-video-player-container">
                    <iframe src="https://www.youtube.com/embed/GPd0niZQMme" title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
                <!-- Playlist selection -->
                <div class="video-playlist" id="video-playlist-container">
                    <?php foreach ($mockVideos as $vid): ?>
                        <div class="playlist-item <?php echo $vid['id'] === 'vid-1' ? 'active' : ''; ?>" data-youtube-id="<?php echo $vid['youtubeId']; ?>" style="display:flex; gap:10px; padding:10px; cursor:pointer;">
                            <img src="<?php echo $vid['thumb']; ?>" alt="video thumb" style="width:100px; height:60px; object-fit:cover; border-radius:4px;">
                            <div>
                                <h4 style="font-size:0.85rem; margin:0 0 5px 0; font-weight:600; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo sanitize($vid['title']); ?></h4>
                                <span style="font-size:0.75rem; color:var(--text-muted);"><i class="far fa-clock"></i> <?php echo $vid['duration']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Photo Gallery Section -->
        <section class="section-padding" style="padding-top: 0;">
            <h2 class="section-title"><i class="far fa-images" style="color: var(--primary);"></i> Photo Gallery (Glimpses of Palghar)</h2>
            <div class="photo-gallery-grid" id="photo-gallery-container">
                <?php foreach ($mockPhotos as $ph): ?>
                    <div class="photo-gallery-item" style="position:relative; cursor:pointer; overflow:hidden; border-radius:8px;">
                        <img src="<?php echo $ph['url']; ?>" alt="<?php echo sanitize($ph['title']); ?>" style="width:100%; height:200px; object-fit:cover; transition:transform 0.3s;">
                        <div class="photo-overlay" style="position:absolute; bottom:0; left:0; right:0; background:linear-gradient(transparent, rgba(0,0,0,0.8)); padding:15px; color:#fff;">
                            <span style="font-size:0.7rem; background:var(--primary); padding:2px 6px; border-radius:3px; font-weight:bold;"><?php echo sanitize($ph['cat']); ?></span>
                            <h4 style="margin:5px 0 0 0; font-size:0.9rem; font-weight:600;"><?php echo sanitize($ph['title']); ?></h4>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Ad Banner Placement Widget -->
        <section class="ad-banner-section">
            <div class="ad-wrapper">
                <div class="ad-content">
                    <h3>Advertise Your Business on Palghar's Number 1 News Portal!</h3>
                    <p>Reach out to lakhs of local citizens at cost-effective rates. Contact our advertising desk representative today.</p>
                </div>
                <a href="#contact-forms" class="ad-btn">Send Ad Inquiry</a>
            </div>
        </section>

        <!-- Contact & Ad Forms Section -->
        <section class="contact-grid section-padding" id="contact-forms" style="padding-top: 0;">
            <!-- Forms Card -->
            <div class="form-card">
                <h2 class="section-title" style="font-size: 1.5rem; margin-bottom: 25px;"><i class="far fa-envelope"></i> Contact Us</h2>
                <form id="contact-form-element" method="POST" action="index.php">
                    <input type="hidden" name="action" value="submit_inquiry">
                    
                    <div class="form-group-row">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" name="name" required placeholder="Your Name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="Your Email">
                        </div>
                    </div>
                    <div class="form-group-row">
                        <div class="form-group">
                            <label class="form-label">Mobile Number *</label>
                            <input type="tel" class="form-control" name="phone" required placeholder="10-digit number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Inquiry Type *</label>
                            <select class="form-control" name="type" required>
                                <option value="general">General Inquiry</option>
                                <option value="ad">Advertisement Placement</option>
                                <option value="news_tip">Submit a News Tip</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Your Message *</label>
                        <textarea class="form-control" name="message" required placeholder="Write your message here..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit" id="form-submit-btn">Submit Inquiry Form</button>
                </form>
            </div>

            <!-- Side Widgets Social Connection -->
            <div class="social-widget-card">
                <h3 class="sidebar-header" style="border-bottom-color: var(--secondary);"><i class="fas fa-share-nodes"></i> Connect With Us</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Palghar LIVE News is active across major social networks. Join us to receive immediate notifications directly on your mobile device.</p>
                <a href="https://youtube.com/@palgharlivenews/community?si=GPd0niZQMmemXHo7" target="_blank" class="social-button youtube">
                    <span><i class="fab fa-youtube"></i> YouTube Channel</span>
                    <span style="font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">SUBSCRIBE</span>
                </a>
                <a href="https://www.facebook.com/share/1DzefDPcC2/" target="_blank" class="social-button facebook">
                    <span><i class="fab fa-facebook-f"></i> Facebook Page</span>
                    <span style="font-size: 0.8rem; background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">FOLLOW</span>
                </a>
                <a href="https://api.whatsapp.com/send?text=Get the latest updates from Palghar District on Palghar LIVE News Portal: https://youtube.com/@palgharlivenews" target="_blank" class="social-button whatsapp">
                    <span><i class="fab fa-whatsapp"></i> Share on WhatsApp</span>
                    <span style="font-size: 1.2rem;"><i class="fas fa-chevron-right"></i></span>
                </a>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container footer-content-wrap">
            <div class="footer-grid">
                <!-- About column -->
                <div class="footer-about">
                    <img src="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Logo" class="footer-logo" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
                    <p>Palghar district's leading digital news channel. Committed to truth and representing the issues of the common public under our motto "With Truth, With Public".</p>
                    <div style="display: flex; gap: 10px;">
                        <a href="https://www.facebook.com/share/1DzefDPcC2/" target="_blank" style="color: var(--accent);"><i class="fab fa-facebook-square fa-2x"></i></a>
                        <a href="https://youtube.com/@palgharlivenews/community?si=GPd0niZQMmemXHo7" target="_blank" style="color: var(--accent);"><i class="fab fa-youtube-square fa-2x"></i></a>
                    </div>
                </div>
                <!-- Links column -->
                <div>
                    <h3 class="footer-title">Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <?php foreach (array_slice($allSections, 0, 3) as $sec): ?>
                            <li><a href="index.php?cat=<?php echo $sec['id']; ?>"><?php echo sanitize($sec['title']); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="admin/login.php">Admin Login</a></li>
                    </ul>
                </div>
                <!-- Newsletter/Subscribe -->
                <div class="footer-newsletter">
                    <h3 class="footer-title">Newsletter Subscription</h3>
                    <p>Subscribe to our newsletter to receive daily news updates directly in your inbox.</p>
                    <form class="newsletter-form" id="newsletter-form-element">
                        <input type="email" class="newsletter-input" required placeholder="Your email address...">
                        <button type="submit" class="btn-newsletter"><i class="far fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            <!-- Bottom line -->
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Palghar LIVE News. All Rights Reserved. | Developed by Digital Daddy</p>
                <p>Voice of the Public | <a href="admin/login.php" style="color: rgba(255,255,255,0.4); text-decoration: underline;">Admin Portal</a></p>
            </div>
        </div>
    </footer>

    <!-- Global Image Lightbox Modal Viewer -->
    <div id="image-lightbox-modal" class="lightbox" role="dialog" aria-hidden="true">
        <span class="lightbox-close" id="lightbox-close-btn">&times;</span>
        <div class="lightbox-content-wrap">
            <img class="lightbox-content" id="lightbox-zoom-img" alt="Enlarged View">
            <div class="lightbox-caption" id="lightbox-zoom-caption"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/app_v2.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            initHeaderWidgets();
            setupMobileMenu();
            
            // Check redirect status to show toast notifications
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('inquiry') === 'success') {
                showToast("Inquiry submitted successfully! We will contact you shortly.");
            } else if (urlParams.get('inquiry') === 'error') {
                showToast("Failed to submit inquiry. Please try again.", false);
            }
            
            // Initialize Slider carousel for categories banners dynamically
            <?php if ($currentCat !== '' && $sectionInfo && count($bannerImages) > 1): ?>
                const container = document.getElementById('section-banner-container');
                const track = document.getElementById('track-<?php echo $currentCat; ?>');
                const prevBtn = container.querySelector('.prev');
                const nextBtn = container.querySelector('.next');
                const dots = container.querySelectorAll('.section-carousel-dot');
                
                // Initialize Touch Slider bindings on the server-rendered HTML
                new DomSlider(container, track, prevBtn, nextBtn, dots, <?php echo count($bannerImages); ?>);
            <?php endif; ?>

            // Video playlist triggers
            const playlist = document.getElementById('video-playlist-container');
            const playerIframe = document.querySelector('#main-video-player-container iframe');
            if (playlist && playerIframe) {
                playlist.addEventListener('click', (e) => {
                    const item = e.target.closest('.playlist-item');
                    if (item) {
                        document.querySelectorAll('.playlist-item').forEach(el => el.classList.remove('active'));
                        item.classList.add('active');
                        const yid = item.dataset.youtubeId;
                        playerIframe.src = `https://www.youtube.com/embed/${yid}`;
                    }
                });
            }

            // Theme toggle registration
            const themeBtn = document.getElementById('theme-toggle');
            if (themeBtn) {
                themeBtn.addEventListener('click', () => {
                    toggleTheme();
                });
            }

            // Mobile menu drawer
            const menuToggle = document.getElementById('mobile-menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('click', () => {
                    const navMenu = document.getElementById('nav-links-menu');
                    if (navMenu) {
                        navMenu.classList.toggle('active');
                    }
                });
            }

            // Lightbox Modal Close binding
            const closeBtn = document.getElementById('lightbox-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    const modal = document.getElementById('image-lightbox-modal');
                    if (modal) {
                        modal.style.display = "none";
                    }
                });
            }

            // Lightbox click trigger delegate
            document.body.addEventListener('click', (e) => {
                const imgWrap = e.target.closest('.featured-img-wrap img, .card-img-wrap img, .trending-item img, .section-static-banner img, .photo-gallery-item img');
                if (imgWrap && !e.target.closest('.media-manager-item')) {
                    const modal = document.getElementById('image-lightbox-modal');
                    const modalImg = document.getElementById('lightbox-zoom-img');
                    const captionText = document.getElementById('lightbox-zoom-caption');
                    
                    if (modal && modalImg) {
                        modal.style.display = "flex";
                        modalImg.src = imgWrap.src;
                        if (captionText) {
                            captionText.innerHTML = imgWrap.alt || "Palghar LIVE Image Preview";
                        }
                    }
                }
            });

            // Newsletter submit handler
            const newsletterForm = document.getElementById('newsletter-form-element');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    showToast('Thank you for subscribing to our newsletter!');
                    newsletterForm.reset();
                });
            }
        });

        // Simple DOM-based Slider controller class for server-rendered HTML markup
        class DomSlider {
            constructor(container, track, prevBtn, nextBtn, dots, slideCount) {
                this.container = container;
                this.track = track;
                this.prevBtn = prevBtn;
                this.nextBtn = nextBtn;
                this.dots = dots;
                this.slideCount = slideCount;
                this.currentIndex = 0;
                this.autoplayTimer = null;
                this.startX = 0;
                this.isDragging = false;
                this.currentTranslate = 0;
                this.prevTranslate = 0;
                
                this.init();
            }
            
            init() {
                if (this.prevBtn) this.prevBtn.addEventListener('click', () => this.navigate(-1));
                if (this.nextBtn) this.nextBtn.addEventListener('click', () => this.navigate(1));
                this.dots.forEach(dot => {
                    dot.addEventListener('click', () => {
                        const idx = parseInt(dot.dataset.index);
                        this.gotoSlide(idx);
                    });
                });
                
                // Touch & Mouse gestures
                this.track.addEventListener('mousedown', (e) => this.dragStart(e));
                this.track.addEventListener('touchstart', (e) => this.dragStart(e), { passive: true });
                this.track.addEventListener('mousemove', (e) => this.dragMove(e));
                this.track.addEventListener('touchmove', (e) => this.dragMove(e), { passive: true });
                this.track.addEventListener('mouseup', () => this.dragEnd());
                this.track.addEventListener('touchend', () => this.dragEnd());
                this.track.addEventListener('mouseleave', () => this.dragEnd());
                
                this.startAutoplay();
                
                // Pause on hover
                this.container.addEventListener('mouseenter', () => this.stopAutoplay());
                this.container.addEventListener('mouseleave', () => this.startAutoplay());
            }
            
            navigate(dir) {
                const target = (this.currentIndex + dir + this.slideCount) % this.slideCount;
                this.gotoSlide(target);
            }
            
            gotoSlide(idx) {
                this.currentIndex = idx;
                const width = this.container.offsetWidth;
                this.currentTranslate = -this.currentIndex * width;
                this.prevTranslate = this.currentTranslate;
                
                this.track.style.transition = 'transform 0.4s ease-in-out';
                this.track.style.transform = `translateX(${this.currentTranslate}px)`;
                
                this.dots.forEach((dot, dIdx) => {
                    dot.classList.toggle('active', dIdx === this.currentIndex);
                });
            }
            
            dragStart(e) {
                this.isDragging = true;
                this.stopAutoplay();
                this.track.style.transition = 'none';
                this.startX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
            }
            
            dragMove(e) {
                if (!this.isDragging) return;
                const currentX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                const diff = currentX - this.startX;
                const translate = this.prevTranslate + diff;
                this.track.style.transform = `translateX(${translate}px)`;
                this.currentTranslate = translate;
            }
            
            dragEnd() {
                if (!this.isDragging) return;
                this.isDragging = false;
                const movedBy = this.currentTranslate - this.prevTranslate;
                const threshold = this.container.offsetWidth * 0.2;
                
                if (movedBy < -threshold) {
                    this.navigate(1);
                } else if (movedBy > threshold) {
                    this.navigate(-1);
                } else {
                    this.gotoSlide(this.currentIndex);
                }
                this.startAutoplay();
            }
            
            startAutoplay() {
                this.stopAutoplay();
                this.autoplayTimer = setInterval(() => this.navigate(1), 4000);
            }
            
            stopAutoplay() {
                if (this.autoplayTimer) clearInterval(this.autoplayTimer);
            }
        }
    </script>
</body>
</html>
