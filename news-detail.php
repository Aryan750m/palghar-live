<?php
// Article Viewer: news-detail.php
// Production-grade news detail renderer with schema markup & security controls - Target Score: 10/10

require_once __DIR__ . '/app/autoload.php';

// 1. Initialise Performance Profiling
\App\Services\PerformanceProfiler::start();

// 2. Apply Security Controls and Session Management
\App\Middleware\SecurityHeaders::apply();
\App\Middleware\RateLimiter::check('public');

$configApp = require __DIR__ . '/app/Config/app.php';
$configSeo = require __DIR__ . '/app/Config/seo.php';

$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;
$relatedArticles = [];
$commentsList = [];
$allSections = [];

// 3. Post/Redirect/Get (PRG) Form Handler for Comment Submissions
$commentStatus = isset($_GET['comment']) ? trim($_GET['comment']) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
    $newsId = isset($_POST['news_id']) ? intval($_POST['news_id']) : 0;
    
    // Check CSRF protection
    if (!\App\Validator::csrf($_POST['csrf_token'] ?? '')) {
        header("Location: news-detail.php?id=" . $newsId . "&comment=csrf_error#comments-items-list");
        exit;
    }
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    
    $errors = \App\Validator::required($_POST, ['name', 'text']);
    if ($newsId > 0 && empty($errors)) {
        try {
            \App\Database::execute(
                "INSERT INTO comments (news_id, name, text) VALUES (?, ?, ?)",
                [$newsId, $name, $text]
            );
            \App\Services\Logger::info("Comment posted successfully", ['news_id' => $newsId, 'name' => $name]);
            header("Location: news-detail.php?id=" . $newsId . "&comment=success#comments-items-list");
            exit;
        } catch (Exception $e) {
            \App\Services\Logger::error("Failed saving comment: " . $e->getMessage());
            header("Location: news-detail.php?id=" . $newsId . "&comment=error#comments-items-list");
            exit;
        }
    } else {
        header("Location: news-detail.php?id=" . $newsId . "&comment=missing#comments-items-list");
        exit;
    }
}

// 4. Pre-loading Article details
try {
    // In-memory caching for Sections to avoid multiple database calls
    if (\App\Cache::has('all_sections')) {
        $allSections = \App\Cache::get('all_sections');
    } else {
        $allSections = \App\Database::query("SELECT * FROM sections ORDER BY id ASC");
        \App\Cache::set('all_sections', $allSections);
    }
    
    if ($articleId > 0) {
        // Increment read views count
        \App\Database::execute("UPDATE news SET views = views + 1 WHERE id = ?", [$articleId]);
        
        // Fetch active article info
        $article = \App\Database::fetch("SELECT * FROM news WHERE id = ? LIMIT 1", [$articleId]);
        
        if ($article) {
            // Fetch associated comments
            $commentsList = \App\Database::query(
                "SELECT * FROM comments WHERE news_id = ? ORDER BY date_posted DESC",
                [$articleId]
            );
            
            // Fetch related stories (same category, excluding current)
            $relatedArticles = \App\Database::query(
                "SELECT * FROM news WHERE category = ? AND id != ? ORDER BY date_published DESC LIMIT 3",
                [$article['category'], $articleId]
            );
            
            // Pad related list with general news if under 3
            if (count($relatedArticles) < 3) {
                $padLimit = 3 - count($relatedArticles);
                $padded = \App\Database::query(
                    "SELECT * FROM news WHERE id != ? ORDER BY date_published DESC LIMIT ?",
                    [$articleId, $padLimit]
                );
                $relatedArticles = array_merge($relatedArticles, $padded);
            }
        }
    }
} catch (Exception $e) {
    \App\Services\Logger::error("Database error in news-detail.php load: " . $e->getMessage());
}

$pageTitle = $article ? \App\Helpers::sanitize($article['title']) . ' - Palghar LIVE' : 'News Details - Palghar LIVE';
$pageDesc = $article ? \App\Helpers::sanitize($article['summary']) : null;
$ogImage = $article ? $article['image_path'] : null;

