<?php
// Section Management: admin/sections.php
// Production-ready server-rendered category sections slides editor utilizing PRG pattern - Phase 1

require_once 'header.php';

$message = '';
$error = '';

$selectedSecId = isset($_GET['sec_id']) ? trim($_GET['sec_id']) : '';

// =========================================================================
// 1. Fetching Section and Permissions Data
// =========================================================================
try {
    $secListStmt = $db->query("SELECT * FROM sections ORDER BY id ASC");
    $allSections = $secListStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Preload listings error: " . $e->getMessage());
    $allSections = [];
}

// Editor permission gate
$permittedSections = [];
foreach ($allSections as $s) {
    if ($adminRole === 'admin' || in_array($s['id'], $_SESSION['palghar_live_admin_permissions'])) {
        $permittedSections[] = $s;
    }
}

// Fallback selected section to first permitted section
if ($selectedSecId === '' && !empty($permittedSections)) {
    $selectedSecId = $permittedSections[0]['id'];
}

// Gate check for targeted selected section
$isPermitted = false;
$sectionInfo = null;
foreach ($permittedSections as $ps) {
    if ($ps['id'] === $selectedSecId) {
        $isPermitted = true;
        $sectionInfo = $ps;
        break;
    }
}

if ($selectedSecId !== '' && !$isPermitted) {
    header("Location: sections.php?status=permission_denied");
    exit;
}

// =========================================================================
// 2. Post/Redirect/Get Form Actions
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ACTION A: UPDATE METADATA (Title & Description)
    if ($_POST['action'] === 'update_meta') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        if ($title !== '') {
            try {
                $upStmt = $db->prepare("UPDATE sections SET title = ?, description = ? WHERE id = ?");
                $upStmt->execute([$title, $desc, $selectedSecId]);
                
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=meta_success");
                exit;
            } catch (PDOException $e) {
                error_log("Failed saving section info: " . $e->getMessage());
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=save_error");
                exit;
            }
        }
    }
    
    // ACTION B: ADD GALLERY IMAGE
    if ($_POST['action'] === 'add_image') {
        $imageUrl = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
        $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
        
        $finalImagePath = $imageUrl;
        
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileName = $_FILES['image_file']['name'];
            $fileSize = $_FILES['image_file']['size'];
            
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExt, $allowedExts) && $fileSize <= 5 * 1024 * 1024) {
                $uploadDir = '../uploads/sections/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = md5(time() . $fileName) . '.' . $fileExt;
                $targetFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmp, $targetFile)) {
                    $finalImagePath = 'uploads/sections/' . $newFileName;
                }
            } else {
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=invalid_file");
                exit;
            }
        }
        
        if ($finalImagePath !== '') {
            try {
                // Find immediate next sort order
                $ordStmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM section_images WHERE section_id = ?");
                $ordStmt->execute([$selectedSecId]);
                $nextOrder = intval($ordStmt->fetchColumn()) + 1;
                
                $insStmt = $db->prepare("INSERT INTO section_images (section_id, image_path, caption, sort_order) VALUES (?, ?, ?, ?)");
                $insStmt->execute([$selectedSecId, $finalImagePath, $caption, $nextOrder]);
                
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=image_added");
                exit;
            } catch (PDOException $e) {
                error_log("Failed saving section image: " . $e->getMessage());
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=save_error");
                exit;
            }
        } else {
            header("Location: sections.php?sec_id=" . $selectedSecId . "&status=missing_image");
            exit;
        }
    }
    
    // ACTION C: REPLACE GALLERY IMAGE
    if ($_POST['action'] === 'replace_image') {
        $imageId = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
        
        if ($imageId > 0 && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['image_file']['tmp_name'];
            $fileName = $_FILES['image_file']['name'];
            $fileSize = $_FILES['image_file']['size'];
            
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExt, $allowedExts) && $fileSize <= 5 * 1024 * 1024) {
                $uploadDir = '../uploads/sections/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = md5(time() . $fileName) . '.' . $fileExt;
                $targetFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmp, $targetFile)) {
                    try {
                        // Fetch old file path to delete from server disk
                        $oldImgStmt = $db->prepare("SELECT image_path FROM section_images WHERE id = ? AND section_id = ?");
                        $oldImgStmt->execute([$imageId, $selectedSecId]);
                        $oldPath = $oldImgStmt->fetchColumn();
                        if ($oldPath && strpos($oldPath, 'uploads/') === 0 && file_exists('../' . $oldPath)) {
                            unlink('../' . $oldPath);
                        }
                        
                        $upImgStmt = $db->prepare("UPDATE section_images SET image_path = ? WHERE id = ? AND section_id = ?");
                        $upImgStmt->execute(['uploads/sections/' . $newFileName, $imageId, $selectedSecId]);
                        
                        header("Location: sections.php?sec_id=" . $selectedSecId . "&status=image_replaced");
                        exit;
                    } catch (PDOException $e) {
                        error_log("Failed replacing section image: " . $e->getMessage());
                        header("Location: sections.php?sec_id=" . $selectedSecId . "&status=save_error");
                        exit;
                    }
                }
            } else {
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=invalid_file");
                exit;
            }
        }
    }
}

