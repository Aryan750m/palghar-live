<?php
// Admin Health Monitor: admin/health.php
// System diagnostics dashboard - Target Score: 10/10

require_once __DIR__ . '/../app/autoload.php';

\App\Services\PerformanceProfiler::start();
\App\Middleware\SecurityHeaders::apply();
\App\Middleware\RateLimiter::check('admin');

if (!\App\Services\AuthManager::checkSession()) {
    header("Location: login.php");
    exit;
}

// Only admins can view health panel
$adminRole = $_SESSION['palghar_live_admin_role'] ?? 'editor';
if ($adminRole !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// =========================================================================
// Run All Diagnostics
// =========================================================================
$checks = [];

// 1. PHP Version
$phpVersion = PHP_VERSION;
$phpOk = version_compare($phpVersion, '8.0.0', '>=');
$checks[] = [
    'label'   => 'PHP Version',
    'value'   => $phpVersion,
    'status'  => $phpOk ? 'ok' : 'warn',
    'note'    => $phpOk ? 'PHP 8.0+ detected ✓' : 'PHP 8.0 or higher is strongly recommended',
    'icon'    => 'fa-php',
];

// 2. Database Connection
$dbStatus = 'ok'; $dbNote = 'Connected successfully ✓';
try {
    $pdo = \App\Database::getConnection();
    $dbVersion = $pdo->query('SELECT VERSION()')->fetchColumn();
    $dbValue = 'MySQL ' . $dbVersion;
} catch (Exception $e) {
    $dbStatus = 'error'; $dbNote = 'Connection failed: ' . $e->getMessage();
    $dbValue = 'N/A';
}
$checks[] = [
    'label'  => 'Database Connection',
    'value'  => $dbValue,
    'status' => $dbStatus,
    'note'   => $dbNote,
    'icon'   => 'fa-database',
];

// 3. GD Image Library
$gdEnabled = extension_loaded('gd') && function_exists('imagecreatefromjpeg');
$gdInfo = $gdEnabled ? gd_info() : [];
$webpSupport = !empty($gdInfo['WebP Support']);
$avifSupport = !empty($gdInfo['AVIF Support']);
$checks[] = [
    'label'  => 'GD Image Library',
    'value'  => $gdEnabled ? 'Enabled (WebP: ' . ($webpSupport ? 'Yes' : 'No') . ' / AVIF: ' . ($avifSupport ? 'Yes' : 'No') . ')' : 'Not Loaded',
    'status' => $gdEnabled ? 'ok' : 'error',
    'note'   => $gdEnabled ? 'Image processing available ✓' : 'GD extension missing — uploads will be disabled',
    'icon'   => 'fa-image',
];

// 4. Writable Directories
$dirs = [
    '../uploads/news/'  => 'uploads/news/',
    '../logs/'          => 'logs/',
    '../assets/css/'    => 'assets/css/',
    '../assets/js/'     => 'assets/js/',
];
foreach ($dirs as $path => $label) {
    $absPath = __DIR__ . '/' . $path;
    if (!is_dir($absPath)) {
        @mkdir($absPath, 0755, true);
    }
    $writable = is_writable($absPath);
    $checks[] = [
        'label'  => 'Writable: ' . $label,
        'value'  => $writable ? 'Writable ✓' : 'NOT WRITABLE ✗',
        'status' => $writable ? 'ok' : 'error',
        'note'   => $writable ? 'Directory is properly writable' : 'Run: chmod 755 ' . $label,
        'icon'   => 'fa-folder-open',
    ];
}

// 5. Disk Space
$totalBytes = disk_total_space(__DIR__);
$freeBytes  = disk_free_space(__DIR__);
$usedPct    = $totalBytes > 0 ? round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1) : 0;
$diskStatus = $usedPct > 90 ? 'error' : ($usedPct > 75 ? 'warn' : 'ok');
$checks[] = [
    'label'  => 'Disk Space',
    'value'  => round($freeBytes / 1024 / 1024 / 1024, 2) . ' GB free of ' . round($totalBytes / 1024 / 1024 / 1024, 2) . ' GB (' . $usedPct . '% used)',
    'status' => $diskStatus,
    'note'   => $diskStatus === 'ok' ? 'Disk usage within normal range ✓' : 'Disk usage is high — consider cleanup',
    'icon'   => 'fa-hard-drive',
];

