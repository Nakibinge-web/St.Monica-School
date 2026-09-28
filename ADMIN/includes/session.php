<?php
/**
 * St. Monica Junior School CMS
 * Admin Session Bootstrap
 *
 * The single place the admin session is started, so it always gets the hardened settings.
 * Nothing starts a session merely by being included: public API requests (homepage, gallery,
 * forms...) never create one, and admin pages start it on demand via start_admin_session().
 */

/**
 * Cookie name for the admin session (config app.session_name).
 */
function admin_session_name(): string {
    static $name = null;
    if ($name === null) {
        $config = require __DIR__ . '/config.php';
        $name = $config['app']['session_name'] ?? 'st_monica_admin_session';
    }
    return $name;
}

/**
 * Whether this request carries an admin session cookie (i.e. someone has signed in before).
 */
function admin_session_cookie_present(): bool {
    return !empty($_COOKIE[admin_session_name()]);
}

/**
 * Start the admin session with hardened cookie settings. Safe to call repeatedly.
 */
function start_admin_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    if (headers_sent()) {
        // Too late to send a cookie; nothing sensible can be done for this request
        return;
    }

    $config = require __DIR__ . '/config.php';
    $timeout = (int)($config['app']['session_timeout'] ?? 7200);
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');

    // Only ever accept IDs this server issued, via cookies (never URLs)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    // Keep idle sessions on disk for the full inactivity timeout (PHP's default is 24 minutes)
    ini_set('session.gc_maxlifetime', (string)max($timeout, 1440));

    session_name(admin_session_name());
    session_set_cookie_params([
        'lifetime' => 0,                  // ends when the browser closes (remember-me has its own cookie)
        'path'     => admin_cookie_path(),// only sent to the admin panel, never to the public website
        'secure'   => $secure,            // HTTPS-only whenever the site is served over HTTPS
        'httponly' => true,               // invisible to JavaScript
        // Lax: never sent with cross-site form posts or background requests (CSRF), but still
        // sent when an admin follows a normal link to the panel, e.g. from a notification email.
        'samesite' => 'Lax',
    ]);
    session_start();

    // Remove the legacy default-named session cookie left over from before this fix
    if (isset($_COOKIE['PHPSESSID']) && session_name() !== 'PHPSESSID') {
        setcookie('PHPSESSID', '', ['expires' => time() - 3600, 'path' => '/']);
    }
}

/**
 * URL path of the admin panel (e.g. "/St.monica/ADMIN/"), used to scope the session cookie.
 */
function admin_cookie_path(): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($scriptDir, '/ADMIN');
    return ($pos !== false ? substr($scriptDir, 0, $pos) : '') . '/ADMIN/';
}
