<?php
// Settings & Logs: admin/settings.php
// Production-ready server-rendered control panel settings, subscriber inquiries lists, and database tools - Phase 1

require_once 'header.php';

$message = '';
$error = '';

// =========================================================================
// 1. Post/Redirect/Get Form Actions
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Gated role permission check: Only Administrator can clear inquiries or reset
    if ($adminRole !== 'admin') {
        header("Location: settings.php?status=permission_denied");
        exit;
    }
    
    // ACTION A: CLEAR ALL INQUIRIES
    if ($_POST['action'] === 'clear_inquiries') {
        try {
            $db->exec("DELETE FROM inquiries");
            header("Location: settings.php?status=clear_success");
            exit;
        } catch (PDOException $e) {
            error_log("Failed clearing inquiries: " . $e->getMessage());
            header("Location: settings.php?status=action_error");
            exit;
        }
    }
    
    // ACTION B: DELETE SINGLE INQUIRY
    if ($_POST['action'] === 'delete_inquiry') {
        $inqId = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        if ($inqId > 0) {
            try {
                $delStmt = $db->prepare("DELETE FROM inquiries WHERE id = ?");
                $delStmt->execute([$inqId]);
                header("Location: settings.php?status=delete_success");
                exit;
            } catch (PDOException $e) {
                error_log("Failed deleting inquiry: " . $e->getMessage());
                header("Location: settings.php?status=action_error");
                exit;
            }
        }
    }
}

// =========================================================================
// 2. Feedback Status Translator
// =========================================================================
$status = isset($_GET['status']) ? $_GET['status'] : '';
switch ($status) {
    case 'clear_success':
        $message = 'Inquiry logs cleared successfully!';
        break;
    case 'delete_success':
        $message = 'Inquiry item deleted successfully.';
        break;
    case 'permission_denied':
        $error = 'Access Denied: You do not have permissions to modify logs.';
        break;
    case 'action_error':
        $error = 'Database transaction failed.';
        break;
}

// =========================================================================
// 3. Preloading Inquiries Lists
// =========================================================================
try {
    $inqStmt = $db->query("SELECT * FROM inquiries ORDER BY date_received DESC");
    $inquiriesList = $inqStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Preload inquiries failed: " . $e->getMessage());
    $inquiriesList = [];
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

<!-- Visitor Inquiries List -->
<div class="form-card" style="box-shadow: none; border-color: var(--border-color); padding: 30px; margin-bottom: 30px;" id="inquiries">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
        <h3 style="margin:0;"><i class="far fa-envelope"></i> Visitor Inquiries & Leads Log</h3>
        
        <?php if ($adminRole === 'admin' && !empty($inquiriesList)): ?>
            <form method="POST" action="settings.php" onsubmit="return confirm('Warning: This will permanently delete all inquiry history logs on Hostinger database. Do you want to proceed?');">
                <input type="hidden" name="action" value="clear_inquiries">
                <button type="submit" class="btn-live" style="background-color:var(--primary); font-size:0.85rem; box-shadow:none;"><i class="far fa-trash-alt"></i> Clear Logs</button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sender Name</th>
                    <th>Contact Details</th>
                    <th>Inquiry Type</th>
                    <th>Message</th>
                    <th>Received Date</th>
                    <?php if ($adminRole === 'admin'): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inquiriesList)): ?>
                    <tr><td colspan="<?php echo $adminRole === 'admin' ? 6 : 5; ?>" style="text-align:center; color:var(--text-muted);">No visitor inquiries or advertising leads found in database.</td></tr>
                <?php else: ?>
                    <?php foreach ($inquiriesList as $inq): ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo sanitize($inq['name']); ?></td>
                            <td>
                                <span style="display:block; font-size:0.85rem;"><i class="fas fa-phone"></i> <?php echo sanitize($inq['phone']); ?></span>
                                <?php if ($inq['email']): ?>
                                    <span style="display:block; font-size:0.82rem; color:var(--text-muted);"><i class="far fa-envelope"></i> <?php echo sanitize($inq['email']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inq['type'] === 'ad'): ?>
                                    <span class="badge success">Ad Placement</span>
                                <?php elseif ($inq['type'] === 'news_tip'): ?>
                                    <span class="badge info">News Tip</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#e2e8f0; color:#475569;">General</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 320px; font-size:0.85rem; line-height:1.4; white-space:normal; word-break:break-word;">
                                <?php echo nl2br(sanitize($inq['message'])); ?>
                            </td>
                            <td style="font-size: 0.78rem; color: var(--text-muted);"><?php echo date("M d, Y h:i A", strtotime($inq['date_received'])); ?></td>
                            <?php if ($adminRole === 'admin'): ?>
                                <td>
                                    <form method="POST" action="settings.php" onsubmit="return confirm('Are you sure you want to delete this inquiry?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_inquiry">
                                        <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                        <button type="submit" class="btn-action delete" style="border:none; cursor:pointer;"><i class="far fa-trash-alt"></i> Delete</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Database Settings Tools (Only Admin) -->
<?php if ($adminRole === 'admin'): ?>
    <div class="form-card" style="box-shadow: none; border-color: var(--border-color); padding: 30px; margin-bottom: 25px;">
        <h3><i class="fas fa-exclamation-triangle" style="color:var(--primary);"></i> Advanced Database Control Tools</h3>
        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.9rem;">To run a full database table rebuild and re-populate the initial default datasets (including sections, sections slides, articles, inquiries, and users) please follow these safety steps:</p>
        
        <div style="background-color: var(--bg-main); border-left: 4px solid var(--primary); padding: 15px; border-radius: 4px; font-size:0.88rem; line-height:1.6; color:var(--text-muted);">
            <strong>How to Reset Database to Defaults:</strong>
            <ol style="margin:5px 0 0 0; padding-left:20px;">
                <li>Connect to your server via File Manager or FTP.</li>
                <li>Locate the file <code>install.lock</code> at the root directory and delete it.</li>
                <li>Open your browser and navigate to: <a href="../install.php" target="_blank" style="color:var(--primary); font-weight:600; text-decoration:underline;">install.php</a>.</li>
                <li>Submit the setup form to restore initial tables and create fresh default admin user profiles.</li>
            </ol>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
