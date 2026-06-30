<?php
// News Management: admin/news.php
// Production-ready server-rendered News CRUD panel using Post/Redirect/Get pattern - Phase 1

require_once 'header.php';

$message = '';
$error = '';

// =========================================================================
// 1. Post/Redirect/Get (PRG) Form Actions
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_news') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $author = isset($_POST['author']) ? trim($_POST['author']) : '';
        $summary = isset($_POST['summary']) ? trim($_POST['summary']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $imageUrl = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
        $lang = isset($_POST['language']) ? trim($_POST['language']) : 'en';
        $featured = isset($_POST['featured']) && $_POST['featured'] === '1' ? 1 : 0;
        $trending = isset($_POST['trending']) && $_POST['trending'] === '1' ? 1 : 0;
        
        // Gated Category permissions check for Editors
        if ($adminRole === 'editor') {
            $allowedSections = $_SESSION['palghar_live_admin_permissions'];
            if (!in_array($category, $allowedSections)) {
                header("Location: news.php?status=permission_denied");
                exit;
            }
        }
        
        // Handle news image upload
        $finalImagePath = $imageUrl ?: 'https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=800&q=80';
        
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileName = $_FILES['image_file']['name'];
            $fileSize = $_FILES['image_file']['size'];
            $fileType = $_FILES['image_file']['type'];
            
            // Validate image files (under 5MB)
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExt, $allowedExts) && $fileSize <= 5 * 1024 * 1024) {
                // Target folder
                $uploadDir = '../uploads/news/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $newFileName = md5(time() . $fileName) . '.' . $fileExt;
                $targetFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmp, $targetFile)) {
                    $finalImagePath = 'uploads/news/' . $newFileName;
                }
            } else {
                header("Location: news.php?status=invalid_file");
                exit;
            }
        }
        
        if ($title !== '' && $category !== '' && $content !== '') {
            try {
                if ($id > 0) {
                    // Fetch existing image path if no replacement supplied
                    if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
                        $exStmt = $db->prepare("SELECT image_path FROM news WHERE id = ?");
                        $exStmt->execute([$id]);
                        $existingImage = $exStmt->fetchColumn();
                        if ($existingImage && $imageUrl === $existingImage) {
                            $finalImagePath = $existingImage;
                        }
                    }
                    
                    // Edit Article
                    $stmt = $db->prepare("UPDATE news SET title = ?, summary = ?, content = ?, category = ?, image_path = ?, author = ?, language = ?, featured = ?, trending = ? WHERE id = ?");
                    $stmt->execute([$title, $summary, $content, $category, $finalImagePath, $author, $lang, $featured, $trending, $id]);
                    
                    header("Location: news.php?status=update_success");
                    exit;
                } else {
                    // Create Article
                    $stmt = $db->prepare("INSERT INTO news (title, summary, content, category, image_path, author, language, featured, trending) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $summary, $content, $category, $finalImagePath, $author, $lang, $featured, $trending]);
                    
                    header("Location: news.php?status=create_success");
                    exit;
                }
            } catch (PDOException $e) {
                error_log("Failed saving article: " . $e->getMessage());
                header("Location: news.php?status=save_error");
                exit;
            }
        } else {
            header("Location: news.php?status=missing_fields");
            exit;
        }
    }
}

// Handle GET Actions (Delete Article)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        try {
            // Verify editor permission on this article category before delete
            $stmt = $db->prepare("SELECT category FROM news WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetchColumn();
            
            if ($category) {
                if ($adminRole === 'editor') {
                    $allowedSections = $_SESSION['palghar_live_admin_permissions'];
                    if (!in_array($category, $allowedSections)) {
                        header("Location: news.php?status=permission_denied");
                        exit;
                    }
                }
                
                $delStmt = $db->prepare("DELETE FROM news WHERE id = ?");
                $delStmt->execute([$id]);
                
                header("Location: news.php?status=delete_success");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Failed deleting article: " . $e->getMessage());
            header("Location: news.php?status=delete_error");
            exit;
        }
    }
}

// =========================================================================
// 2. Feedback Toasts Parser
// =========================================================================
$status = isset($_GET['status']) ? $_GET['status'] : '';
switch ($status) {
    case 'create_success':
        $message = 'Article published successfully!';
        break;
    case 'update_success':
        $message = 'Article details updated successfully!';
        break;
    case 'delete_success':
        $message = 'Article deleted successfully.';
        break;
    case 'permission_denied':
        $error = 'Access Denied: You do not have permissions for that category.';
        break;
    case 'invalid_file':
        $error = 'Failed uploading file. Make sure size is under 5MB and extension is JPG, PNG, or WEBP.';
        break;
    case 'missing_fields':
        $error = 'Required parameters were missing.';
        break;
    case 'save_error':
    case 'delete_error':
        $error = 'Database transaction failed.';
        break;
}

