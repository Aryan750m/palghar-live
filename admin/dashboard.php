<?php
// Dashboard: admin/dashboard.php
// Production-ready dashboard page showcasing recent items and statistics overview

require_once 'header.php';

// Fetch recent articles (latest 5)
try {
    $newsStmt = $db->query("SELECT * FROM news ORDER BY date_published DESC LIMIT 5");
    $recentNews = $newsStmt->fetchAll();
} catch (PDOException $e) {
    $recentNews = [];
}

// Fetch recent inquiries (latest 5)
try {
    $inqStmt = $db->query("SELECT * FROM inquiries ORDER BY date_received DESC LIMIT 5");
    $recentInquiries = $inqStmt->fetchAll();
} catch (PDOException $e) {
    $recentInquiries = [];
}
?>
<div class="form-card" style="box-shadow: none; border-color: var(--border-color); padding: 30px; margin-bottom:25px;">
    <h3 style="margin-bottom: 10px; font-family: var(--font-heading);">Welcome to the Palghar LIVE Control Center</h3>
    <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;">You are logged in as <strong><?php echo htmlspecialchars($adminUser); ?></strong> (Role: <span class="badge info"><?php echo htmlspecialchars($adminRole); ?></span>). Use the tabs above to manage news articles, modify homepage category banner carousels, view logs, or control user editor roles.</p>
    
    <div style="display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap;">
        <a href="news.php" class="btn-live" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;"><i class="far fa-edit"></i> Publish News Article</a>
        <a href="sections.php" class="btn-live" style="background-color: var(--accent); color: var(--secondary); text-decoration:none; display:inline-flex; align-items:center; gap:8px;"><i class="fas fa-images"></i> Manage Slide Banners</a>
        <a href="settings.php" class="btn-live" style="background-color: var(--secondary); text-decoration:none; display:inline-flex; align-items:center; gap:8px;"><i class="fas fa-inbox"></i> View Visitor Inquiries</a>
    </div>
</div>

<div class="user-management-split" style="display: flex; gap: 25px; flex-wrap: wrap;">
    <!-- Recent Articles Column -->
    <div style="flex: 1.2; min-width: 300px;">
        <h4 style="margin-bottom: 15px; font-family: var(--font-heading);"><i class="far fa-newspaper" style="color:var(--primary);"></i> Recently Published Articles</h4>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Views</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentNews)): ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No articles found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentNews as $art): ?>
                            <tr>
                                <td style="font-weight: 600; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <a href="../news-detail.php?id=<?php echo $art['id']; ?>" target="_blank" style="color:var(--text-primary); text-decoration:none;"><?php echo sanitize($art['title']); ?></a>
                                </td>
                                <td><span class="badge info"><?php echo getCategoryLabel($art['category']); ?></span></td>
                                <td><?php echo $art['views']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px; text-align:right;">
            <a href="news.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none; font-weight:600;">Go to News Manager <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <!-- Recent Inquiries Column -->
    <div style="flex: 1; min-width: 300px;">
        <h4 style="margin-bottom: 15px; font-family: var(--font-heading);"><i class="far fa-envelope" style="color:var(--primary);"></i> Recent Inquiries</h4>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sender</th>
                        <th>Type</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInquiries)): ?>
                        <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No inquiries logged.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentInquiries as $inq): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo sanitize($inq['name']); ?></td>
                                <td>
                                    <?php if ($inq['type'] === 'ad'): ?>
                                        <span class="badge success">Ad</span>
                                    <?php elseif ($inq['type'] === 'news_tip'): ?>
                                        <span class="badge info">Tip</span>
                                    <?php else: ?>
                                        <span class="badge" style="background:#e2e8f0; color:#475569;">General</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.78rem; color: var(--text-muted);"><?php echo date("M d", strtotime($inq['date_received'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:10px; text-align:right;">
            <a href="settings.php#inquiries" style="font-size:0.85rem; color:var(--primary); text-decoration:none; font-weight:600;">View All Inquiries <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