// =========================================================================
// 3. GET Actions (Delete image & reorder arrows shift)
// =========================================================================
if (isset($_GET['action'])) {
    
    // ACTION D: DELETE IMAGE
    if ($_GET['action'] === 'delete_image' && isset($_GET['image_id'])) {
        $imgId = intval($_GET['image_id']);
        if ($imgId > 0) {
            try {
                // Delete old file
                $oldImgStmt = $db->prepare("SELECT image_path FROM section_images WHERE id = ? AND section_id = ?");
                $oldImgStmt->execute([$imgId, $selectedSecId]);
                $oldPath = $oldImgStmt->fetchColumn();
                if ($oldPath && strpos($oldPath, 'uploads/') === 0 && file_exists('../' . $oldPath)) {
                    unlink('../' . $oldPath);
                }
                
                // Delete DB record
                $delImgStmt = $db->prepare("DELETE FROM section_images WHERE id = ? AND section_id = ?");
                $delImgStmt->execute([$imgId, $selectedSecId]);
                
                // Normalize sort orders of remaining images
                $normStmt = $db->prepare("SELECT id FROM section_images WHERE section_id = ? ORDER BY sort_order ASC");
                $normStmt->execute([$selectedSecId]);
                $remaining = $normStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $upOrdStmt = $db->prepare("UPDATE section_images SET sort_order = ? WHERE id = ?");
                foreach ($remaining as $idx => $idVal) {
                    $upOrdStmt->execute([$idx + 1, $idVal]);
                }
                
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=image_deleted");
                exit;
            } catch (PDOException $e) {
                error_log("Failed deleting section image: " . $e->getMessage());
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=delete_error");
                exit;
            }
        }
    }
    
    // ACTION E: MOVE IMAGE (Up / Down)
    if ($_GET['action'] === 'move_image' && isset($_GET['image_id']) && isset($_GET['dir'])) {
        $imgId = intval($_GET['image_id']);
        $dir = trim($_GET['dir']);
        
        if ($imgId > 0 && ($dir === 'up' || $dir === 'down')) {
            try {
                // Get current sort order of targeted image
                $curOrdStmt = $db->prepare("SELECT sort_order FROM section_images WHERE id = ? AND section_id = ?");
                $curOrdStmt->execute([$imgId, $selectedSecId]);
                $currentOrder = intval($curOrdStmt->fetchColumn());
                
                if ($currentOrder > 0) {
                    $targetOrder = $dir === 'up' ? $currentOrder - 1 : $currentOrder + 1;
                    
                    // Verify target order exists
                    $checkStmt = $db->prepare("SELECT id FROM section_images WHERE sort_order = ? AND section_id = ?");
                    $checkStmt->execute([$targetOrder, $selectedSecId]);
                    $swappingId = $checkStmt->fetchColumn();
                    
                    if ($swappingId) {
                        // Perform transaction swap
                        $db->beginTransaction();
                        $upStmt1 = $db->prepare("UPDATE section_images SET sort_order = ? WHERE id = ?");
                        $upStmt1->execute([$targetOrder, $imgId]);
                        $upStmt2 = $db->prepare("UPDATE section_images SET sort_order = ? WHERE id = ?");
                        $upStmt2->execute([$currentOrder, $swappingId]);
                        $db->commit();
                    }
                }
                
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=reorder_success");
                exit;
            } catch (PDOException $e) {
                if ($db->inTransaction()) $db->rollBack();
                error_log("Swap reorder failed: " . $e->getMessage());
                header("Location: sections.php?sec_id=" . $selectedSecId . "&status=reorder_error");
                exit;
            }
        }
    }
}

