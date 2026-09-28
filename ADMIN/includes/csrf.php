<?php
/**
 * St. Monica Junior School CMS
 * CSRF Protection Utilities
 */

// The session is started on demand (never on include) with the hardened settings in session.php
require_once __DIR__ . '/session.php';

/**
 * Get or create current session CSRF token
 */
function csrf_token(): string {
    start_admin_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden input field with CSRF token
 */
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate submitted CSRF token
 */
function verify_csrf_token(?string $token = null): bool {
    start_admin_session();
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Guard function to enforce CSRF validation on POST requests
 */
function require_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!verify_csrf_token()) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF security token.']);
                exit;
            }
            die("Security error: Invalid or expired CSRF token. Please refresh the page and try again.");
        }
    }
}
