<?php
/**
 * St. Monica Junior School CMS - Public API Health Check
 * Pings each public JSON API endpoint and reports whether it responds successfully.
 * Internal error detail is never shown here - only reachable/unreachable status.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'Public API Health Check';
$activeMenu = 'system';

$endpoints = [
    'Homepage'        => 'homepage/',
    'Staff'           => 'staff/',
    'News & Events'   => 'news-events/',
    'Gallery'         => 'gallery/',
    'About'           => 'about/',
    'Contact'         => 'contact/',
    'Testimonials'    => 'testimonials/',
    'Admission Info'  => 'admission-info/',
    'SEO'             => 'seo/?page=homepage',
    'Media Library'   => 'media/',
];

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseApiUrl = $protocol . $host . rtrim(admin_url('api'), '/') . '/';

$results = [];
foreach ($endpoints as $label => $path) {
    $url = $baseApiUrl . $path;
    $start = microtime(true);
    $status = 'unreachable';
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => ['Accept: application/json']
        ]);
        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body !== false) {
            $json = json_decode($body, true);
            $status = (is_array($json) && !empty($json['success'])) ? 'ok' : (($httpCode > 0) ? 'error' : 'unreachable');
        }
    }

    $results[$label] = [
        'status'   => $status,
        'httpCode' => $httpCode,
        'timeMs'   => round((microtime(true) - $start) * 1000)
    ];
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('system/') ?>" class="hover:text-slate-800">System Health</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">API Health</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Public API Health Check</h1>
    </div>
    <a href="<?= admin_url('system/api-health.php') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">refresh</span>
        <span>Re-check Now</span>
    </a>
</div>

<div class="cms-card overflow-hidden">
    <table class="cms-table">
        <thead>
            <tr><th>Endpoint</th><th>Status</th><th>HTTP Code</th><th>Response Time</th></tr>
        </thead>
        <tbody>
            <?php foreach ($results as $label => $r): ?>
                <tr>
                    <td class="font-semibold text-slate-800 text-sm"><?= e($label) ?></td>
                    <td>
                        <?php if ($r['status'] === 'ok'): ?>
                            <span class="cms-badge badge-published">Operational</span>
                        <?php elseif ($r['status'] === 'error'): ?>
                            <span class="cms-badge bg-amber-100 text-amber-800">Responding with Errors</span>
                        <?php else: ?>
                            <span class="cms-badge bg-red-100 text-red-700">Unreachable</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-xs font-mono text-slate-500"><?= $r['httpCode'] ?: '-' ?></td>
                    <td class="text-xs text-slate-500"><?= $r['timeMs'] ?>ms</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