// =========================================================================
// 4. Feedback Status Messages
// =========================================================================
$status = isset($_GET['status']) ? $_GET['status'] : '';
switch ($status) {
    case 'meta_success':
        $message = 'Section title and description updated successfully!';
        break;
    case 'image_added':
        $message = 'Image slide added successfully!';
        break;
    case 'image_replaced':
        $message = 'Image slide replaced successfully!';
        break;
    case 'image_deleted':
        $message = 'Image slide removed successfully.';
        break;
    case 'reorder_success':
        $message = 'Slides sequence reordered successfully.';
        break;
    case 'permission_denied':
        $error = 'Access Denied: You do not possess permissions to edit that category.';
        break;
    case 'invalid_file':
        $error = 'Upload error: Make sure file size is under 5MB and extension is JPG, PNG or WEBP.';
        break;
    case 'missing_image':
        $error = 'Please enter an image URL link or choose a local file to upload.';
        break;
    case 'save_error':
    case 'delete_error':
    case 'reorder_error':
        $error = 'Database transaction failed.';
        break;
}

// =========================================================================
// 5. Preloading Slides list for targeted section
// =========================================================================
$bannerImages = [];
if ($sectionInfo) {
    try {
        $imgListStmt = $db->prepare("SELECT * FROM section_images WHERE section_id = ? ORDER BY sort_order ASC");
        $imgListStmt->execute([$selectedSecId]);
        $bannerImages = $imgListStmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Preload images failed: " . $e->getMessage());
    }
}
?>

<?php if ($message !== ''): ?>
    <div class="stat-card" style="background-color:#dcfce7; border-left:4px solid #15803d; padding:12px 18px; border-radius:4px; margin-bottom:20px;">
        <span style="color:#166534; font-weight:bold;">✓ Success:</span> <?php echo htmlspecialchars($message); ?>
    </div>
