<?php
/**
 * St. Monica Junior School CMS - Edit Email Template
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$id = (int)($_GET['id'] ?? 0);
$template = Database::fetchOne("SELECT * FROM `email_templates` WHERE `id` = :id", ['id' => $id]);

if (!$template) {
    set_flash('danger', 'Email template not found.');
    redirect(admin_url('email-templates/'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $subject = trim($_POST['subject'] ?? '');
    $bodyHtml = trim($_POST['body_html'] ?? '');

    if (empty($subject) || empty($bodyHtml)) {
        set_flash('danger', 'Subject and body are both required.');
    } else {
        try {
            Database::update('email_templates', [
                'subject'   => $subject,
                'body_html' => sanitize_html($bodyHtml)
            ], 'id = :id', ['id' => $id]);

            log_activity('Updated Email Template', "Template '{$template['label']}' updated", 'email_templates', $id);
            set_flash('success', 'Email template updated successfully.');
            redirect(admin_url('email-templates/'));
        } catch (Exception $e) {
            set_flash('danger', 'Failed to update template.');
        }
    }
}

$pageTitle = 'Edit: ' . $template['label'];
$activeMenu = 'email-templates';

// Known placeholders per template, shown as a helpful reference (display only)
$placeholderHints = [
    'new_application_staff'            => ['application_number', 'pupil_name', 'pupil_class', 'parent_name', 'school_name'],
    'application_received'             => ['parent_name', 'pupil_name', 'application_number', 'school_name'],
    'application_status_update'        => ['parent_name', 'pupil_name', 'pupil_class', 'application_number', 'application_status', 'school_name', 'school_phone', 'school_email', 'custom_message'],
    'application_status_accepted'      => ['parent_name', 'pupil_name', 'pupil_class', 'application_number', 'application_status', 'school_name', 'school_phone', 'school_email', 'custom_message'],
    'application_status_under_review'  => ['parent_name', 'pupil_name', 'pupil_class', 'application_number', 'application_status', 'school_name', 'school_phone', 'school_email', 'custom_message'],
    'application_status_contacted'     => ['parent_name', 'pupil_name', 'pupil_class', 'application_number', 'application_status', 'school_name', 'school_phone', 'school_email', 'custom_message'],
    'application_status_rejected'      => ['parent_name', 'pupil_name', 'pupil_class', 'application_number', 'application_status', 'school_name', 'school_phone', 'school_email', 'custom_message'],
    'application_status_withdrawn'     => ['parent_name', 'pupil_name', 'pupil_class', 'application_number', 'application_status', 'school_name', 'school_phone', 'school_email', 'custom_message'],
    'new_enquiry_staff'                => ['enquiry_name', 'enquiry_email', 'enquiry_subject', 'school_name'],
    'password_reset'                   => ['admin_name', 'reset_url', 'school_name'],
    'admin_invitation'                 => ['admin_name', 'admin_role', 'login_url', 'school_name'],
];
$hints = $placeholderHints[$template['template_key']] ?? [];

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('email-templates/') ?>" class="hover:text-slate-800">Email Templates</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold"><?= e($template['label']) ?></span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit Email Template</h1>
    </div>
    <a href="<?= admin_url('email-templates/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-3xl mx-auto">
    <?php if (!empty($hints)): ?>
        <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
            <p class="text-xs font-semibold text-slate-700 mb-2">Available placeholders for this template:</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($hints as $h): ?>
                    <code class="text-[11px] bg-white border border-slate-200 px-2 py-1 rounded"><?= '{{' . e($h) . '}}' ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= admin_url('email-templates/edit.php?id=' . $template['id']) ?>" class="space-y-6">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Email Subject *</label>
            <input type="text" name="subject" required value="<?= e($template['subject']) ?>" class="cms-input">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Email Body *</label>
            <textarea name="body_html" rows="10" data-rich-editor required class="cms-textarea leading-relaxed text-sm"><?= e($template['body_html']) ?></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('email-templates/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Save Template</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
