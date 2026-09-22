<?php
/**
 * St. Monica Junior School CMS
 * CSRF Protection Utilities
 */

if (session_status() === PHP_SESSION_NONE) {
    // Hardened session configuration
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/**
 * Get or create current session CSRF token
 */
function csrf_token(): string {
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
