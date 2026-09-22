<?php
/**
 * St. Monica Junior School CMS - Logout Handler
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/functions.php';
require_once CMS_ROOT . '/includes/auth.php';

logout_admin();

set_flash('info', 'You have been logged out successfully.');
redirect(admin_url('login/login.php'));
