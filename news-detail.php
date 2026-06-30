<?php
// Article Viewer: news-detail.php
// Production-ready server-rendered article details and comment listings from MySQL - Phase 1

require_once 'config/db.php';
require_once 'includes/functions.php';

$db = getDatabaseConnection();

$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;
$relatedArticles = [];
$commentsList = [];
$allSections = [];

// =========================================================================
// 1. Post/Redirect/Get (PRG) Form Handler for Comment Submissions
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_comment') {
    $newsId = isset($_POST['news_id']) ? intval($_POST['news_id']) : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    
    if ($newsId > 0 && $name !== '' && $text !== '') {
        try {
            $stmt = $db->prepare("INSERT INTO comments (news_id, name, text) VALUES (?, ?, ?)");
            $stmt->execute([$newsId, $name, $text]);
            
            // Redirect to article page to prevent duplicate submissions (PRG Pattern)
            header("Location: news-detail.php?id=" . $newsId . "&comment=success#comments-items-list");
            exit;
        } catch (PDOException $e) {
            error_log("Failed saving comment: " . $e->getMessage());
            header("Location: news-detail.php?id=" . $newsId . "&comment=error#comments-items-list");
            exit;
        }
    } else {
        header("Location: news-detail.php?id=" . $newsId . "&comment=missing#comments-items-list");
        exit;
    }
}

