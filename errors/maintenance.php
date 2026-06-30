<?php
// errors/maintenance.php — Maintenance Mode page

http_response_code(503);
header("Retry-After: 3600");

$cssHash = file_exists(__DIR__ . '/../assets/css/style.min.css') ? filemtime(__DIR__ . '/../assets/css/style.min.css') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | Palghar LIVE</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($cssHash): ?><link rel="stylesheet" href="../assets/css/style.min.css?v=<?php echo $cssHash; ?>"><?php endif; ?>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Inter', sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #e2e8f0; }
        .maintenance-page { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px; }
        .logo-area img { height: 80px; border-radius: 8px; }
        .maintenance-icon { font-size: 5rem; margin: 24px 0 8px; animation: spin-slow 6s linear infinite; display: inline-block; }
        @keyframes spin-slow { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .maintenance-title { font-size: clamp(1.5rem, 4vw, 2.5rem); font-weight: 800; margin: 16px 0 12px; background: linear-gradient(135deg, #E31B23, #fbbf24); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .maintenance-desc { color: #94a3b8; font-size: 1rem; max-width: 500px; line-height: 1.7; margin-bottom: 32px; }
        .social-links { display: flex; gap: 16px; justify-content: center; margin-top: 24px; }
        .social-links a { color: #94a3b8; font-size: 1.5rem; transition: color 0.2s; }
        .social-links a:hover { color: #E31B23; }
        .progress-bar { width: 280px; height: 6px; background: rgba(255,255,255,0.1); border-radius: 6px; overflow: hidden; margin: 0 auto 24px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #E31B23, #fbbf24); animation: progress-anim 3s ease-in-out infinite alternate; border-radius: 6px; }
        @keyframes progress-anim { from { width: 20%; } to { width: 85%; } }
    </style>
</head>
<body>
    <div class="maintenance-page">
        <div class="logo-area">
            <img src="../assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Palghar LIVE" onerror="this.style.display='none'">
        </div>
        <div class="maintenance-icon">⚙️</div>
        <h1 class="maintenance-title">We'll Be Right Back!</h1>
        <p class="maintenance-desc">
            Palghar LIVE is currently undergoing scheduled maintenance to bring you a better experience. 
            We will be back online shortly. Thank you for your patience!
        </p>
        <div class="progress-bar"><div class="progress-fill"></div></div>
        <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 16px;">Follow us for live updates during maintenance:</p>
        <div class="social-links">
            <a href="https://www.facebook.com/share/1DzefDPcC2/" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-square"></i></a>
            <a href="https://www.youtube.com/@palgharlivenews" target="_blank" aria-label="YouTube"><i class="fab fa-youtube-square"></i></a>
        </div>
        <p style="margin-top: 40px; color: #334155; font-size: 0.78rem;">
            &copy; <?php echo date('Y'); ?> Palghar LIVE — The Strong Voice of the Common People
        </p>
    </div>
</body>
</html>