// =========================================================================
// 3. Edit Article Preloader
// =========================================================================
$editArticle = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = intval($_GET['id']);
    if ($editId > 0) {
        $editStmt = $db->prepare("SELECT * FROM news WHERE id = ?");
        $editStmt->execute([$editId]);
        $editArticle = $editStmt->fetch();
        
        // Editor checks edit permission
        if ($editArticle && $adminRole === 'editor') {
            $allowedSections = $_SESSION['palghar_live_admin_permissions'];
            if (!in_array($editArticle['category'], $allowedSections)) {
                $editArticle = null;
                $error = 'Access Denied: You are not authorized to edit this article category.';
            }
        }
    }
}

// =========================================================================
// 4. Fetching news & category dropdowns list
// =========================================================================
try {
    // Dropdown Categories
    $secStmt = $db->query("SELECT * FROM sections ORDER BY id ASC");
    $allSections = $secStmt->fetchAll();
    
    // News listings table
    if ($adminRole === 'admin') {
        $articlesStmt = $db->query("SELECT * FROM news ORDER BY date_published DESC");
    } else {
        // Editors only see news in their permitted categories
        $allowedSections = $_SESSION['palghar_live_admin_permissions'];
        if (empty($allowedSections)) {
            $articlesStmt = null;
        } else {
            $placeholders = implode(',', array_fill(0, count($allowedSections), '?'));
            $articlesStmt = $db->prepare("SELECT * FROM news WHERE category IN ($placeholders) ORDER BY date_published DESC");
            $articlesStmt->execute($allowedSections);
        }
    }
    $articlesList = $articlesStmt ? $articlesStmt->fetchAll() : [];
} catch (PDOException $e) {
    error_log("Preload listings error: " . $e->getMessage());
    $allSections = [];
    $articlesList = [];
}
?>

<?php if ($message !== ''): ?>
    <div class="stat-card" style="background-color:#dcfce7; border-left:4px solid #15803d; padding:12px 18px; border-radius:4px; margin-bottom:20px;">
        <span style="color:#166534; font-weight:bold;">✓ Success:</span> <?php echo htmlspecialchars($message); ?>
    </div>
<?php elseif ($error !== ''): ?>
    <div class="stat-card" style="background-color:#fee2e2; border-left:4px solid #b91c1c; padding:12px 18px; border-radius:4px; margin-bottom:20px;">
        <span style="color:#991b1b; font-weight:bold;">✗ Access Gate Warning:</span> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- News Publish Form -->
