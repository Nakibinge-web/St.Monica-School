<?php
/**
 * St. Monica Junior School CMS - Update Contact Info Handler
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('contact');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('contact/'));
}

require_csrf();

$schoolName = trim($_POST['school_name'] ?? 'St. Monica Junior School Kasanje');
$phone = trim($_POST['phone'] ?? '');
$altPhone = trim($_POST['alternative_phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$admissionsEmail = trim($_POST['admissions_email'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$openingHours = trim($_POST['opening_hours'] ?? '');
$address = trim($_POST['address'] ?? '');
$village = trim($_POST['village'] ?? '');
$district = trim($_POST['district'] ?? '');
$facebook = trim($_POST['facebook'] ?? '');
$instagram = trim($_POST['instagram'] ?? '');
$youtube = trim($_POST['youtube'] ?? '');
$tiktok = trim($_POST['tiktok'] ?? '');
$mapUrl = trim($_POST['map_url'] ?? '');

// If user pasted full iframe tag, extract the src URL
if (preg_match('/src=["\']([^"\']+)["\']/', $mapUrl, $matches)) {
    $mapUrl = $matches[1];
}

if (empty($phone) || empty($email) || empty($address)) {
    set_flash('danger', 'Primary phone, email and address are required.');
    redirect(admin_url('contact/'));
}

try {
    $existing = Database::fetchOne("SELECT `id` FROM `contact_information` LIMIT 1");
    $data = [
        'school_name'       => $schoolName,
        'phone'             => $phone,
        'alternative_phone' => $altPhone,
        'email'             => $email,
        'admissions_email'  => $admissionsEmail,
        'whatsapp'          => $whatsapp,
        'opening_hours'     => $openingHours,
        'address'           => $address,
        'village'           => $village,
        'district'          => $district,
        'facebook'          => $facebook,
        'instagram'         => $instagram,
        'youtube'           => $youtube,
        'tiktok'            => $tiktok,
        'map_url'           => $mapUrl
    ];

    if ($existing) {
        Database::update('contact_information', $data, "id = :id", ['id' => $existing['id']]);
    } else {
        Database::insert('contact_information', $data);
    }

    log_activity('Updated Global Contact Info', "Phone: {$phone}, Email: {$email}");
    set_flash('success', 'Global contact information updated successfully! Changes are live across the website.');
} catch (Exception $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect(admin_url('contact/'));
