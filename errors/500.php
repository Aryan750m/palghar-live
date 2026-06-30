<?php
// errors/500.php — Custom 500 Internal Server Error page

if (function_exists('http_response_code')) {
    http_response_code(500);
}

$cssHash = file_exists(__DIR__ . '/../assets/css/style.min.css') ? filemtime(__DIR__ . '/../assets/css/style.min.css') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error | Palghar LIVE</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($cssHash): ?><link rel="stylesheet" href="../assets/css/style.min.css?v=<?php echo $cssHash; ?>"><?php endif; ?>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; }
        .error-page { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px; }
        .error-code { font-size: clamp(5rem, 12vw, 9rem); font-weight: 900; color: #E31B23; line-height: 1; }
        .error-title { font-size: clamp(1.4rem, 3.5vw, 2rem); font-weight: 700; margin: 16px 0 12px; }
        .error-desc { color: #94a3b8; font-size: 1rem; max-width: 500px; line-height: 1.7; margin-bottom: 32px; }
        .btn-home { display: inline-flex; align-items: center; gap: 8px; background: #E31B23; color: white; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: opacity 0.2s; }
        .btn-home:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <div class="error-page">
        <div style="font-size: 3.5rem; margin-bottom: 12px;">⚙️</div>
        <div class="error-code">500</div>
        <h1 class="error-title">Internal Server Error</h1>
        <p class="error-desc">Something went wrong on our end. Our technical team has been notified and we are working to fix the issue. Please try again in a few minutes.</p>
        <a href="../index.php" class="btn-home"><i class="fas fa-home"></i> Return to Homepage</a>
        <p style="margin-top: 40px; color: #475569; font-size: 0.8rem;">
            Palghar LIVE — The Strong Voice of the Common People
        </p>
    </div>
</body>
</html>