<div class="form-card" style="box-shadow: none; border-color: var(--border-color); padding: 30px; margin-bottom: 30px;">
    <h3 style="margin-bottom: 25px; font-family: var(--font-heading);" id="form-heading-title">
        <?php echo $editArticle ? 'Edit News Article ("' . sanitize($editArticle['title']) . '")' : 'Add New News Article'; ?>
    </h3>
    
    <form id="admin-news-form" method="POST" action="news.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_news">
        <input type="hidden" name="id" value="<?php echo $editArticle ? $editArticle['id'] : ''; ?>">
        
        <div class="form-group">
            <label class="form-label">News Headline *</label>
            <input type="text" class="form-control" name="title" required placeholder="Enter catchy, bold headline..." value="<?php echo $editArticle ? sanitize($editArticle['title']) : ''; ?>">
        </div>

        <div class="form-group-row">
            <div class="form-group">
                <label class="form-label">Category *</label>
                <select class="form-control" name="category" required>
                    <?php foreach ($allSections as $sec): ?>
                        <?php 
                        // Editors can only select categories they own permissions for
                        if ($adminRole === 'editor' && !in_array($sec['id'], $_SESSION['palghar_live_admin_permissions'])) {
                            continue;
                        }
                        $selected = ($editArticle && $editArticle['category'] === $sec['id']) ? 'selected' : '';
                        ?>
                        <option value="<?php echo $sec['id']; ?>" <?php echo $selected; ?>><?php echo sanitize($sec['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Reporter / Author Name *</label>
                <input type="text" class="form-control" name="author" required placeholder="e.g. Staff Reporter, Palghar" value="<?php echo $editArticle ? sanitize($editArticle['author']) : 'Staff Reporter, Palghar'; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">News Summary (Short Highlight) *</label>
            <input type="text" class="form-control" name="summary" required placeholder="Enter 1-2 sentence core highlight..." value="<?php echo $editArticle ? sanitize($editArticle['summary']) : ''; ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Detailed Content *</label>
            <textarea class="form-control" name="content" required placeholder="Enter full detailed news story. Press enter to split paragraphs..." style="min-height: 250px;"><?php echo $editArticle ? sanitize($editArticle['content']) : ''; ?></textarea>
        </div>

        <div class="form-group-row">
            <div class="form-group">
                <label class="form-label">Image URL Link</label>
                <input type="url" class="form-control" id="news-image" name="image_url" placeholder="https://example.com/image.jpg" value="<?php echo $editArticle ? sanitize($editArticle['image_path']) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Or Upload Local Image (Phase 1 Uploads)</label>
                <input type="file" class="form-control" name="image_file" accept="image/*" style="padding: 6px;">
            </div>
            <div class="form-group">
                <label class="form-label">Language</label>
                <select class="form-control" name="language">
                    <option value="en" <?php echo ($editArticle && $editArticle['language'] === 'en') ? 'selected' : ''; ?>>English (en)</option>
                    <option value="mr" <?php echo ($editArticle && $editArticle['language'] === 'mr') ? 'selected' : ''; ?>>Marathi (mr)</option>
                    <option value="hi" <?php echo ($editArticle && $editArticle['language'] === 'hi') ? 'selected' : ''; ?>>Hindi (hi)</option>
                </select>
            </div>
        </div>

        <!-- Suggestive Template Images Gallery -->
        <div class="form-group">
            <label class="form-label" style="font-size: 0.78rem;">Or select from suggested template images below:</label>
            <div class="image-presets-grid" id="news-image-presets" style="display: flex; gap: 10px; margin-top: 5px;">
                <div class="preset-img-option" data-url="https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=800&q=80">
                    <img src="https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=120&q=80" alt="Rain" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;">
                </div>
                <div class="preset-img-option" data-url="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80">
                    <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=120&q=80" alt="Road" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;">
                </div>
                <div class="preset-img-option" data-url="https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80">
                    <img src="https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=120&q=80" alt="Art" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;">
                </div>
                <div class="preset-img-option" data-url="https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80">
                    <img src="https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=120&q=80" alt="Sports" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;">
                </div>
                <div class="preset-img-option" data-url="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80">
                    <img src="https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=120&q=80" alt="Nature" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px; cursor: pointer;">
                </div>
            </div>
        </div>

        <div class="form-group-row" style="margin-top: 15px; border-top: 1px solid var(--border-color); padding-top: 20px;">
            <div style="display: flex; gap: 20px; align-items: center;">
                <label style="font-weight: 600; display:flex; gap: 8px; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="featured" value="1" style="width:18px; height:18px;" <?php echo ($editArticle && $editArticle['featured'] == 1) ? 'checked' : ''; ?>> Show in Main Featured News (Featured)
                </label>
                <label style="font-weight: 600; display:flex; gap: 8px; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="trending" value="1" style="width:18px; height:18px;" <?php echo (!$editArticle || $editArticle['trending'] == 1) ? 'checked' : ''; ?>> Include in Trending Column (Trending)
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 25px;">
            <button type="submit" class="btn-submit" style="flex: 2;"><?php echo $editArticle ? 'Save Changes' : 'Publish News Story'; ?></button>
            <?php if ($editArticle): ?>
                <a href="news.php" class="btn-submit btn-cancel" style="background-color: var(--text-muted); flex: 1; text-align:center; text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- News Table Listings -->
<div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
    <input type="text" placeholder="Search news tables..." class="form-control" style="width: 300px; padding: 10px;" id="admin-search-input">
</div>

<div class="admin-table-container" style="margin-bottom: 30px;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Date Published</th>
                <th>Views</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="admin-articles-table-body">
            <?php if (empty($articlesList)): ?>
                <tr><td colspan="5" style="text-align:center; color:var(--text-muted);">No news stories published in your permitted category.</td></tr>
            <?php else: ?>
                <?php foreach ($articlesList as $item): ?>
                    <tr>
                        <td style="font-weight: 600; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?php echo $item['featured'] == 1 ? '<span style="color:#D4AF37;">★</span> ' : ''; ?><?php echo sanitize($item['title']); ?>
                        </td>
                        <td><span class="badge info"><?php echo getCategoryLabel($item['category']); ?></span></td>
                        <td><?php echo date("M d, Y", strtotime($item['date_published'])); ?></td>
                        <td><?php echo $item['views']; ?> Readers</td>
                        <td class="actions-cell">
                            <a href="news.php?action=edit&id=<?php echo $item['id']; ?>" class="btn-action edit" style="text-decoration:none;"><i class="far fa-edit"></i> Edit</a>
                            <a href="news.php?action=delete&id=<?php echo $item['id']; ?>" class="btn-action delete" style="text-decoration:none;" onclick="return confirm('Are you sure you want to permanently delete this news story?');"><i class="far fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    // Search filter helper
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('admin-search-input');
        if (searchInput) {
            searchInput.addEventListener('keyup', () => {
                const query = searchInput.value.toLowerCase();
                const rows = document.querySelectorAll('#admin-articles-table-body tr');
                rows.forEach(row => {
                    const title = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                    const cat = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                    if (row.cells.length > 1) {
                        row.style.display = (title.includes(query) || cat.includes(query)) ? '' : 'none';
                    }
                });
            });
        }
        
        // News presets helper
        const presets = document.querySelectorAll('.preset-img-option');
        const imgInput = document.getElementById('news-image');
        presets.forEach(p => {
            p.addEventListener('click', () => {
                presets.forEach(el => el.classList.remove('selected'));
                p.classList.add('selected');
                imgInput.value = p.dataset.url;
            });
        });
    });
</script>

<?php
require_once 'footer.php';
?>
