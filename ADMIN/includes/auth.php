<?php
/**
 * St. Monica Junior School CMS
 * Authentication Guard & Session Management
 */

if (!defined('CMS_ROOT')) {
    if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';

// Ensure secure session is active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_name('st_monica_admin');
    session_start();
}

/**
 * Check if the current user is authenticated as administrator
 */
function is_logged_in(): bool {
    if (empty($_SESSION['admin_id'])) {
        // No active session - fall back to a remember-me cookie, if present and valid
        return attempt_remember_login();
    }

    // Check session expiration (default 2 hours inactivity)
    $maxLifetime = 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxLifetime)) {
        logout_admin();
        return false;
    }

    // Honor a Super Admin's explicit session revocation from the Security Center.
    // Checked every request (cheap indexed lookup on a small table) so a revoke takes effect immediately.
    try {
        $revoked = Database::fetchColumn(
            "SELECT COUNT(*) FROM `admin_sessions` WHERE `session_id` = :sid AND `revoked_at` IS NOT NULL",
            ['sid' => session_id()]
        );
        if ((int)$revoked > 0) {
            logout_admin();
            return false;
        }
    } catch (Exception $e) {
        // Table may not be migrated yet; fail open rather than lock everyone out
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Require authentication guard on protected pages
 */
function require_auth(): void {
    if (!is_logged_in()) {
        // Save attempted URL for redirect after login
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'] ?? admin_url('dashboard/');
        set_flash('warning', 'Please sign in to access the administration panel.');
        redirect(admin_url('login/login.php'));
    }
}

/**
 * Retrieve current logged-in administrator record
 */
function current_admin(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id'    => $_SESSION['admin_id'],
        'name'  => $_SESSION['admin_name'] ?? 'Admin',
        'email' => $_SESSION['admin_email'] ?? '',
        'role'  => $_SESSION['admin_role'] ?? 'administrator'
    ];
}

/**
 * Role permissions mapping
 */
function get_role_permissions(string $role): array {
    $role = strtolower($role);
    if ($role === 'administrator' || $role === 'super_admin') {
        return ['*'];
    }
    if ($role === 'editor') {
        return ['dashboard', 'homepage', 'about', 'staff', 'news-events', 'gallery', 'testimonials', 'media', 'seo', 'profile', 'preview', 'announcements', 'inquiries', 'notifications'];
    }
    if ($role === 'admissions_manager') {
        return ['dashboard', 'admissions', 'admission-info', 'profile', 'inquiries', 'notifications'];
    }
    return ['dashboard', 'profile', 'notifications'];
}

/**
 * Check if the currently authenticated admin has access to a specific module
 */
function can_manage(string $module): bool {
    $admin = current_admin();
    if (!$admin) return false;
    $perms = get_role_permissions($admin['role'] ?? '');
    return in_array('*', $perms, true) || in_array($module, $perms, true);
}

/**
 * Guard that enforces module-level authorization
 */
function require_module(string $module): void {
    require_auth();
    if (!can_manage($module)) {
        http_response_code(403);
        set_flash('danger', 'Access denied. You do not have permission to access the ' . htmlspecialchars($module) . ' section.');
        redirect(admin_url('dashboard/'));
    }
}

/**
 * Check if the currently authenticated admin has one of the specified roles
 */
function has_role(string|array $roles): bool {
    $admin = current_admin();
    if (!$admin) return false;
    $currentRole = strtolower($admin['role'] ?? '');
    if ($currentRole === 'administrator') $currentRole = 'super_admin';
    $roles = (array)$roles;
    $roles = array_map(fn($r) => strtolower($r) === 'administrator' ? 'super_admin' : strtolower($r), $roles);
    return in_array($currentRole, $roles, true);
}

/**
 * Guard that enforces specific role(s)
 */
function require_role(string|array $roles): void {
    require_auth();
    if (!has_role($roles)) {
        http_response_code(403);
        set_flash('danger', 'Access denied: Super Administrator privileges are required for this action.');
        redirect(admin_url('dashboard/'));
    }
}

/**
 * Log in an administrator record and initialize session securely
 */
