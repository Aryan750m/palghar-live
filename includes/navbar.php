<?php
// Shared View: includes/navbar.php
// Production-ready navbar template that loads category tabs dynamically from MySQL

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$db = getDatabaseConnection();
try {
    $navSections = $db->query("SELECT id, title FROM sections ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in navbar sections query: " . $e->getMessage());
    $navSections = [];
}

$currentCat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$basePath = (defined('IN_ADMIN_DIR') ? '../' : '');
?>
<nav class="navbar">
    <div class="container nav-content">
        <button class="mobile-menu-btn" id="mobile-menu-toggle">☰</button>
        <ul class="nav-links" id="nav-links-menu">
            <li><a href="<?php echo $basePath; ?>index.php" class="nav-item <?php echo ($currentCat === '' && !strpos($_SERVER['SCRIPT_NAME'], 'admin')) ? 'active' : ''; ?>">Home</a></li>
            <?php foreach ($navSections as $sec): ?>
                <li><a href="<?php echo $basePath; ?>index.php?cat=<?php echo $sec['id']; ?>" class="nav-item <?php echo $currentCat === $sec['id'] ? 'active' : ''; ?>"><?php echo sanitize($sec['title']); ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?php echo (defined('IN_ADMIN_DIR') ? 'index.php' : 'admin/index.php'); ?>" class="nav-item <?php echo strpos($_SERVER['SCRIPT_NAME'], 'admin') ? 'active' : ''; ?>" style="color: var(--accent);"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
        </ul>
        <div class="nav-right-controls">
            <button class="theme-toggle-btn" id="theme-toggle">🌙</button>
        </div>
    </div>
</nav>
