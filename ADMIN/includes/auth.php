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
        return false;
    }

    // Check session expiration (default 2 hours inactivity)
    $maxLifetime = 7200;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxLifetime)) {
        logout_admin();
        return false;
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
        return ['dashboard', 'homepage', 'about', 'staff', 'news-events', 'gallery', 'testimonials', 'media', 'seo', 'profile', 'preview'];
    }
    if ($role === 'admissions_manager') {
        return ['dashboard', 'admissions', 'admission-info', 'profile'];
    }
    return ['dashboard', 'profile'];
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
function login_admin(array $admin): void {
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
}

/**
 * Completely destroy session on logout
 */
function logout_admin(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    log_activity('Admin Logout', 'Administrator signed out.');

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
