<?php
/**
 * St. Monica Junior School CMS - Admission Information Manager
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('admissions');

$pageTitle = 'Admission Information CMS';
$activeMenu = 'admission-info';

// Process POST updates
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? 'update_steps';

    if ($action === 'update_steps') {
        $steps = $_POST['steps'] ?? [];
        try {
            foreach ($steps as $key => $data) {
                $title = trim($data['title'] ?? '');
                $content = trim($data['content'] ?? '');
                $icon = trim($data['icon'] ?? 'check_circle');

                if (!empty($title)) {
                    Database::query(
                        "INSERT INTO `admission_info` (`section_key`, `title`, `content`, `icon`, `status`) 
                         VALUES (:key, :title, :content, :icon, 'published')
                         ON DUPLICATE KEY UPDATE `title` = :up_title, `content` = :up_content, `icon` = :up_icon",
                        [
                            'key'        => $key,
                            'title'      => $title,
                            'content'    => $content,
                            'icon'       => $icon,
                            'up_title'   => $title,
                            'up_content' => $content,
                            'up_icon'    => $icon
                        ]
                    );
                }
            }
            log_activity('Updated Admission Steps', 'Updated 4-step admission procedure', 'admission_info');
            set_flash('success', 'Admission procedure steps updated successfully.');
            redirect(admin_url('admission-info/'));
        } catch (Exception $e) {
            set_flash('danger', 'Error updating steps: ' . $e->getMessage());
        }
    } elseif ($action === 'update_section') {
        $key = trim($_POST['section_key'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

        if (!empty($key) && !empty($title)) {
            try {
                // Sanitize HTML
                $cleanContent = sanitize_html($content);

                Database::query(
                    "INSERT INTO `admission_info` (`section_key`, `title`, `content`, `status`) 
                     VALUES (:key, :title, :content, :status)
                     ON DUPLICATE KEY UPDATE `title` = :up_title, `content` = :up_content, `status` = :up_status",
                    [
                        'key'        => $key,
                        'title'      => $title,
                        'content'    => $cleanContent,
                        'status'     => $status,
                        'up_title'   => $title,
                        'up_content' => $cleanContent,
                        'up_status'  => $status
                    ]
                );

                log_activity('Updated Admission Section', "Section: {$title} ({$key})", 'admission_info');
                set_flash('success', "Section '{$title}' updated successfully.");
                redirect(admin_url('admission-info/'));
            } catch (Exception $e) {
                set_flash('danger', 'Error updating section: ' . $e->getMessage());
            }
        }
    }
}

// Fetch current admission records
$records = [];
try {
    $rows = Database::fetchAll("SELECT * FROM `admission_info` ORDER BY `display_order` ASC, `id` ASC");
    foreach ($rows as $r) {
        $records[$r['section_key']] = $r;
    }
} catch (Exception $e) {
    // Database might not be initialized
}

// Fetch incoming pupil applications for live visibility in admission-info
$recentApplications = [];
$totalNewApps = 0;
$totalApps = 0;
try {
    $totalApps = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions`");
    $totalNewApps = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `status` = 'New'");
    $recentApplications = Database::fetchAll("SELECT * FROM `admissions` ORDER BY `submitted_at` DESC, `id` DESC LIMIT 5");
} catch (Exception $e) {
    // Table might not exist yet
}

// Default steps definition
$defaultSteps = [
    'step_1' => [
        'title'   => $records['step_1']['title'] ?? 'Inquiry & School Visit',
        'content' => $records['step_1']['content'] ?? 'Contact us via phone, WhatsApp, email, or visit our campus in Kasanje to learn more about our academic programs, school culture, and admission requirements.',
        'icon'    => $records['step_1']['icon'] ?? 'help_center'
    ],
    'step_2' => [
        'title'   => $records['step_2']['title'] ?? 'Application Form',
        'content' => $records['step_2']['content'] ?? 'Complete the application form either through this website or in person at the school office. Submit required documents including birth certificate and previous school reports.',
        'icon'    => $records['step_2']['icon'] ?? 'description'
    ],
    'step_3' => [
        'title'   => $records['step_3']['title'] ?? 'Pupil Assessment',
        'content' => $records['step_3']['content'] ?? 'Your child will undergo a simple, friendly assessment to help our academic team understand their current learning level and ensure proper class placement.',
        'icon'    => $records['step_3']['icon'] ?? 'person_search'
    ],
    'step_4' => [
        'title'   => $records['step_4']['title'] ?? 'Enrollment & Fees',
        'content' => $records['step_4']['content'] ?? 'Upon acceptance, confirm enrollment by completing registration formalities, paying school fees at our partner banks, and collecting school material lists.',
        'icon'    => $records['step_4']['icon'] ?? 'check_circle'
    ]
];

$requirementsRecord = $records['requirements'] ?? [
    'title'   => 'General Admission Requirements',
    'content' => "<p>St. Monica Junior School admits pupils without distinction of race, nationality, or religious background. Requirements include:</p><ul><li>Completed and signed Application Form</li><li>Copy of Birth Certificate or Immunization Card (for Nursery)</li><li>Original or verified copy of the most recent School Report Card</li><li>2 recent passport-size color photographs of the child</li><li>Copy of Parent / Guardian National ID or Passport</li></ul>",
    'status'  => 'published'
];

$feesRecord = $records['fees_note'] ?? [
    'title'   => 'School Fees Policy & Guidelines',
    'content' => "<p>Our tuition and boarding fees are structured to provide premium learning and welfare at competitive rates. Fees are payable before the start of each term via school bank accounts or mobile money merchant codes. Contact the school bursar for detailed fee structures per class level.</p>",
    'status'  => 'published'
];

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Admission Information Management</h1>
        <p class="text-sm text-slate-500 mt-1">Manage public admissions steps, requirements, documents, and fee policy information.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('admissions/') ?>" class="cms-btn cms-btn-outline text-xs flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
            <span>View All Applications (<?= $totalApps ?>)</span>
            <?php if ($totalNewApps > 0): ?>
                <span class="px-1.5 py-0.5 bg-red-600 text-white rounded-full text-[10px] font-bold"><?= $totalNewApps ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= public_url('about.html') ?>" target="_blank" class="cms-btn cms-btn-primary text-xs">
            <span class="material-symbols-outlined text-[16px]">visibility</span>
            <span>View on Website</span>
        </a>
    </div>
</div>

<div class="space-y-8 max-w-5xl">
    <!-- Live Submitted Applications Panel -->
    <div class="cms-card overflow-hidden border-l-4 border-l-[#1e2a4a]">
        <div class="p-5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#1e2a4a] text-white flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-[24px]">how_to_reg</span>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900 brand-font">Incoming Pupil Applications</h2>
                        <?php if ($totalNewApps > 0): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 animate-pulse">
                                <?= $totalNewApps ?> New
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500">Live submissions received from parents through the website Application Form.</p>
                </div>
            </div>
            <a href="<?= admin_url('admissions/') ?>" class="cms-btn cms-btn-accent text-xs flex items-center gap-1.5 self-start sm:self-auto">
                <span>Open Applications Manager</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>

        <?php if (empty($recentApplications)): ?>
            <div class="p-8 text-center text-slate-400 text-xs">
                <span class="material-symbols-outlined text-4xl text-slate-300 block mb-2">inbox</span>
                <p class="font-medium text-slate-600">No applications received yet.</p>
                <p class="mt-1">When parents submit the Application Form on the website, their details appear here and in the Applications manager.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="cms-table text-xs">
                    <thead>
                        <tr>
                            <th style="width: 130px;">App Reference</th>
                            <th>Pupil Name</th>
                            <th>Class</th>
                            <th>Parent & Phone</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentApplications as $app): 
                            $badge = match($app['status']) {
                                'New'          => 'bg-red-100 text-red-700 border-red-200',
                                'Under Review' => 'bg-amber-100 text-amber-800 border-amber-200',
                                'Contacted'    => 'bg-blue-100 text-blue-800 border-blue-200',
                                'Accepted'     => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'Rejected'     => 'bg-slate-100 text-slate-700 border-slate-200',
                                'Withdrawn'    => 'bg-purple-100 text-purple-800 border-purple-200',
                                default        => 'bg-slate-100 text-slate-700'
                            };
                        ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="font-bold font-mono text-[#1e2a4a]">
                                    <a href="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="hover:underline">
                                        <?= e($app['application_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="font-semibold text-slate-900"><?= e($app['pupil_name']) ?></div>
                                </td>
                                <td>
                                    <span class="px-2 py-0.5 rounded bg-slate-100 font-medium text-slate-700"><?= e($app['pupil_class']) ?></span>
                                </td>
                                <td>
                                    <div class="font-medium text-slate-800"><?= e($app['parent_name']) ?></div>
                                    <div class="text-[11px] text-slate-500 font-mono"><?= e($app['mobile']) ?></div>
                                </td>
                                <td>
                                    <span class="text-slate-600 truncate max-w-[140px] block" title="<?= e($app['location']) ?>">
                                        <?= e($app['location']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="px-2 py-0.5 rounded-full font-bold border text-[11px] inline-flex items-center gap-1 <?= $badge ?>">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <?= e($app['status']) ?>
                                    </span>
                                </td>
                                <td class="text-slate-500 whitespace-nowrap">
                                    <?= date('M j, Y H:i', strtotime($app['submitted_at'])) ?>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="cms-btn cms-btn-outline text-[11px] py-1 px-2.5" title="Review Application">
                                        <span>View Dossier</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <!-- Section 1: 4-Step Admission Procedure -->
    <div class="cms-card overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#1e2a4a] text-white flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">route</span>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 brand-font">Admission Procedure (4 Steps)</h2>
                    <p class="text-xs text-slate-500">Displayed on the public About Us page to guide parents through the admission journey.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= admin_url('admission-info/') ?>" class="p-6 space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_steps">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php $i = 1; foreach ($defaultSteps as $stepKey => $step): ?>
                    <div class="p-5 rounded-lg border border-slate-200 bg-white relative">
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                Step <?= $i ?>
                            </span>
                            <div class="flex items-center gap-1 text-slate-400">
                                <span class="material-symbols-outlined text-[18px]">icon:</span>
                                <input type="text" name="steps[<?= $stepKey ?>][icon]" value="<?= e($step['icon']) ?>" 
                                       class="cms-input py-1 px-2 text-xs w-28 font-mono" placeholder="Material Icon">
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Step Title</label>
                                <input type="text" name="steps[<?= $stepKey ?>][title]" value="<?= e($step['title']) ?>" 
                                       class="cms-input text-sm font-semibold" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Step Description</label>
                                <textarea name="steps[<?= $stepKey ?>][content]" rows="3" 
                                          class="cms-textarea text-xs" required><?= e($step['content']) ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php $i++; endforeach; ?>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="cms-btn cms-btn-primary">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span>Save 4-Step Procedure</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Section 2: General Requirements & Required Documents -->
    <div class="cms-card overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">fact_check</span>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 brand-font">Admission Requirements & Documents</h2>
                    <p class="text-xs text-slate-500">General eligibility, document checklist, and age guidelines.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= admin_url('admission-info/') ?>" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_section">
            <input type="hidden" name="section_key" value="requirements">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Section Title</label>
                    <input type="text" name="title" value="<?= e($requirementsRecord['title']) ?>" class="cms-input font-bold text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Publication Status</label>
                    <select name="status" class="cms-select text-sm font-semibold">
                        <option value="published" <?= ($requirementsRecord['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= ($requirementsRecord['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Requirements Content (Supports HTML list tags)</label>
                <textarea name="content" rows="6" class="cms-textarea text-xs font-mono" placeholder="Enter requirements list..."><?= e($requirementsRecord['content']) ?></textarea>
                <span class="text-[11px] text-slate-400 mt-1 block">Supports semantic tags: &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;.</span>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="cms-btn cms-btn-primary">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span>Save Requirements</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Section 3: School Fees Policy Note -->
    <div class="cms-card overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center">
                    <span class="material-symbols-outlined text-[24px]">payments</span>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 brand-font">School Fees Policy Note</h2>
                    <p class="text-xs text-slate-500">Information on fee structures, payment modes, and financial terms.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= admin_url('admission-info/') ?>" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_section">
            <input type="hidden" name="section_key" value="fees_note">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Section Title</label>
                    <input type="text" name="title" value="<?= e($feesRecord['title']) ?>" class="cms-input font-bold text-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Publication Status</label>
                    <select name="status" class="cms-select text-sm font-semibold">
                        <option value="published" <?= ($feesRecord['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= ($feesRecord['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Fees Content Note</label>
                <textarea name="content" rows="4" class="cms-textarea text-xs font-mono" placeholder="Enter fee payment details..."><?= e($feesRecord['content']) ?></textarea>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="cms-btn cms-btn-primary">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    <span>Save Fees Policy</span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
