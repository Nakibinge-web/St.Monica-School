<?php
/**
 * St. Monica Junior School CMS - Email Templates
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_role('super_admin');

$pageTitle = 'Email Templates';
$activeMenu = 'email-templates';

$templates = [];
try {
    $templates = Database::fetchAll("SELECT * FROM `email_templates` ORDER BY `label` ASC");
} catch (Exception $e) {
    // ignore
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Email Templates</h1>
        <p class="text-sm text-slate-500 mt-1">Customize the wording of automated system emails. Placeholders like <code class="text-[11px] bg-slate-100 px-1 py-0.5 rounded">{{applicant_name}}</code> are replaced automatically and safely - no code can be executed through templates.</p>
    </div>
</div>

<div class="cms-card overflow-hidden">
    <table class="cms-table">
        <thead>
            <tr>
                <th>Template</th>
                <th>Subject</th>
                <th>Last Updated</th>
                <th class="text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($templates)): ?>
                <tr><td colspan="4" class="text-center py-12 text-slate-400">No email templates found. Run the database migrations to seed defaults.</td></tr>
            <?php else: ?>
                <?php foreach ($templates as $t): ?>
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="font-semibold text-slate-900 text-sm"><?= e($t['label']) ?></td>
                        <td class="text-xs text-slate-600 max-w-sm truncate"><?= e($t['subject']) ?></td>
                        <td class="text-xs text-slate-500"><?= date('M j, Y', strtotime($t['updated_at'])) ?></td>
                        <td class="text-right">
                            <a href="<?= admin_url('email-templates/edit.php?id=' . $t['id']) ?>" class="p-1.5 text-slate-600 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit Template">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
