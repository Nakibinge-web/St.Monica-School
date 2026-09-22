<?php
/**
 * St. Monica Junior School CMS - Admin Root Router
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', __DIR__);

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';

if (is_logged_in()) {
    redirect(admin_url('dashboard/'));
} else {
    redirect(admin_url('login/login.php'));
}
