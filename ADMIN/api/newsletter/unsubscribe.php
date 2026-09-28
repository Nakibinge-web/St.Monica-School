<?php
/**
 * St. Monica Junior School - Newsletter Unsubscribe
 * GET  ?token=...  -> confirmation page with an "Unsubscribe" button (a plain GET never
 *                     unsubscribes, because email security scanners open links automatically)
 * POST ?token=...  -> unsubscribes. Also serves one-click unsubscribe (RFC 8058) from
 *                     Gmail/Outlook, which POST "List-Unsubscribe=One-Click".
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';
require_once CMS_ROOT . '/services/NewsletterService.php';

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$state = 'invalid';   // invalid | confirm | done | error
$email = '';

try {
    if ($method === 'POST') {
        $row = NewsletterService::unsubscribeByToken($token);
        if ($row) {
            $state = 'done';
            $email = $row['email'];
            log_activity('Newsletter Unsubscribe', "Unsubscribed: {$row['email']}", 'newsletter');
        }
        // Mail clients' one-click requests only need a status code
        if (($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
            http_response_code($row ? 200 : 404);
            exit;
        }
    } else {
        $row = NewsletterService::findByToken($token);
        if ($row) {
            $email = $row['email'];
            $state = $row['status'] === 'unsubscribed' ? 'done' : 'confirm';
        }
    }
} catch (Exception $e) {
    $state = 'error';
}

if ($state === 'invalid') http_response_code(404);
$safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
$home = htmlspecialchars(public_url('index.html'), ENT_QUOTES, 'UTF-8');
$logo = htmlspecialchars(public_url('assets/imgz/logo2-cut.png'), ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Newsletter - St. Monica Junior School Kasanje</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: #f3f3f3; font-family: 'Montserrat', -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; color: #2C2C2C; }
        .card { width: 100%; max-width: 460px; background: #fff; border-radius: 16px; box-shadow: 0 20px 40px -16px rgba(30,42,74,.25);
                border-top: 5px solid #d93633; padding: 36px 32px; text-align: center; }
        .logo { width: 88px; height: 88px; object-fit: contain; }
        h1 { font-size: 22px; color: #1e2a4a; margin: 16px 0 8px; }
        p { font-size: 15px; line-height: 1.6; margin: 0 0 20px; color: #45464e; }
        .email { font-weight: 700; color: #1e2a4a; word-break: break-all; }
        .btn { display: inline-block; border: 0; cursor: pointer; border-radius: 9999px; padding: 12px 26px; font-weight: 700;
               font-size: 14px; text-decoration: none; font-family: inherit; }
        .btn-primary { background: #d93633; color: #fff; }
        .btn-primary:hover { background: #b51a1e; }
        .btn-link { background: transparent; color: #1e2a4a; text-decoration: underline; }
        .actions { display: flex; flex-direction: column; gap: 10px; align-items: center; }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" src="<?= $logo ?>" alt="St. Monica Junior School logo">
        <?php if ($state === 'confirm'): ?>
            <h1>Unsubscribe from our newsletter?</h1>
            <p><span class="email"><?= $safeEmail ?></span> will no longer receive the St. Monica Junior School newsletter.</p>
            <form method="POST" action="?token=<?= $safeToken ?>" class="actions">
                <input type="hidden" name="token" value="<?= $safeToken ?>">
                <button type="submit" class="btn btn-primary">Yes, unsubscribe me</button>
                <a class="btn btn-link" href="<?= $home ?>">No, keep me subscribed</a>
            </form>
        <?php elseif ($state === 'done'): ?>
            <h1>You have been unsubscribed</h1>
            <p><span class="email"><?= $safeEmail ?></span> will not receive any more newsletters from us. You can subscribe again at any time from our website.</p>
            <a class="btn btn-primary" href="<?= $home ?>">Visit our website</a>
        <?php elseif ($state === 'error'): ?>
            <h1>Something went wrong</h1>
            <p>We could not process your request right now. Please try again later.</p>
            <a class="btn btn-primary" href="<?= $home ?>">Visit our website</a>
        <?php else: ?>
            <h1>Link not recognised</h1>
            <p>This unsubscribe link is invalid or incomplete. Please use the link from the most recent newsletter email.</p>
            <a class="btn btn-primary" href="<?= $home ?>">Visit our website</a>
        <?php endif; ?>
    </main>
</body>
</html>