// 6. PHP Upload Limits
$uploadMax  = ini_get('upload_max_filesize');
$postMax    = ini_get('post_max_size');
$memLimit   = ini_get('memory_limit');
$maxExec    = ini_get('max_execution_time');
$checks[] = [
    'label'  => 'PHP Limits',
    'value'  => "Upload: $uploadMax | POST: $postMax | Memory: $memLimit | Max Exec: {$maxExec}s",
    'status' => 'ok',
    'note'   => 'PHP ini limits as configured on this server',
    'icon'   => 'fa-sliders',
];

// 7. SSL / HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
           ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$checks[] = [
    'label'  => 'HTTPS / SSL',
    'value'  => $isHttps ? 'Active' : 'Not detected',
    'status' => $isHttps ? 'ok' : 'warn',
    'note'   => $isHttps ? 'Site is running over HTTPS ✓' : 'HTTPS not detected on this request — verify reverse proxy settings',
    'icon'   => 'fa-lock',
];

// 8. PHP Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'json', 'session', 'openssl'];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'label'  => 'PHP Extension: ' . $ext,
        'value'  => $loaded ? 'Loaded ✓' : 'NOT LOADED ✗',
        'status' => $loaded ? 'ok' : 'error',
        'note'   => $loaded ? "Extension $ext is active" : "Missing extension: $ext — required for this portal",
        'icon'   => 'fa-puzzle-piece',
    ];
}

// 9. Log files status
$logDir = __DIR__ . '/../logs/';
$logFiles = ['application.log', 'security.log', 'performance.log', 'sql.log', 'uploads.log'];
foreach ($logFiles as $logFile) {
    $path  = $logDir . $logFile;
    $exists = file_exists($path);
    $sizeKb = $exists ? round(filesize($path) / 1024, 1) : 0;
    $logStatus = ($exists && $sizeKb > 5000) ? 'warn' : 'ok';
    $checks[] = [
        'label'  => 'Log: ' . $logFile,
        'value'  => $exists ? $sizeKb . ' KB' : 'Not yet created',
        'status' => $logStatus,
        'note'   => $logStatus === 'warn' ? 'Log file is large (>5MB) — consider rotation' : 'Log file within acceptable size',
        'icon'   => 'fa-file-lines',
    ];
}

// 10. Database Table Health
try {
    $tables = ['news', 'sections', 'comments', 'inquiries', 'users', 'user_permissions', 'section_images'];
    foreach ($tables as $table) {
        $count = \App\Database::fetch("SELECT COUNT(*) as c FROM `$table`")['c'] ?? 0;
        $checks[] = [
            'label'  => 'DB Table: ' . $table,
            'value'  => number_format($count) . ' rows',
            'status' => 'ok',
            'note'   => "Table `$table` exists and is accessible ✓",
            'icon'   => 'fa-table',
        ];
    }
} catch (Exception $e) {
    $checks[] = [
        'label'  => 'DB Table Check',
        'value'  => 'Failed',
        'status' => 'error',
        'note'   => $e->getMessage(),
        'icon'   => 'fa-table',
    ];
}

// Count totals
$okCount    = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));
$warnCount  = count(array_filter($checks, fn($c) => $c['status'] === 'warn'));
$errorCount = count(array_filter($checks, fn($c) => $c['status'] === 'error'));
$totalCount = count($checks);

$overallStatus = $errorCount > 0 ? 'error' : ($warnCount > 0 ? 'warn' : 'ok');

