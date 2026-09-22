<?php
/**
 * St. Monica Junior School - Public "Rate Us" Submission API
 * Endpoint: POST /ADMIN/api/testimonials/submit.php
 *
 * Accepts a visitor-submitted rating/testimonial and stores it as a
 * draft testimonial so it stays hidden from the public site until an
 * administrator reviews and publishes it from the CMS.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(dirname(__DIR__)));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/database.php';

// Handle CORS preflight
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    json_response(true, 'Preflight OK', null, 200);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(false, 'Method not allowed. Only POST requests are accepted.', null, 405);
}

// Parse request data (support form-data, x-www-form-urlencoded, and json)
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $jsonData = json_decode($raw, true);
    if (is_array($jsonData)) {
        $input = array_merge($input, $jsonData);
    }
}

/**
 * Strip any HTML/script markup and non-printable control characters from
 * visitor-supplied text before it is validated or stored, so no markup or
 * executable payload ever reaches the database, the admin dashboard, or
 * the activity log — regardless of how the value is later rendered.
 * Rejects anything that isn't a plain scalar (blocks array-based param abuse).
 */
function clean_public_text(mixed $value, int $maxLen, bool $allowNewlines = false): string {
    if (!is_string($value) && !is_numeric($value)) {
        return '';
    }
    $value = (string)$value;

    // Remove control/null bytes; keep tab, and newline/CR only where allowed
    $pattern = $allowNewlines
        ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'
        : '/[\x00-\x1F\x7F]/u';
    $value = preg_replace($pattern, ' ', $value) ?? '';

    // Strip all HTML tags — these fields are plain text only, never markup
    $value = strip_tags($value);

    // Collapse repeated whitespace and trim
    $value = trim(preg_replace('/[ \t]+/', ' ', $value));

    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }

    return $value;
}

// Extract, type-guard, and sanitize fields before any validation happens
$name    = clean_public_text($input['name'] ?? '', 150);
$role    = clean_public_text($input['role'] ?? 'Parent', 100);
$content = clean_public_text($input['content'] ?? $input['message'] ?? '', 1000, true);

$ratingRaw = $input['rating'] ?? 0;
$rating = (is_scalar($ratingRaw) && is_numeric($ratingRaw)) ? (int)$ratingRaw : 0;

// Validation (runs against the already-sanitized values)
$errors = [];

if (empty($name) || mb_strlen($name) < 3) {
    $errors['name'] = 'Please enter your full name (minimum 3 characters).';
} elseif (mb_strlen($name) > 150) {
    $errors['name'] = 'Name cannot exceed 150 characters.';
}

if (empty($role)) {
    $role = 'Parent';
} elseif (mb_strlen($role) > 100) {
    $errors['role'] = 'Role cannot exceed 100 characters.';
}

if ($rating < 1 || $rating > 5) {
    $errors['rating'] = 'Please select a star rating between 1 and 5.';
}

if (empty($content) || mb_strlen($content) < 10) {
    $errors['content'] = 'Please share a little more detail (minimum 10 characters).';
} elseif (mb_strlen($content) > 1000) {
    $errors['content'] = 'Your message cannot exceed 1000 characters.';
}

if (!empty($errors)) {
    json_response(false, 'Please correct the highlighted validation errors.', ['errors' => $errors], 422);
}

try {
    // Compute avatar initials from the submitted name
    $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    $initials = count($parts) >= 2
        ? strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1))
        : strtoupper(mb_substr($name, 0, 2));

    $newId = Database::insert('testimonials', [
        'name'          => $name,
        'role'          => $role,
        'child_info'    => $role,
        'rating'        => $rating,
        'content'       => $content,
        'initials'      => $initials,
        'display_order' => 0,
        'status'        => 'draft' // Hidden until an admin reviews and publishes it
    ]);

    log_activity(
        'Public Rating Submitted',
        "New visitor rating ({$rating}\u{2605}) submitted by {$name} ({$role}) — pending review",
        'testimonials',
        $newId
    );

    json_response(true, 'Thank you! Your review has been submitted and will appear on our website after admin review.', [
        'name'   => $name,
        'rating' => $rating
    ], 201);

} catch (Exception $e) {
    json_response(false, 'Unable to submit your review at this time: ' . $e->getMessage(), null, 500);
}
