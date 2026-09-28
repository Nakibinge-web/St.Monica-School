<?php
/**
 * St. Monica Junior School CMS - Export newsletter subscribers as CSV
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('newsletter');

$status = in_array($_GET['status'] ?? '', ['subscribed', 'unsubscribed'], true) ? $_GET['status'] : '';

$rows = Database::fetchAll(
    "SELECT `email`, `status`, `source`, `subscribed_at`, `unsubscribed_at` FROM `newsletter_subscribers`"
    . ($status ? " WHERE `status` = :status" : '') . " ORDER BY `subscribed_at` DESC",
    $status ? ['status' => $status] : []
);

log_activity('Exported Newsletter Subscribers', count($rows) . ' row(s)' . ($status ? " ({$status})" : ''), 'newsletter');

$filename = 'newsletter-subscribers' . ($status ? "-{$status}" : '') . '-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows characters correctly
fputcsv($out, ['Email', 'Status', 'Signed up from', 'Subscribed at', 'Unsubscribed at']);
foreach ($rows as $r) {
    // Prefix values Excel would treat as formulas (CSV injection guard)
    $safe = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, [
        $r['email'], $r['status'], $r['source'] === 'admin' ? 'Added by admin' : ($r['source'] ?: 'Website'),
        $r['subscribed_at'], $r['unsubscribed_at'] ?? '',
    ]);
    fputcsv($out, $safe);
}
fclose($out);
