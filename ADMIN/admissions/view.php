<?php
/**
 * St. Monica Junior School CMS - Application Detail & Status Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('admissions');

$id = (int)($_GET['id'] ?? 0);
$app = Database::fetchOne("SELECT * FROM `admissions` WHERE `id` = :id", ['id' => $id]);

if (!$app) {
    set_flash('danger', 'Application not found.');
    redirect(admin_url('admissions/'));
}

// Handle status & notes update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $newStatus = trim($_POST['status'] ?? $app['status']);
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    $allowedStatuses = ['New', 'Under Review', 'Contacted', 'Accepted', 'Rejected', 'Withdrawn'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        $newStatus = $app['status'];
    }

    try {
        $oldStatus = $app['status'];
        Database::update('admissions', [
            'status'      => $newStatus,
            'admin_notes' => $adminNotes
        ], 'id = :id', ['id' => $id]);

        $logMsg = "Application #{$app['application_number']} ({$app['pupil_name']}) status updated";
        if ($oldStatus !== $newStatus) {
            $logMsg .= " from '{$oldStatus}' to '{$newStatus}'";
        }
        log_activity('Status Change', $logMsg, 'admissions', $id);

        set_flash('success', "Application status successfully updated to '{$newStatus}'.");
        redirect(admin_url('admissions/view.php?id=' . $id));
    } catch (Exception $e) {
        set_flash('danger', 'Failed to update application: ' . $e->getMessage());
    }
}

$pageTitle = 'Application #' . $app['application_number'];
$activeMenu = 'admissions';

$statusBadge = match($app['status']) {
    'New'          => 'bg-red-100 text-red-700 border-red-200',
    'Under Review' => 'bg-amber-100 text-amber-800 border-amber-200',
    'Contacted'    => 'bg-blue-100 text-blue-800 border-blue-200',
    'Accepted'     => 'bg-emerald-100 text-emerald-800 border-emerald-200',
    'Rejected'     => 'bg-slate-100 text-slate-700 border-slate-200',
    'Withdrawn'    => 'bg-purple-100 text-purple-800 border-purple-200',
    default        => 'bg-slate-100 text-slate-700 border-slate-200'
};

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('admissions/') ?>" class="hover:text-slate-800">Admissions</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold"><?= e($app['application_number']) ?></span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Application: <?= e($app['application_number']) ?></h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('admissions/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to List</span>
        </a>
        <button type="button" data-delete-btn data-action="<?= admin_url('admissions/delete.php?id=' . $app['id']) ?>" data-name="Application <?= e($app['application_number']) ?>" class="cms-btn cms-btn-outline text-xs text-red-600 border-red-200 hover:bg-red-50">
            <span class="material-symbols-outlined text-[16px]">delete</span>
            <span>Delete</span>
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column: Applicant & Parent Details (8 cols) -->
    <div class="lg:col-span-8 space-y-6">
        <!-- Pupil Information Card -->
        <div class="cms-card p-6">
            <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-[#1e2a4a] flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">school</span>
                    </span>
                    <h2 class="font-bold text-slate-900 text-base">Pupil Information</h2>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusBadge ?>">
                    Status: <?= e($app['status']) ?>
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Full Pupil Name</label>
                    <p class="font-bold text-slate-900 text-base"><?= e($app['pupil_name']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Class Applying For</label>
                    <p class="font-semibold text-red-600 text-base"><?= e($app['pupil_class']) ?></p>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs text-slate-400 font-medium block mb-1">Residential Address / Location</label>
                    <p class="font-medium text-slate-700"><?= e($app['location']) ?></p>
                </div>
            </div>
        </div>

        <!-- Parent / Guardian Information Card -->
        <div class="cms-card p-6">
            <div class="flex items-center gap-2.5 pb-4 mb-5 border-b border-slate-100">
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">family_restroom</span>
                </span>
                <h2 class="font-bold text-slate-900 text-base">Parent / Guardian Information</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Parent's Full Name</label>
                    <p class="font-bold text-slate-900"><?= e($app['parent_name']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-slate-400 font-medium block mb-1">Mobile Telephone</label>
                    <p class="font-mono font-bold text-slate-800">
                        <a href="tel:<?= e($app['mobile']) ?>" class="hover:text-red-600 inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-emerald-600">phone</span>
                            <?= e($app['mobile']) ?>
                        </a>
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs text-slate-400 font-medium block mb-1">Email Address</label>
                    <?php if (!empty($app['email'])): ?>
                        <a href="mailto:<?= e($app['email']) ?>" class="text-slate-700 font-medium hover:text-red-600 inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[16px] text-blue-600">mail</span>
                            <?= e($app['email']) ?>
                        </a>
                    <?php else: ?>
                        <span class="text-xs text-slate-400 italic">Not provided</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Application Notes / Message Submitted -->
        <div class="cms-card p-6">
            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">chat</span>
                </span>
                <h2 class="font-bold text-slate-900 text-base">Applicant Notes & Remarks</h2>
            </div>
            <div class="p-4 bg-slate-50 rounded-lg text-sm text-slate-700 leading-relaxed border border-slate-200">
                <?= nl2br(e($app['message'])) ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Status Transition & Internal Notes (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        <!-- Status Action Card -->
        <div class="cms-card p-6 border-t-4 border-t-[#1e2a4a]">
            <h3 class="font-bold text-slate-900 text-base mb-1 brand-font">Workflow Status</h3>
            <p class="text-xs text-slate-500 mb-5">Change status and record internal staff remarks.</p>

            <form method="POST" action="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="statusSelect">
                        Application Status <span class="text-red-600">*</span>
                    </label>
                    <select id="statusSelect" name="status" class="cms-select text-sm font-semibold">
                        <?php foreach (['New', 'Under Review', 'Contacted', 'Accepted', 'Rejected', 'Withdrawn'] as $st): ?>
                            <option value="<?= $st ?>" <?= $app['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="adminNotes">
                        Internal Administrative Remarks
                    </label>
                    <textarea id="adminNotes" name="admin_notes" rows="4" placeholder="e.g. Called parent on Sep 19. Assessment booked for Sep 22..."
                              class="cms-textarea text-xs"><?= e($app['admin_notes'] ?? '') ?></textarea>
                    <span class="text-[11px] text-slate-400 mt-1 block">Private notes visible only to school administrators.</span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="cms-btn cms-btn-primary w-full text-xs">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Save Status & Notes</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Metadata Card -->
        <div class="cms-card p-6 text-xs text-slate-600 space-y-3">
            <h4 class="font-bold text-slate-900 text-xs uppercase tracking-wider mb-2">Audit Information</h4>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-400">Application No:</span>
                <span class="font-mono font-bold text-slate-800"><?= e($app['application_number']) ?></span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-100">
                <span class="text-slate-400">Submitted On:</span>
                <span class="font-medium text-slate-800"><?= date('M j, Y g:i A', strtotime($app['submitted_at'])) ?></span>
            </div>
            <div class="flex justify-between py-1.5">
                <span class="text-slate-400">Last Updated:</span>
                <span class="font-medium text-slate-800"><?= date('M j, Y g:i A', strtotime($app['updated_at'])) ?></span>
            </div>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