$cssHash = file_exists('../assets/css/style.min.css') ? filemtime('../assets/css/style.min.css') : time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Monitor - Palghar LIVE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.min.css?v=<?php echo $cssHash; ?>">
    <style nonce="<?php echo \App\Middleware\SecurityHeaders::getNonce(); ?>">
        .health-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; margin-top: 20px; }
        .health-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; display: flex; gap: 14px; align-items: flex-start; transition: box-shadow 0.2s; }
        .health-card:hover { box-shadow: var(--shadow-md); }
        .health-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1rem; }
        .health-icon.ok    { background: #dcfce7; color: #16a34a; }
        .health-icon.warn  { background: #fef3c7; color: #d97706; }
        .health-icon.error { background: #fee2e2; color: #dc2626; }
        .health-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 4px; }
        .health-value { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 4px; }
        .health-note { font-size: 0.78rem; color: var(--text-muted); }
        .status-badge.ok    { background: #dcfce7; color: #16a34a; }
        .status-badge.warn  { background: #fef3c7; color: #d97706; }
        .status-badge.error { background: #fee2e2; color: #dc2626; }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .summary-bar { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .summary-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 16px 24px; text-align: center; min-width: 100px; }
        .summary-stat .num { font-size: 2rem; font-weight: 800; }
        .summary-stat .lbl { font-size: 0.78rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
    </style>
</head>
<body>
<?php require_once 'header.php'; ?>

<div class="admin-page-content">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-family: var(--font-heading); font-size: 1.6rem; margin: 0;">
                <i class="fas fa-stethoscope" style="color: var(--primary);"></i> System Health Monitor
            </h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">
                Real-time diagnostics for the Palghar LIVE server environment. Last run: <?php echo date("D, d M Y H:i:s T"); ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="status-badge <?php echo $overallStatus; ?>">
                <?php if ($overallStatus === 'ok'): ?>All Systems Operational
                <?php elseif ($overallStatus === 'warn'): ?>Warnings Detected
                <?php else: ?>Critical Issues Found
                <?php endif; ?>
            </span>
            <a href="health.php" class="btn-action edit" style="text-decoration:none;"><i class="fas fa-rotate-right"></i> Refresh</a>
        </div>
    </div>

    <!-- Summary Counts -->
    <div class="summary-bar">
        <div class="summary-stat">
            <div class="num" style="color: var(--text-primary);"><?php echo $totalCount; ?></div>
            <div class="lbl">Total Checks</div>
        </div>
        <div class="summary-stat">
            <div class="num" style="color: #16a34a;"><?php echo $okCount; ?></div>
            <div class="lbl">Passed</div>
        </div>
        <div class="summary-stat">
            <div class="num" style="color: #d97706;"><?php echo $warnCount; ?></div>
            <div class="lbl">Warnings</div>
        </div>
        <div class="summary-stat">
            <div class="num" style="color: #dc2626;"><?php echo $errorCount; ?></div>
            <div class="lbl">Errors</div>
        </div>
    </div>

    <!-- Diagnostic Cards Grid -->
    <div class="health-grid">
        <?php foreach ($checks as $check): ?>
            <div class="health-card">
                <div class="health-icon <?php echo $check['status']; ?>">
                    <i class="fas <?php echo htmlspecialchars($check['icon']); ?>"></i>
                </div>
                <div style="min-width: 0;">
                    <div class="health-label"><?php echo htmlspecialchars($check['label']); ?></div>
                    <div class="health-value"><?php echo htmlspecialchars($check['value']); ?></div>
                    <div class="health-note"><?php echo htmlspecialchars($check['note']); ?></div>
                </div>
                <div style="margin-left: auto; flex-shrink: 0;">
                    <span class="status-badge <?php echo $check['status']; ?>">
                        <?php echo $check['status'] === 'ok' ? 'OK' : strtoupper($check['status']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; \App\Services\PerformanceProfiler::stop(); ?>
</body>
</html>
