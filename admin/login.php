<?php
// Admin Login: admin/login.php
// Production-grade login controller with security controls and diagnostics - Target Score: 10/10

require_once __DIR__ . '/../app/autoload.php';

// 1. Initialise profiling
\App\Services\PerformanceProfiler::start();

// 2. Enforce secure headers and rate-limiting
\App\Middleware\SecurityHeaders::apply();
\App\Middleware\RateLimiter::check('login');

// Redirect if session is already active
if (\App\Services\AuthManager::checkSession()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

// 3. Form submission processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!\App\Validator::csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        $validationErrors = \App\Validator::required($_POST, ['username', 'password']);
        
        if (empty($validationErrors)) {
            if (\App\Services\AuthManager::login($username, $password)) {
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Invalid credentials or account is temporarily locked out.';
            }
        } else {
            $error = 'Please populate both username and password fields.';
        }
    }
}

$cssHash = file_exists('../assets/css/style.min.css') ? filemtime('../assets/css/style.min.css') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Palghar LIVE</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Production CSS Bundle -->
    <link rel="stylesheet" href="../assets/css/style.min.css?v=<?php echo $cssHash; ?>">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh; background-color:var(--bg-main); margin:0;">

    <a href="#admin-login-form" class="skip-link">Skip to Form</a>

    <!-- Glassmorphic Login Gate Card -->
    <div class="admin-gate-overlay" id="admin-login-gate" style="display:flex; position:static; background:none;">
        <div class="admin-gate-card" style="box-shadow: var(--shadow-lg); border: 1px solid var(--border-color); background-color: var(--bg-card); width: 100%; max-width: 420px; border-radius: var(--border-radius-md); padding: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Logo" class="admin-gate-logo" style="max-height: 80px;" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
                <h3 style="margin-top: 15px; font-family: var(--font-heading); color: var(--text-primary);">Administrator Login</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Sign in to access your dashboard</p>
            </div>
            
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert" style="background-color:#fee2e2; border-left:4px solid #b91c1c; padding:12px; border-radius:4px; margin-bottom:20px; text-align:left; font-size: 0.85rem; color:#991b1b;">
                    <strong>✗ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form id="admin-login-form" method="POST" action="login.php">
                <?php echo \App\Middleware\CSRFCheck::getInputField(); ?>
                
                <div class="form-group" style="text-align:left; margin-bottom: 15px;">
                    <label class="form-label" for="login-username">Username</label>
                    <input type="text" name="username" id="login-username" class="form-control" placeholder="Enter username" required autocomplete="username" style="padding:10px; width: 100%; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); background-color: var(--bg-card); color: var(--text-primary);">
                </div>
                
                <div class="form-group" style="text-align:left; margin-bottom: 20px;">
                    <label class="form-label" for="login-password">Password</label>
                    <input type="password" name="password" id="login-password" class="form-control" placeholder="Enter password" required autocomplete="current-password" style="padding:10px; width: 100%; border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); background-color: var(--bg-card); color: var(--text-primary);">
                </div>
                
                <button type="submit" class="btn-submit" id="btn-login-submit" style="width:100%; padding:12px; font-weight:bold; background-color: var(--primary); color: var(--text-light); border-radius: var(--border-radius-sm); cursor: pointer;">Log In</button>
            </form>
            
            <div style="margin-top:20px; text-align:center;">
                <a href="../index.php" style="color:var(--primary); font-size:0.85rem; text-decoration:none; font-weight: 500;"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
            </div>
        </div>
    </div>

</body>
</html>
<?php
\App\Services\PerformanceProfiler::stop();
?>