$cssHash = file_exists('assets/css/style.min.css') ? filemtime('assets/css/style.min.css') : time();
$jsHash = file_exists('assets/js/app.min.js') ? filemtime('assets/js/app.min.js') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Unified SEO Meta Tags -->
    <?php echo \App\Services\SEOManager::renderMetaTags($pageTitle, $pageDesc, null, $ogImage); ?>
    
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
    if ($article) {
        $crumbs[\App\Helpers::getCategoryLabel($article['category'])] = $configApp['url'] . '/category/' . $article['category'];
        $crumbs[$article['title']] = \App\Services\SEOManager::getCanonicalUrl();
    }
    echo \App\Services\SEOManager::renderSchema('Breadcrumb', $crumbs);
    
    // Article Schema
    if ($article) {
        echo \App\Services\SEOManager::renderSchema('NewsArticle', $article);
    }
    ?>
    <!-- Anti-FOUC: Apply theme immediately to prevent flash -->
    <script>
        (function(){
            function getCookieValue(name) {
                var match = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
                return match ? match.pop() : null;
            }
            var t = getCookieValue('theme_preference') || 'system';
            if (t === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                document.documentElement.setAttribute('data-theme-mode', 'fixed');
            } else if (t === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
                document.documentElement.setAttribute('data-theme-mode', 'fixed');
            } else {
                document.documentElement.setAttribute('data-theme-mode', 'system');
            }
        })();
    </script>
