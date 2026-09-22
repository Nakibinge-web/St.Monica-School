<?php
/**
 * St. Monica Junior School - Public Admission Application Submission API
 * Endpoint: POST /ADMIN/api/admissions/apply.php
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

// Extract and normalize fields
$parentName = trim($input['parentName'] ?? $input['parent_name'] ?? '');
$mobile     = trim($input['mobile'] ?? $input['phone'] ?? '');
$email      = trim($input['emailAddress'] ?? $input['email'] ?? '');
$pupilName  = trim($input['pupilName'] ?? $input['pupil_name'] ?? '');
$pupilClass = trim($input['pupilClass'] ?? $input['pupil_class'] ?? '');
$location   = trim($input['location'] ?? $input['address'] ?? '');
$message    = trim($input['applicationMessage'] ?? $input['message'] ?? '');

// Validation
$errors = [];

if (empty($parentName) || mb_strlen($parentName) < 3) {
    $errors['parentName'] = "Parent or guardian's full name is required (minimum 3 characters).";
}
if (empty($mobile) || mb_strlen($mobile) < 9) {
    $errors['mobile'] = 'A valid mobile contact number is required.';
} elseif (!preg_match('/^(\+?256|0)?[0-9\s\-]{9,15}$/', $mobile)) {
    $errors['mobile'] = 'Please enter a valid telephone number format.';
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['emailAddress'] = 'Please enter a valid email address.';
}

if (empty($pupilName) || mb_strlen($pupilName) < 3) {
    $errors['pupilName'] = "Pupil's full name is required.";
}

if (empty($pupilClass)) {
    $errors['pupilClass'] = 'Please select the class for admission.';
}

if (empty($location) || mb_strlen($location) < 3) {
    $errors['location'] = 'Your residential location or address is required.';
}

if (empty($message) || mb_strlen($message) < 5) {
    $errors['applicationMessage'] = 'Please provide brief details or notes regarding your application.';
}

if (!empty($errors)) {
    json_response(false, 'Please correct the highlighted validation errors.', ['errors' => $errors], 422);
}

try {
    $applicationNumber = generate_application_number();

    $insertData = [
        'application_number' => $applicationNumber,
        'parent_name'        => $parentName,
        'mobile'             => $mobile,
        'email'              => !empty($email) ? $email : null,
        'pupil_name'         => $pupilName,
        'pupil_class'        => $pupilClass,
        'location'           => $location,
        'message'            => $message,
        'status'             => 'New'
    ];

    $newId = Database::insert('admissions', $insertData);

    // Audit log
    log_activity(
        'Public Application Submitted',
        "Application #{$applicationNumber} received for {$pupilName} ({$pupilClass}) from parent {$parentName}",
        'admissions',
        $newId
    );

    json_response(true, "Application submitted successfully! Your tracking reference is {$applicationNumber}.", [
        'application_number' => $applicationNumber,
        'pupil_name'         => $pupilName,
        'pupil_class'        => $pupilClass,
        'parent_name'        => $parentName,
        'status'             => 'New',
        'submitted_at'       => date('Y-m-d H:i:s')
    ], 201);

} catch (Exception $e) {
    json_response(false, 'Unable to submit application at this time: ' . $e->getMessage(), null, 500);
}