<?php elseif ($error !== ''): ?>
    <div class="stat-card" style="background-color:#fee2e2; border-left:4px solid #b91c1c; padding:12px 18px; border-radius:4px; margin-bottom:20px;">
        <span style="color:#991b1b; font-weight:bold;">✗ Error:</span> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="form-card" style="box-shadow: none; border-color: var(--border-color); padding: 30px; margin-bottom: 30px;">
    <h3 style="margin-bottom: 10px;">Manage Homepage Category Sections</h3>
    <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.9rem;">Select a news section below to edit its landing info, description banner, and image slideshow gallery.</p>
    
    <div class="form-group" style="margin-bottom: 20px;">
        <label class="form-label" for="section-select" style="font-weight:bold; display:block; margin-bottom:5px;">Select Section Category</label>
        <select class="form-control" id="section-select" onchange="window.location.href='sections.php?sec_id=' + this.value;">
            <?php foreach ($permittedSections as $ps): ?>
                <option value="<?php echo $ps['id']; ?>" <?php echo $selectedSecId === $ps['id'] ? 'selected' : ''; ?>><?php echo sanitize($ps['title']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <?php if ($sectionInfo): ?>
        <!-- Section Metadata Form -->
        <form id="admin-section-form" method="POST" action="sections.php?sec_id=<?php echo $selectedSecId; ?>">
            <input type="hidden" name="action" value="update_meta">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="font-weight:bold; display:block; margin-bottom:5px;">Section Banner Title *</label>
                <input type="text" class="form-control" name="title" required placeholder="e.g. Sports Updates" value="<?php echo sanitize($sectionInfo['title']); ?>">
            </div>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="font-weight:bold; display:block; margin-bottom:5px;">Section Summary & Description *</label>
                <textarea class="form-control" name="description" required placeholder="Enter description of what this section showcases..." style="min-height: 80px;"><?php echo sanitize($sectionInfo['description']); ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit" style="width:100%; font-weight:bold; margin-bottom:30px;">Save Section Information</button>
        </form>

        <div style="border-top:1px solid var(--border-color); padding-top:25px; margin-top:25px;">
            <h4 style="margin-bottom: 5px; font-family: var(--font-heading);">Image Gallery & Slideshow Manager</h4>
            <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.85rem;">Upload local files or add image links. Use the arrow buttons to shift sequence sorting. Updates are immediately saved and apply to homepage slider rendering.</p>
            
            <!-- Upload Image Sub-form -->
            <form method="POST" action="sections.php?sec_id=<?php echo $selectedSecId; ?>" enctype="multipart/form-data" style="margin-bottom:25px; padding:20px; background-color:var(--bg-main); border-radius:6px; border:1px solid var(--border-color);">
                <input type="hidden" name="action" value="add_image">
                <div class="image-upload-row" style="display: flex; gap: 15px; align-items: flex-start; flex-wrap: wrap;">
                    <div class="file-upload-block" style="flex: 1; min-width: 200px;">
                        <label class="file-upload-label" style="display: flex; align-items: center; justify-content: center; gap: 10px; border: 2px dashed var(--border-color); border-radius: 4px; padding: 15px; cursor: pointer; font-weight: bold; background-color: var(--bg-card); transition: var(--transition);">
                            <i class="fas fa-cloud-upload-alt" style="color: var(--primary); font-size: 1.2rem;"></i> Upload Local File
                        </label>
                        <input type="file" name="image_file" accept="image/*" class="file-upload-input" style="display: none;" onchange="this.previousElementSibling.innerHTML='<i class=\'fas fa-check\' style=\'color:green;\'></i> ' + this.files[0].name;">
                    </div>
                    <div style="flex: 2; display: flex; flex-direction: column; gap: 8px; min-width: 250px;">
                        <input type="url" class="form-control" name="image_url" placeholder="Or enter external image URL (e.g. https://...)">
                        <input type="text" class="form-control" name="caption" placeholder="Enter slide overlay caption (optional)">
                    </div>
                    <button type="submit" class="btn-live" style="background-color: var(--primary); border: none; padding: 12px 20px; font-weight: bold; cursor: pointer; border-radius: 4px; color:#fff; display:flex; align-items:center; gap:8px; height:44px;"><i class="fas fa-plus"></i> Add Image</button>
                </div>
            </form>
            
            <!-- Slides Listings Grid -->
            <div class="media-manager-grid" id="media-manager-grid">
                <?php if (empty($bannerImages)): ?>
                    <div style="grid-column: 1/-1; padding: 25px 0; text-align:center; color: var(--text-muted);">No images loaded in this section yet. Add some to get started.</div>
                <?php else: ?>
                    <?php foreach ($bannerImages as $idx => $img): ?>
                        <div class="media-manager-item">
                            <div class="media-thumb">
                                <img src="../<?php echo $img['image_path']; ?>" alt="Section Slide" onerror="this.src='https://via.placeholder.com/300x200?text=Broken+Image'">
                                <span class="media-order-badge">Slide 0<?php echo $idx + 1; ?></span>
                            </div>
                            <input type="text" class="media-caption-input" placeholder="No caption" value="<?php echo sanitize($img['caption']); ?>" disabled style="background-color:var(--bg-main);">
                            <div class="media-manager-actions" style="margin-top:10px; display:flex; justify-content:space-between; align-items:center; width:100%;">
                                <div class="media-reorder-arrows" style="display:flex; gap:5px;">
                                    <?php if ($idx > 0): ?>
                                        <a href="sections.php?sec_id=<?php echo $selectedSecId; ?>&action=move_image&image_id=<?php echo $img['id']; ?>&dir=up" class="media-arrow-btn" title="Move Up"><i class="fas fa-arrow-left"></i></a>
                                    <?php else: ?>
                                        <span class="media-arrow-btn disabled" style="opacity:0.3; cursor:not-allowed;"><i class="fas fa-arrow-left"></i></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($idx < count($bannerImages) - 1): ?>
                                        <a href="sections.php?sec_id=<?php echo $selectedSecId; ?>&action=move_image&image_id=<?php echo $img['id']; ?>&dir=down" class="media-arrow-btn" title="Move Down"><i class="fas fa-arrow-right"></i></a>
                                    <?php else: ?>
                                        <span class="media-arrow-btn disabled" style="opacity:0.3; cursor:not-allowed;"><i class="fas fa-arrow-right"></i></span>
                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap: 5px;">
                                    <!-- Replace image trigger form -->
                                    <form method="POST" action="sections.php?sec_id=<?php echo $selectedSecId; ?>" enctype="multipart/form-data" style="display:inline;">
                                        <input type="hidden" name="action" value="replace_image">
                                        <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                                        <label class="media-arrow-btn" style="cursor:pointer;" title="Replace Image">
                                            <i class="fas fa-sync"></i>
                                            <input type="file" name="image_file" accept="image/*" style="display:none;" onchange="this.form.submit();">
                                        </label>
                                    </form>
                                    <a href="sections.php?sec_id=<?php echo $selectedSecId; ?>&action=delete_image&image_id=<?php echo $img['id']; ?>" class="media-arrow-btn" style="color:var(--primary);" title="Remove Image" onclick="return confirm('Are you sure you want to remove this image from the section?');"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>