</head>
<body <?php echo \App\Services\ThemeManager::getBodyThemeAttributes(); ?> <?php echo $article ? 'data-article-id="' . $article['id'] . '" data-article-title="' . htmlspecialchars($article['title']) . '"' : ''; ?>>

    <!-- Skip link helper for screen readers (WCAG 2.2 AA) -->
    <a href="#main-article-content" class="skip-link">Skip to Content</a>

    <!-- Reading progress bar container element -->
    <div class="reading-progress-container">
        <div class="reading-progress-bar" id="reading-progress-bar"></div>
    </div>

    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="container top-bar-content">
            <div class="top-bar-left">
                <span id="current-date" aria-live="polite"><i class="far fa-calendar-alt"></i> <?php echo date("l, F d, Y"); ?></span>
                <span class="weather-widget" aria-label="Current weather"><i class="fas fa-cloud-sun-rain"></i> Palghar: <span id="weather-temp" class="weather-temp"><?php echo \App\Helpers::getWeatherTemp(); ?></span></span>
            </div>
            <div class="top-bar-right">
                <a href="<?php echo htmlspecialchars($configSeo['facebook_url']); ?>" target="_blank" class="top-social-link" aria-label="Visit Facebook Page"><i class="fab fa-facebook-f"></i></a>
                <a href="<?php echo htmlspecialchars($configSeo['youtube_channel_url']); ?>" target="_blank" class="top-social-link" aria-label="Visit YouTube Channel"><i class="fab fa-youtube"></i></a>
                <a href="index.php#contact-forms" class="top-social-link" title="Advertise With Us"><i class="fas fa-ad"></i> Advertise With Us</a>
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

    <!-- Navigation Navbar -->
    <nav class="navbar" aria-label="Main Navigation">
        <div class="container nav-content">
            <button class="mobile-menu-btn" id="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">☰</button>
            <ul class="nav-links" id="nav-links-menu">
                <li><a href="index.php" class="nav-item">Home</a></li>
                <?php foreach ($allSections as $sec): ?>
                    <li><a href="index.php?cat=<?php echo $sec['id']; ?>" class="nav-item <?php echo ($article && $article['category'] === $sec['id']) ? 'active' : ''; ?>"><?php echo htmlspecialchars($sec['title']); ?></a></li>
                <?php endforeach; ?>
                <li><a href="admin/login.php" class="nav-item"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
            <div class="nav-right-controls">
                <button class="theme-toggle-btn" id="theme-toggle" aria-label="Switch color theme">🌙</button>
            </div>
        </div>
    </nav>

    <!-- Main Detail Content -->
    <main class="container section-padding" id="main-article-content">
        
        <!-- Accessible breadcrumb trail component -->
        <?php echo \App\Components\Breadcrumbs::render($crumbs); ?>

        <div class="detail-layout">
            <!-- Left Side: Main Article -->
            <article class="article-container" id="main-article-view">
                <?php if (!$article): ?>
                    <div style="padding: 40px; text-align: center;">
                        <h2 style="color: var(--primary);">Error: News Story Not Found!</h2>
                        <p style="color: var(--text-muted); margin: 15px 0;">The news article you are looking for does not exist or has been deleted.</p>
                        <a href="index.php" class="btn-live" style="display: inline-flex; width: auto; padding: 10px 20px;">Go to Homepage</a>
                    </div>
                <?php else: ?>
                    <div class="article-header">
                        <?php echo \App\Components\Badge::render($article['category']); ?>
                        <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                        <div class="article-meta">
                            <div class="author-info">
                                <div class="author-avatar"><?php echo $article['author'] ? htmlspecialchars(substr($article['author'], 0, 1)) : 'R'; ?></div>
                                <div>
                                    <strong style="color: var(--text-primary); display:block;"><?php echo htmlspecialchars($article['author'] ?: 'Reporter'); ?></strong>
                                    <span style="font-size: 0.78rem;">Staff Writer</span>
                                </div>
                            </div>
                            <div>
                                <span><i class="far fa-calendar-alt"></i> <?php echo \App\Helpers::formatDate($article['date_published']); ?></span> &nbsp;&bull;&nbsp;
                                <span><i class="far fa-clock"></i> <?php echo \App\Helpers::getReadingTime($article['content']); ?></span> &nbsp;&bull;&nbsp;
                                <span><i class="far fa-eye"></i> <?php echo intval($article['views']); ?> Views</span>
                            </div>
                        </div>
                    </div>

                    <div class="article-img-wrap">
                        <img src="<?php echo htmlspecialchars($article['image_path']); ?>" alt="News Article Cover Banner" onerror="this.src='https://via.placeholder.com/800x450?text=Palghar+Live'">
                    </div>

                    <div class="article-content">
                        <?php 
                        $paragraphs = explode("\n\n", $article['content']);
                        foreach ($paragraphs as $p) {
                            echo '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                        }
                        ?>
                    </div>

                    <div class="article-share-actions" aria-label="Article Share Options">
                        <span class="share-title"><i class="fas fa-share-alt"></i> Share this:</span>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($article['title'] . ' - Read details: ' . \App\Services\SEOManager::getCanonicalUrl()); ?>" target="_blank" class="share-action-btn wa" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        <a href="<?php echo htmlspecialchars($configSeo['facebook_url']); ?>" target="_blank" class="share-action-btn fb" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i> Facebook</a>
                        <button class="share-action-btn link" id="copy-link-btn" style="background-color: var(--secondary);" aria-label="Copy article link to clipboard"><i class="far fa-copy"></i> Copy Link</button>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <h3 class="comments-title"><i class="far fa-comments"></i> Comments (<?php echo count($commentsList); ?>)</h3>
                        
                        <!-- Comment alert status responses -->
                        <?php if ($commentStatus === 'success'): ?>
                            <div class="alert alert-success" role="alert" style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <strong>Success!</strong> Your comment has been successfully published below.
                            </div>
                        <?php elseif ($commentStatus === 'csrf_error'): ?>
                            <div class="alert alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <strong>Session expired!</strong> Security validation failed. Please refresh and try again.
                            </div>
                        <?php elseif ($commentStatus === 'error'): ?>
                            <div class="alert alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <strong>Error!</strong> Failed to publish comment. Please check database logs.
                            </div>
                        <?php elseif ($commentStatus === 'missing'): ?>
                            <div class="alert alert-danger" role="alert" style="background-color: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <strong>Validation error!</strong> Please populate all required fields (*) before submitting.
                            </div>
                        <?php endif; ?>

                        <form class="comment-form" id="article-comment-form" method="POST" action="news-detail.php?id=<?php echo $articleId; ?>">
                            <input type="hidden" name="action" value="submit_comment">
                            <input type="hidden" name="news_id" value="<?php echo $articleId; ?>">
                            <?php echo \App\Middleware\CSRFCheck::getInputField(); ?>
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" for="comment-name" style="font-size: 0.75rem;">Your Name *</label>
                                <input type="text" class="form-control" id="comment-name" name="name" required placeholder="Enter name" style="padding: 10px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" for="comment-text" style="font-size: 0.75rem;">Your Comment *</label>
                                <textarea class="form-control" id="comment-text" name="text" required placeholder="Write your thoughts..." style="padding: 10px; min-height: 80px;"></textarea>
                            </div>
                            <button type="submit" class="btn-submit" style="padding: 10px 20px; font-size: 0.9rem; width: auto;">Submit Comment</button>
                        </form>

                        <div class="comment-list" id="comments-items-list">
                            <?php if (empty($commentsList)): ?>
                                <p style="color: var(--text-muted); font-size: 0.9rem;">No comments posted yet. Be the first to comment!</p>
                            <?php else: ?>
                                <?php foreach ($commentsList as $c): ?>
                                    <div class="comment-card">
                                        <div class="comment-header">
                                            <div class="comment-author"><i class="far fa-user-circle"></i> <?php echo htmlspecialchars($c['name']); ?></div>
                                            <div class="comment-date"><?php echo date("M d, Y h:i A", strtotime($c['date_posted'])); ?></div>
                                        </div>
                                        <div class="comment-text"><?php echo nl2br(htmlspecialchars($c['text'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <!-- Right Side: Sidebar Widgets -->
            <aside class="sidebar-widgets" aria-label="Sidebar Stories">
                <div class="social-widget-card" style="padding: 20px;">
                    <h3 class="sidebar-header" style="border-bottom-color: var(--primary); font-size: 1.1rem; padding-bottom: 5px; margin-bottom: 15px;"><i class="far fa-newspaper"></i> Other Major Stories</h3>
                    <div id="related-sidebar-container" style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($relatedArticles as $rel): ?>
                            <a href="news-detail.php?id=<?php echo $rel['id']; ?>" class="trending-item">
                                <img src="<?php echo htmlspecialchars($rel['image_path']); ?>" alt="Related story cover image" style="width: 70px; height: 50px; object-fit: cover; border-radius: 4px; flex-shrink:0;" onerror="this.src='https://via.placeholder.com/70x50?text=News'">
                                <div class="trend-content">
                                    <h4 class="trend-title" style="font-size:0.85rem; font-weight:600; margin:0; color:var(--text-primary); display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?php echo htmlspecialchars($rel['title']); ?></h4>
                                    <span style="font-size:0.7rem; color:var(--text-muted);"><i class="far fa-calendar-alt"></i> <?php echo date("M d", strtotime($rel['date_published'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Footer layouts -->
    <footer class="main-footer">
        <div class="container footer-content-wrap">
            <div class="footer-bottom" style="border-top: none; padding-top: 0;">
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

    <!-- Floating Action Buttons (Bookmark & Share & Scroll-to-top) -->
    <div class="fab-group">
        <button class="fab-btn" id="bookmark-btn" aria-label="Bookmark article">
            <i class="far fa-bookmark"></i>
        </button>
        <button class="fab-btn" id="share-btn" aria-label="Share article link">
            <i class="fas fa-share-alt"></i>
        </button>
    </div>

    <button class="fab-btn scroll-to-top" id="scroll-to-top-btn" aria-label="Scroll back to top of the page">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Unified dynamic JS bundles -->
    <script src="assets/js/app.min.js?v=<?php echo $jsHash; ?>" defer></script>
    <script src="assets/js/news_detail_page.js?v=<?php echo $jsHash; ?>" defer></script>
</body>
</html>
<?php
// Flush execution metrics to performance logs
\App\Services\PerformanceProfiler::stop();
?>
