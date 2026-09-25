<?php
/**
 * St. Monica Junior School CMS - Revoke an Active Admin Session
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('security/'));
}

require_csrf();

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $session = Database::fetchOne("SELECT s.*, a.name AS admin_name FROM `admin_sessions` s JOIN `admins` a ON a.id = s.admin_id WHERE s.id = :id", ['id' => $id]);

        if ($session && (int)$session['admin_id'] !== (int)$_SESSION['admin_id']) {
            Database::update('admin_sessions', ['revoked_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
            log_activity('Revoked Admin Session', "Revoked session for {$session['admin_name']} (IP: {$session['ip_address']})", 'security', $id);
            set_flash('success', "Session for '{$session['admin_name']}' has been revoked.");
        } else {
            set_flash('danger', 'You cannot revoke your own active session from here. Use Sign Out instead.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Failed to revoke session.');
    }
}

redirect(admin_url('security/'));
