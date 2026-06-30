<?php
// errors/403.php — Custom 403 Forbidden page

require_once __DIR__ . '/../app/autoload.php';
\App\Middleware\SecurityHeaders::apply();
http_response_code(403);

$cssHash = file_exists('../assets/css/style.min.css') ? filemtime('../assets/css/style.min.css') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Forbidden | Palghar LIVE</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.min.css?v=<?php echo $cssHash; ?>">
    <style>
        .error-page { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px; background: var(--bg-main); }
        .error-code { font-size: clamp(6rem, 15vw, 10rem); font-weight: 900; background: linear-gradient(135deg, #b91c1c, var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; }
        .error-title { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 700; color: var(--text-primary); margin: 16px 0 12px; }
        .error-desc { color: var(--text-muted); font-size: 1rem; max-width: 480px; line-height: 1.6; margin-bottom: 32px; }
        .error-actions { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    </style>
</head>
<body>
    <div class="error-page">
        <div style="font-size: 4rem; margin-bottom: 16px;">🔒</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Access Forbidden</h1>
        <p class="error-desc">You do not have permission to access this page. Please log in with appropriate credentials or contact the site administrator.</p>
        <div class="error-actions">
            <a href="../index.php" class="btn-live"><i class="fas fa-home"></i> Go to Homepage</a>
            <a href="../admin/login.php" class="read-more-btn"><i class="fas fa-user-shield"></i> Admin Login</a>
        </div>
    </div>
</body>
</html>
