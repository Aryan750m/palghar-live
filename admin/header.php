<?php
// Shared Admin Header: admin/header.php
// Production-ready shared head section and layout navigation for admin portal

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['palghar_live_admin_session']) || $_SESSION['palghar_live_admin_session'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

$db = getDatabaseConnection();
$adminUser = $_SESSION['palghar_live_admin_username'];
$adminRole = $_SESSION['palghar_live_admin_role'];

// Fetch quick stats count
try {
    $statArticles = $db->query("SELECT COUNT(*) FROM news")->fetchColumn();
    $statComments = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $statInquiries = $db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
} catch (PDOException $e) {
    $statArticles = 0;
    $statComments = 0;
    $statInquiries = 0;
}

// Helper to check active navigation link
function isPageActive($fileName) {
    $current = basename($_SERVER['PHP_SELF']);
    return $current === $fileName ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Palghar LIVE</title>
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <link rel="shortcut icon" type="image/jpeg" href="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body>

    <!-- Main Admin Dashboard -->
    <div class="admin-dashboard container" id="admin-main-dashboard" style="display: block; padding-top:20px; padding-bottom:40px;">
        
        <!-- Header Controls -->
        <div class="admin-header-content" style="border-bottom: 2px solid var(--border-color); padding-bottom: 15px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div class="logo-container">
                <a href="dashboard.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none;">
                    <img src="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Logo" class="site-logo" style="height:55px;" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
                    <div class="brand-details">
                        <h2 class="brand-title" style="font-size: 1.6rem; margin:0;">Admin <span>Control Panel</span></h2>
                        <p class="brand-slogan" style="font-size: 0.75rem; margin:0;">Palghar LIVE News Management (logged in: <?php echo htmlspecialchars($adminUser); ?>)</p>
                    </div>
                </a>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="btn-live" style="background-color: var(--secondary); box-shadow: none; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Log Out</a>
                <a href="../index.php" target="_blank" class="btn-live" style="text-decoration:none;"><i class="fas fa-home"></i> Go to Website</a>
            </div>
        </div>

        <!-- Admin Stats Strip -->
        <div class="admin-stats-grid" style="margin-bottom: 25px;">
            <div class="stat-card">
                <div class="stat-details">
                    <h4>Total Articles</h4>
                    <span class="stat-val"><?php echo $statArticles; ?></span>
                </div>
                <div class="stat-icon"><i class="far fa-newspaper"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-details">
                    <h4>Reader Inquiries</h4>
                    <span class="stat-val"><?php echo $statInquiries; ?></span>
                </div>
                <div class="stat-icon"><i class="far fa-envelope"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-details">
                    <h4>Total Comments</h4>
                    <span class="stat-val"><?php echo $statComments; ?></span>
                </div>
                <div class="stat-icon"><i class="far fa-comments"></i></div>
            </div>
        </div>

        <!-- Dashboard Tabbed Navigation -->
        <div class="admin-tab-nav" style="margin-bottom: 30px; border-bottom: 1px solid var(--border-color); display: flex; gap: 10px; flex-wrap: wrap; padding-bottom:5px;">
            <a href="dashboard.php" class="admin-tab-btn <?php echo isPageActive('dashboard.php'); ?>" style="text-decoration:none;"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="news.php" class="admin-tab-btn <?php echo isPageActive('news.php'); ?>" style="text-decoration:none;"><i class="far fa-edit"></i> News Manager</a>
            <a href="sections.php" class="admin-tab-btn <?php echo isPageActive('sections.php'); ?>" style="text-decoration:none;"><i class="fas fa-images"></i> Manage Sections</a>
            <?php if ($adminRole === 'admin'): ?>
                <a href="users.php" class="admin-tab-btn <?php echo isPageActive('users.php'); ?>" style="text-decoration:none;"><i class="fas fa-users-cog"></i> User Management</a>
            <?php endif; ?>
            <a href="settings.php" class="admin-tab-btn <?php echo isPageActive('settings.php'); ?>" style="text-decoration:none;"><i class="fas fa-sliders"></i> Settings & Logs</a>
        </div>