// =========================================================================
// 2. Pre-loading Article Data
// =========================================================================
try {
    if ($articleId > 0) {
        // Increment read views count
        $db->prepare("UPDATE news SET views = views + 1 WHERE id = ?")->execute([$articleId]);
        
        // Fetch article info
        $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
        $stmt->execute([$articleId]);
        $article = $stmt->fetch();
        
        if ($article) {
            // Fetch associated comments
            $comStmt = $db->prepare("SELECT * FROM comments WHERE news_id = ? ORDER BY date_posted DESC");
            $comStmt->execute([$articleId]);
            $commentsList = $comStmt->fetchAll();
            
            // Fetch related stories (same category, excluding current)
            $relStmt = $db->prepare("SELECT * FROM news WHERE category = ? AND id != ? ORDER BY date_published DESC LIMIT 3");
            $relStmt->execute([$article['category'], $articleId]);
            $relatedArticles = $relStmt->fetchAll();
            
            // Pad related list with general news if under 3
            if (count($relatedArticles) < 3) {
                $padStmt = $db->prepare("SELECT * FROM news WHERE id != ? ORDER BY date_published DESC LIMIT ?");
                $padLimit = 3 - count($relatedArticles);
                $padStmt->execute([$articleId, $padLimit]);
                $relatedArticles = array_merge($relatedArticles, $padStmt->fetchAll());
            }
        }
    }
    
    // Fetch all category sections
    $secStmt = $db->query("SELECT * FROM sections ORDER BY id ASC");
    $allSections = $secStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in news-detail.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article ? sanitize($article['title']) . ' - Palghar LIVE' : 'News Details - Palghar LIVE'; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
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
                <a href="index.php#contact-forms" class="top-social-link" title="Advertise With Us"><i class="fas fa-ad"></i> Advertise With Us</a>
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
                        <span class="brand-title">Palghar <span>LIVE</span></span>
                        <p class="brand-slogan">The Strong Voice of the Common People</p>
                    </div>
                </a>
            </div>
            <div class="header-actions">
                <a href="https://youtube.com/@palgharlivenews/community?si=GPd0niZQMmemXHo7" target="_blank" class="btn-live">
                    <span class="live-pulse"></span> Watch Live (YouTube)
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Navbar -->
    <nav class="navbar">
        <div class="container nav-content">
            <button class="mobile-menu-btn" id="mobile-menu-toggle">☰</button>
            <ul class="nav-links" id="nav-links-menu">
                <li><a href="index.php" class="nav-item">Home</a></li>
                <?php foreach ($allSections as $sec): ?>
                    <li><a href="index.php?cat=<?php echo $sec['id']; ?>" class="nav-item <?php echo ($article && $article['category'] === $sec['id']) ? 'active' : ''; ?>"><?php echo sanitize($sec['title']); ?></a></li>
                <?php endforeach; ?>
                <li><a href="admin/login.php" class="nav-item"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
            <div class="nav-right-controls">
                <button class="theme-toggle-btn" id="theme-toggle">🌙</button>
            </div>
        </div>
    </nav>

    <!-- Main Detail Content -->
    <main class="container section-padding">
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
                        <span class="article-cat-tag" style="background-color: <?php echo getCategoryColor($article['category']); ?>; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">
                            <?php echo getCategoryLabel($article['category']); ?>
                        </span>
                        <h1 class="article-title"><?php echo sanitize($article['title']); ?></h1>
                        <div class="article-meta">
                            <div class="author-info">
                                <div class="author-avatar"><?php echo $article['author'] ? substr($article['author'], 0, 1) : 'R'; ?></div>
                                <div>
                                    <strong style="color: var(--text-primary); display:block;"><?php echo sanitize($article['author'] ?: 'Reporter'); ?></strong>
                                    <span style="font-size: 0.78rem;">Staff Writer</span>
                                </div>
                            </div>
                            <div>
                                <span><i class="far fa-calendar-alt"></i> <?php echo formatDate($article['date_published']); ?></span> &nbsp;&bull;&nbsp;
                                <span><i class="far fa-eye"></i> <?php echo $article['views']; ?> Readers</span>
                            </div>
                        </div>
                    </div>

                    <div class="article-img-wrap">
                        <img src="<?php echo $article['image_path']; ?>" alt="<?php echo sanitize($article['title']); ?>" onerror="this.src='https://via.placeholder.com/800x450?text=Palghar+Live'">
                    </div>

                    <div class="article-content">
                        <?php 
                        $paragraphs = explode("\n\n", $article['content']);
                        foreach ($paragraphs as $p) {
                            echo '<p>' . nl2br(sanitize($p)) . '</p>';
                        }
                        ?>
                    </div>

                    <div class="article-share-actions">
                        <span class="share-title"><i class="fas fa-share-alt"></i> Share this:</span>
                        <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($article['title'] . ' - Read details: ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="share-action-btn wa"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                        <a href="https://www.facebook.com/share/1DzefDPcC2/" target="_blank" class="share-action-btn fb"><i class="fab fa-facebook-f"></i> Facebook</a>
                        <button class="share-action-btn link" id="btn-copy-link" style="background-color: var(--secondary);"><i class="far fa-copy"></i> Copy Link</button>
                    </div>

                    <!-- Comments Section -->
                    <div class="comments-section">
                        <h3 class="comments-title"><i class="far fa-comments"></i> Comments (<?php echo count($commentsList); ?>)</h3>
                        
                        <form class="comment-form" id="article-comment-form" method="POST" action="news-detail.php?id=<?php echo $articleId; ?>">
                            <input type="hidden" name="action" value="submit_comment">
                            <input type="hidden" name="news_id" value="<?php echo $articleId; ?>">
                            
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" style="font-size: 0.75rem;">Your Name *</label>
                                <input type="text" class="form-control" name="name" required placeholder="Enter name" style="padding: 10px;">
                            </div>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label" style="font-size: 0.75rem;">Your Comment *</label>
                                <textarea class="form-control" name="text" required placeholder="Write your thoughts..." style="padding: 10px; min-height: 80px;"></textarea>
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
                                            <div class="comment-author"><i class="far fa-user-circle"></i> <?php echo sanitize($c['name']); ?></div>
                                            <div class="comment-date"><?php echo date("M d, Y h:i A", strtotime($c['date_posted'])); ?></div>
                                        </div>
                                        <div class="comment-text"><?php echo nl2br(sanitize($c['text'])); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <!-- Right Side: Sidebar Widgets -->
            <aside class="sidebar-widgets">
                <!-- Sidebar Related News -->
                <div class="social-widget-card" style="padding: 20px;">
                    <h3 class="sidebar-header" style="border-bottom-color: var(--primary); font-size: 1.1rem; padding-bottom: 5px; margin-bottom: 15px;"><i class="far fa-newspaper"></i> Other Major Stories</h3>
                    <div id="related-sidebar-container" style="display: flex; flex-direction: column; gap: 15px;">
                        <?php foreach ($relatedArticles as $rel): ?>
                            <a href="news-detail.php?id=<?php echo $rel['id']; ?>" class="trending-item" style="padding: 10px; align-items: flex-start; text-decoration: none; display: flex; gap: 10px;">
                                <img src="<?php echo $rel['image_path']; ?>" alt="thumb" style="width: 70px; height: 50px; object-fit: cover; border-radius: 4px; flex-shrink:0;" onerror="this.src='https://via.placeholder.com/70x50?text=News'">
                                <div class="trend-content">
                                    <h4 class="trend-title" style="font-size: 0.85rem; line-height: 1.3; font-weight:600; margin:0 0 4px 0; color:var(--text-primary); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo sanitize($rel['title']); ?></h4>
                                    <span style="font-size: 0.72rem; color: var(--text-muted);"><i class="far fa-calendar-alt"></i> <?php echo date("M d", strtotime($rel['date_published'])); ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sidebar Sponsor Ad Widget -->
                <div class="sidebar-ad-card">
                    <h4>Palghar LIVE Digital</h4>
                    <p>Contact our representative today to publish your digital advertisement.</p>
                    <a href="index.php#contact-forms" class="btn-live" style="box-shadow: none; display: inline-flex; font-size: 0.8rem; padding: 8px 16px; margin-top: 10px; background-color: var(--accent); color: var(--secondary);">Advertise Here</a>
                </div>
            </aside>
        </div>
    </main>

    <!-- Footer -->
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
            
            // Check status for toast feedback
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('comment') === 'success') {
                showToast("Comment added successfully!");
            } else if (urlParams.get('comment') === 'error') {
                showToast("Failed to submit comment. Please try again.", false);
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

            // Copy Link action
            const copyBtn = document.getElementById('btn-copy-link');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => {
                    navigator.clipboard.writeText(window.location.href).then(() => {
                        showToast('Link copied to clipboard! You can share it now.');
                    }).catch(() => {
                        showToast('Failed to copy link.', false);
                    });
                });
            }

            // Lightbox close
            const closeBtn = document.getElementById('lightbox-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    const modal = document.getElementById('image-lightbox-modal');
                    if (modal) {
                        modal.style.display = "none";
                    }
                });
            }

            // Lightbox open triggers
            document.body.addEventListener('click', (e) => {
                const imgWrap = e.target.closest('.article-img-wrap img, .trending-item img');
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
        });
    </script>
</body>
</html>
