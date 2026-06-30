<?php
// Admin Login: admin/login.php
// Production-ready credentials authentication screen using PHP sessions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['palghar_live_admin_session']) && $_SESSION['palghar_live_admin_session'] === true) {
    header("Location: dashboard.php");
    exit;
}

require_once '../config/db.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if ($username !== '' && $password !== '') {
        try {
            $db = getDatabaseConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'enabled'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Initialize Session
                $_SESSION['palghar_live_admin_session'] = true;
                $_SESSION['palghar_live_admin_username'] = $user['username'];
                $_SESSION['palghar_live_admin_role'] = $user['role'];
                $_SESSION['palghar_live_admin_id'] = $user['id'];
                
                // Fetch permissions if editor
                if ($user['role'] === 'editor') {
                    $pStmt = $db->prepare("SELECT section_id FROM user_permissions WHERE user_id = ?");
                    $pStmt->execute([$user['id']]);
                    $_SESSION['palghar_live_admin_permissions'] = $pStmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $_SESSION['palghar_live_admin_permissions'] = []; // Full access
                }
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Database connection failure.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Palghar LIVE</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh; background-color:var(--bg-main); margin:0;">

    <!-- Access Gate Screen -->
    <div class="admin-gate-overlay" id="admin-login-gate" style="display:flex; position:static; background:none;">
        <div class="admin-gate-card" style="box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid var(--border-color); background-color: var(--bg-card); width: 100%; max-width: 420px;">
            <img src="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Logo" class="admin-gate-logo" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
            <h3>Administrator Login</h3>
            <p>Please enter your credentials to log in.</p>
            
            <?php if ($error !== ''): ?>
                <div class="stat-card" style="background-color:#fee2e2; border-left:4px solid #b91c1c; padding:10px 15px; border-radius:4px; margin-bottom:15px; text-align:left;">
                    <span style="color:#991b1b; font-weight:bold; font-size:0.85rem;">✗ Error:</span>
                    <p style="color:#991b1b; margin:3px 0 0 0; font-size:0.82rem;"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <form id="admin-login-form" method="POST" action="login.php">
                <div class="login-form-group" style="text-align:left;">
                    <label class="form-label" for="login-username">Username</label>
                    <input type="text" name="username" id="login-username" class="form-control" placeholder="Enter username..." required autocomplete="username" style="padding:10px;">
                </div>
                
                <div class="login-form-group" style="text-align:left; margin-top:15px;">
                    <label class="form-label" for="login-password">Password</label>
                    <input type="password" name="password" id="login-password" class="form-control" placeholder="Enter password..." required autocomplete="current-password" style="padding:10px;">
                </div>
                
                <button type="submit" class="btn-submit" id="btn-login-submit" style="margin-top:20px; width:100%; padding:12px; font-weight:bold;">Log In</button>
            </form>
            <div style="margin-top:20px; text-align:center;">
                <a href="../index.php" style="color:var(--primary); font-size:0.85rem; text-decoration:none;"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
            </div>
        </div>
    </div>

</body>
</html>
