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

require_once CMS_ROOT . '/services/EmailService.php';

// Handle status update / note submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $formAction = $_POST['form_action'] ?? 'update_status';

    if ($formAction === 'add_note') {
        $noteText = trim($_POST['note'] ?? '');
        if ($noteText === '') {
            set_flash('danger', 'Please enter a note before saving.');
        } else {
            try {
                Database::insert('admission_notes', [
                    'admission_id' => $id,
                    'admin_id'     => $_SESSION['admin_id'] ?? null,
                    'admin_name'   => $_SESSION['admin_name'] ?? 'Admin',
                    'note'         => $noteText
                ]);
                log_activity('Added Note', "Internal note added to application #{$app['application_number']}", 'admissions', $id);
                set_flash('success', 'Internal note added.');
            } catch (Exception $e) {
                set_flash('danger', 'Failed to save note.');
            }
        }
        redirect(admin_url('admissions/view.php?id=' . $id));
    }

    $newStatus = trim($_POST['status'] ?? $app['status']);
    $notifyApplicant = !empty($_POST['notify_applicant']);

    $allowedStatuses = ['New', 'Under Review', 'Contacted', 'Accepted', 'Rejected', 'Withdrawn'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
        $newStatus = $app['status'];
    }

    try {
        $oldStatus = $app['status'];
        Database::update('admissions', [
            'status' => $newStatus
        ], 'id = :id', ['id' => $id]);

        $logMsg = "Application #{$app['application_number']} ({$app['pupil_name']}) status updated";
        if ($oldStatus !== $newStatus) {
            $logMsg .= " from '{$oldStatus}' to '{$newStatus}'";
        }
        log_activity('Status Change', $logMsg, 'admissions', $id);

        if ($notifyApplicant && !empty($app['email']) && $oldStatus !== $newStatus) {
            EmailService::sendTemplate('application_status_update', $app['email'], [
                'parent_name'        => $app['parent_name'],
                'pupil_name'         => $app['pupil_name'],
                'application_number' => $app['application_number'],
                'application_status' => $newStatus,
                'school_name'        => 'St. Monica Junior School Kasanje'
            ], $app['parent_name']);
        }

        set_flash('success', "Application status successfully updated to '{$newStatus}'.");
        redirect(admin_url('admissions/view.php?id=' . $id));
    } catch (Exception $e) {
        set_flash('danger', 'Failed to update application status.');
    }
}

// Notes timeline (append-only, newest first)
$notes = [];
try {
    $notes = Database::fetchAll("SELECT * FROM `admission_notes` WHERE `admission_id` = :id ORDER BY `created_at` DESC", ['id' => $id]);
} catch (Exception $e) {
    // Table may not exist yet on an unmigrated install
}

// Status history timeline, sourced from the existing activity log (no duplicate audit system)
$statusHistory = [];
try {
    $statusHistory = Database::fetchAll(
        "SELECT * FROM `activity_logs` WHERE `module` = 'admissions' AND `record_id` = :id AND `action` = 'Status Change' ORDER BY `created_at` DESC",
        ['id' => $id]
    );
} catch (Exception $e) {
    // ignore
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

        <!-- Internal Notes Timeline -->
        <div class="cms-card p-6">
            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">history_edu</span>
                </span>
                <h2 class="font-bold text-slate-900 text-base">Internal Notes</h2>
            </div>

            <form method="POST" action="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="mb-5">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="add_note">
                <textarea name="note" rows="3" required placeholder="e.g. Called parent on Sep 19. Assessment booked for Sep 22..." class="cms-textarea text-sm"></textarea>
                <div class="flex justify-end mt-2">
                    <button type="submit" class="cms-btn cms-btn-primary text-xs">
                        <span class="material-symbols-outlined text-[16px]">add_comment</span>
                        <span>Add Note</span>
                    </button>
                </div>
            </form>

            <?php if (!empty($app['admin_notes'])): ?>
                <div class="flex gap-3 pb-3 mb-3 border-b border-slate-100">
                    <div class="w-2 h-2 rounded-full bg-slate-300 mt-1.5 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(e($app['admin_notes'])) ?></p>
                        <p class="text-[11px] text-slate-400 mt-1">Legacy note (recorded before internal note history was introduced)</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($notes) && empty($app['admin_notes'])): ?>
                <p class="text-xs text-slate-400 py-4 text-center">No internal notes recorded yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($notes as $note): ?>
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-purple-500 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-700 leading-relaxed"><?= nl2br(e($note['note'])) ?></p>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    <?= e($note['admin_name']) ?> &bull; <?= date('M j, Y g:i A', strtotime($note['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Status History Timeline -->
        <div class="cms-card p-6">
            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[20px]">timeline</span>
                </span>
                <h2 class="font-bold text-slate-900 text-base">Status History</h2>
            </div>
            <?php if (empty($statusHistory)): ?>
                <p class="text-xs text-slate-400 py-4 text-center">No status changes recorded yet.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($statusHistory as $entry): ?>
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-700"><?= e($entry['details']) ?></p>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    <?= e($entry['admin_name']) ?> &bull; <?= date('M j, Y g:i A', strtotime($entry['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                <input type="hidden" name="form_action" value="update_status">

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

                <?php if (!empty($app['email'])): ?>
                <label class="flex items-start gap-2 text-xs text-slate-600 cursor-pointer">
                    <input type="checkbox" name="notify_applicant" value="1" class="mt-0.5 rounded border-slate-300">
                    <span>Notify the applicant by email (<?= e($app['email']) ?>) when the status changes.</span>
                </label>
                <?php endif; ?>

                <div class="pt-2">
                    <button type="submit" class="cms-btn cms-btn-primary w-full text-xs">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Save Status</span>
                    </button>
                </div>
            </form>
            <p class="text-[11px] text-slate-400 mt-3">Use the Internal Notes panel below to record private remarks — status changes are logged automatically.</p>
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
