<?php
/**
 * St. Monica Junior School CMS - Delete Admission Application
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('admissions');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('admissions/'));
}

require_csrf();

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $app = Database::fetchOne("SELECT * FROM `admissions` WHERE `id` = :id", ['id' => $id]);

        if ($app) {
            Database::delete('admissions', 'id = :id', ['id' => $id]);

            log_activity(
                'Deleted Application',
                "Application #{$app['application_number']} for {$app['pupil_name']} ({$app['pupil_class']}) deleted",
                'admissions',
                $id
            );

            set_flash('success', "Application #{$app['application_number']} has been permanently deleted.");
        } else {
            set_flash('danger', 'Application record not found.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Database error: ' . $e->getMessage());
    }
}

redirect(admin_url('admissions/'));