function login_admin(array $admin, bool $remember = false): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['admin_id']      = (int)$admin['id'];
    $_SESSION['admin_name']    = $admin['name'];
    $_SESSION['admin_email']   = $admin['email'];
    $_SESSION['admin_role']    = $admin['role'] ?? 'administrator';
    $_SESSION['last_activity'] = time();

    // Update last_login timestamp in database
    try {
        Database::update('admins', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $admin['id']]);
        log_activity('Admin Login', 'Successful sign in from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown'));
    } catch (Exception $e) {
        // Log silently
    }

    // Record this session for the Security Center's active-sessions view
    try {
        Database::insert('admin_sessions', [
            'admin_id'   => (int)$admin['id'],
            'session_id' => session_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    } catch (Exception $e) {
        // Table may not be migrated yet
    }

    if ($remember) {
        issue_remember_token((int)$admin['id']);
    }
}

/**
 * Completely destroy session on logout
 */
function logout_admin(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    log_activity('Admin Logout', 'Administrator signed out.');

    // Mark this session revoked in the Security Center view
    try {
        if (!empty($_SESSION['admin_id'])) {
            Database::update('admin_sessions', ['revoked_at' => date('Y-m-d H:i:s')], 'session_id = :sid AND revoked_at IS NULL', ['sid' => session_id()]);
        }
    } catch (Exception $e) {
        // ignore
    }

    forget_remember_token();

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Issue a new selector/validator remember-me token and set its cookie.
 * The validator is only ever stored hashed; the raw value lives solely in the cookie.
 */
function issue_remember_token(int $adminId): void {
    try {
        $selector = bin2hex(random_bytes(9));
        $validator = bin2hex(random_bytes(32));
        $ttlSeconds = 30 * 24 * 3600; // 30 days

        Database::insert('remember_tokens', [
            'admin_id'       => $adminId,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at'     => date('Y-m-d H:i:s', time() + $ttlSeconds)
        ]);

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('st_monica_remember', $selector . ':' . $validator, [
            'expires'  => time() + $ttlSeconds,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } catch (Exception $e) {
        // Remember-me is a convenience feature; never break login on failure
    }
}

/**
 * Attempt to authenticate the current visitor from a remember-me cookie.
 * Rotates the token on success (old one is invalidated) and purges all of an
 * admin's tokens if a stale/tampered selector is presented (possible theft).
 */
function attempt_remember_login(): bool {
    if (empty($_COOKIE['st_monica_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['st_monica_remember'], 2);
    if (count($parts) !== 2) {
        clear_remember_cookie();
        return false;
    }
    [$selector, $validator] = $parts;

    try {
        $token = Database::fetchOne("SELECT * FROM `remember_tokens` WHERE `selector` = :s AND `expires_at` > NOW()", ['s' => $selector]);
        if (!$token) {
            clear_remember_cookie();
            return false;
        }

        if (!hash_equals($token['validator_hash'], hash('sha256', $validator))) {
            // Selector matched but validator didn't - treat as potential token theft
            Database::delete('remember_tokens', 'admin_id = :id', ['id' => $token['admin_id']]);
            clear_remember_cookie();
            return false;
        }

        $admin = Database::fetchOne("SELECT * FROM `admins` WHERE `id` = :id AND `status` = 'active'", ['id' => $token['admin_id']]);
        if (!$admin) {
            clear_remember_cookie();
            return false;
        }

        Database::delete('remember_tokens', 'id = :id', ['id' => $token['id']]);
        login_admin($admin, true);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete the DB record behind the current remember-me cookie (if any) and clear the cookie.
 */
function forget_remember_token(): void {
    if (!empty($_COOKIE['st_monica_remember'])) {
        $parts = explode(':', $_COOKIE['st_monica_remember'], 2);
        if (count($parts) === 2) {
            try {
                Database::delete('remember_tokens', 'selector = :s', ['s' => $parts[0]]);
            } catch (Exception $e) {
                // ignore
            }
        }
    }
    clear_remember_cookie();
}

function clear_remember_cookie(): void {
    if (isset($_COOKIE['st_monica_remember'])) {
        setcookie('st_monica_remember', '', ['expires' => time() - 3600, 'path' => '/']);
        unset($_COOKIE['st_monica_remember']);
    }
}

/**
 * Whether recent failed login attempts for this email should temporarily block sign-in.
 * Reuses the existing activity log rather than a dedicated attempts table/table lock.
 */
function is_login_locked(string $email): bool {
    try {
        $count = Database::fetchColumn(
            "SELECT COUNT(*) FROM `activity_logs`
             WHERE `action` = 'Failed Login Attempt'
             AND `details` LIKE :pattern
             AND `created_at` >= (NOW() - INTERVAL 15 MINUTE)",
            ['pattern' => 'Attempted email: ' . $email . '%']
        );
        return (int)$count >= 5;
    } catch (Exception $e) {
        return false;
    }
}
