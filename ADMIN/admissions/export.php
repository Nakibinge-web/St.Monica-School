<?php
/**
 * St. Monica Junior School CMS - Export Admissions to CSV
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('admissions');

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$classFilter = trim($_GET['class'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = "(`application_number` LIKE :s1 OR `pupil_name` LIKE :s2 OR `parent_name` LIKE :s3 OR `mobile` LIKE :s4 OR `location` LIKE :s5)";
    $params += ['s1' => "%{$search}%", 's2' => "%{$search}%", 's3' => "%{$search}%", 's4' => "%{$search}%", 's5' => "%{$search}%"];
}
if (!empty($statusFilter)) {
    $where[] = "`status` = :status";
    $params['status'] = $statusFilter;
}
if (!empty($classFilter)) {
    $where[] = "`pupil_class` = :class";
    $params['class'] = $classFilter;
}
if (!empty($dateFrom)) {
    $where[] = "DATE(`submitted_at`) >= :date_from";
    $params['date_from'] = $dateFrom;
}
if (!empty($dateTo)) {
    $where[] = "DATE(`submitted_at`) <= :date_to";
    $params['date_to'] = $dateTo;
}

$whereSql = implode(' AND ', $where);
$applications = Database::fetchAll("SELECT * FROM `admissions` WHERE {$whereSql} ORDER BY `submitted_at` DESC", $params);

log_activity('Exported Admissions', 'Exported ' . count($applications) . ' applications to CSV', 'admissions');

$filename = 'st_monica_admissions_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Header
fputcsv($output, [
    'Application Number',
    'Pupil Name',
    'Class Applying For',
    'Parent / Guardian Name',
    'Mobile Phone',
    'Email Address',
    'Location / Address',
    'Applicant Notes',
    'Status',
    'Admin Remarks',
    'Submission Date'
]);

foreach ($applications as $row) {
    fputcsv($output, [
        $row['application_number'],
        $row['pupil_name'],
        $row['pupil_class'],
        $row['parent_name'],
        $row['mobile'],
        $row['email'] ?? '',
        $row['location'],
        $row['message'],
        $row['status'],
        $row['admin_notes'] ?? '',
        $row['submitted_at']
    ]);
}

fclose($output);
exit;
