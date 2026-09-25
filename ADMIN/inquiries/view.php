<?php
/**
 * St. Monica Junior School CMS - Enquiry Detail & Status Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('inquiries');

$id = (int)($_GET['id'] ?? 0);
$item = Database::fetchOne("SELECT * FROM `enquiries` WHERE `id` = :id", ['id' => $id]);

if (!$item) {
    set_flash('danger', 'Enquiry not found.');
    redirect(admin_url('inquiries/'));
}

// Auto mark-as-read on first view
if ($item['status'] === 'new') {
    Database::update('enquiries', ['status' => 'read'], 'id = :id', ['id' => $id]);
    $item['status'] = 'read';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $newStatus = trim($_POST['status'] ?? $item['status']);
    $allowedStatuses = ['new', 'read', 'replied', 'archived'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        $newStatus = $item['status'];
    }

    try {
        Database::update('enquiries', ['status' => $newStatus], 'id = :id', ['id' => $id]);
        log_activity('Updated Enquiry Status', "Enquiry from {$item['name']} marked as '{$newStatus}'", 'enquiries', $id);
        set_flash('success', "Enquiry marked as '{$newStatus}'.");
        redirect(admin_url('inquiries/view.php?id=' . $id));
    } catch (Exception $e) {
        set_flash('danger', 'Failed to update enquiry status.');
    }
}

$pageTitle = 'Enquiry from ' . $item['name'];
$activeMenu = 'inquiries';

$statusBadge = match($item['status']) {
    'new'      => 'bg-red-100 text-red-700 border-red-200',
    'read'     => 'bg-blue-100 text-blue-800 border-blue-200',
    'replied'  => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
    default    => 'bg-slate-100 text-slate-700 border-slate-200'
};

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('inquiries/') ?>" class="hover:text-slate-800">Enquiries</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold"><?= e($item['name']) ?></span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight"><?= e($item['subject']) ?></h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('inquiries/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to List</span>
        </a>
        <button type="button" data-delete-btn data-action="<?= admin_url('inquiries/delete.php?id=' . $item['id']) ?>" data-name="Enquiry from <?= e($item['name']) ?>" class="cms-btn cms-btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">
            <span class="material-symbols-outlined text-[16px]">delete</span>
            <span>Delete</span>
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <div class="lg:col-span-8 space-y-6">
        <div class="cms-card p-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-base">Message</h2>
                <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusBadge ?>">
                    Status: <?= e(ucfirst($item['status'])) ?>
                </span>
            </div>
            <div class="p-4 bg-slate-50 rounded-lg text-sm text-slate-700 leading-relaxed border border-slate-200">
                <?= nl2br(e($item['message'])) ?>
            </div>
        </div>

        <div class="cms-card p-6">
            <h2 class="font-bold text-slate-900 text-base mb-4">Sender Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Full Name</label>
                    <p class="font-bold text-slate-900"><?= e($item['name']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Email Address</label>
                    <a href="mailto:<?= e($item['email']) ?>" class="text-slate-700 font-medium hover:text-red-600"><?= e($item['email']) ?></a>
                </div>
                <?php if (!empty($item['phone'])): ?>
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Phone</label>
                    <a href="tel:<?= e($item['phone']) ?>" class="text-slate-700 font-medium hover:text-red-600"><?= e($item['phone']) ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="lg:col-span-4 space-y-6">
        <div class="cms-card p-6 border-t-4 border-t-[#1e2a4a]">
            <h3 class="font-bold text-slate-900 text-base mb-1 brand-font">Update Status</h3>
            <p class="text-xs text-slate-500 mb-5">Track whether this enquiry has been handled.</p>

            <form method="POST" action="<?= admin_url('inquiries/view.php?id=' . $item['id']) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <select name="status" class="cms-select text-sm font-semibold">
                    <?php foreach (['new', 'read', 'replied', 'archived'] as $st): ?>
                        <option value="<?= $st ?>" <?= $item['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="cms-btn cms-btn-primary w-full text-xs">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Save Status</span>
                </button>
            </form>
            <a href="mailto:<?= e($item['email']) ?>?subject=<?= rawurlencode('Re: ' . $item['subject']) ?>" class="cms-btn cms-btn-outline w-full text-xs mt-3">
                <span class="material-symbols-outlined text-[16px]">reply</span>
                <span>Reply by Email</span>
            </a>
        </div>

        <div class="cms-card p-6 text-xs text-slate-600 space-y-3">
            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-2">Details</h4>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-400">Submitted On:</span>
                <span class="font-medium text-slate-800"><?= date('M j, Y g:i A', strtotime($item['submitted_at'])) ?></span>
            </div>
            <?php if (!empty($item['ip_address'])): ?>
            <div class="flex justify-between py-1.5">
                <span class="text-slate-400">IP Address:</span>
                <span class="font-mono text-slate-800"><?= e($item['ip_address']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
