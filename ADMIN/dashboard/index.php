<?php
/**
 * St. Monica Junior School CMS - Main Admin Dashboard (Phase Two Enhanced)
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$pageTitle = 'Dashboard Overview';
$activeMenu = 'dashboard';

// Fetch summary metrics safely
try {
    // Core Phase One metrics
    $totalStaff     = (int)Database::fetchColumn("SELECT COUNT(*) FROM `staff`");
    $publishedNews  = (int)Database::fetchColumn("SELECT COUNT(*) FROM `news_events` WHERE `type` = 'news' AND `status` = 'published'");
    $upcomingEvents = (int)Database::fetchColumn("SELECT COUNT(*) FROM `news_events` WHERE `type` IN ('event', 'sports') AND `status` = 'published'");
    $draftNews      = (int)Database::fetchColumn("SELECT COUNT(*) FROM `news_events` WHERE `status` = 'draft'");
    $totalGallery   = (int)Database::fetchColumn("SELECT COUNT(*) FROM `gallery` WHERE `status` = 'published'");

    // Phase Two metrics
    $totalAdmissions = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions`");
    $newAdmissions   = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `status` = 'New'");
    $totalMedia      = (int)Database::fetchColumn("SELECT COUNT(*) FROM `media_library`");
    $testimonials    = (int)Database::fetchColumn("SELECT COUNT(*) FROM `testimonials` WHERE `status` = 'published'");
    
    // Recent applications for Admissions widget
    $recentAdmissions = Database::fetchAll("SELECT * FROM `admissions` ORDER BY `created_at` DESC LIMIT 5");

    // Fetch recent activity
    $recentActivities = Database::fetchAll("SELECT * FROM `activity_logs` ORDER BY `created_at` DESC LIMIT 6");
    
    // Fetch latest news/events
    $latestArticles = Database::fetchAll("SELECT * FROM `news_events` ORDER BY `created_at` DESC LIMIT 4");

    // Fetch school contact info preview
    $contactInfo = Database::fetchOne("SELECT * FROM `contact_information` LIMIT 1");
} catch (Exception $e) {
    $totalStaff = $publishedNews = $upcomingEvents = $draftNews = $totalGallery = 0;
    $totalAdmissions = $newAdmissions = $totalMedia = $testimonials = 0;
    $recentAdmissions = $recentActivities = $latestArticles = [];
    $contactInfo = null;
    $dbError = $e->getMessage();
}

include CMS_ROOT . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="cms-card p-6 sm:p-8 mb-8 bg-gradient-to-r from-[#1e2a4a] via-[#283863] to-[#1e2a4a] text-white border-0 shadow-lg relative overflow-hidden">
    <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
        <span class="material-symbols-outlined text-[180px]">school</span>
    </div>

    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-red-600 text-white shadow-sm">
                    <span class="material-symbols-outlined text-[14px]">verified</span>
                    St. Monica CMS &bull; Phase Two
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-white/15 text-slate-200 backdrop-blur-sm">
                    Role: <?= e(ucwords(str_replace('_', ' ', $_SESSION['admin_role'] ?? 'Administrator'))) ?>
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight brand-font">Welcome back, <?= e($currentAdmin['name'] ?? 'Administrator') ?>!</h1>
            <p class="text-sm text-slate-200 mt-2 max-w-2xl leading-relaxed">
                Oversee admissions, publish academic content, optimize search rankings, and curate media for St. Monica Junior School Kasanje.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <?php if (can_manage('admissions')): ?>
                <a href="<?= admin_url('admissions/') ?>" class="cms-btn cms-btn-accent text-xs">
                    <span class="material-symbols-outlined text-[16px]">how_to_reg</span>
                    <span>Admissions (<?= $newAdmissions ?> New)</span>
                </a>
            <?php endif; ?>
            <?php if (can_manage('media')): ?>
                <a href="<?= admin_url('media/upload.php') ?>" class="cms-btn cms-btn-outline text-xs text-slate-800 bg-white hover:bg-slate-100">
                    <span class="material-symbols-outlined text-[16px]">upload_file</span>
                    <span>Upload Media</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($dbError)): ?>
    <div class="cms-alert bg-amber-50 border-l-4 border-amber-500 text-amber-900 p-4 rounded-lg mb-8">
        <div class="font-bold flex items-center gap-2">
            <span class="material-symbols-outlined">warning</span> Database Notice:
        </div>
        <p class="text-sm mt-1"><?= e($dbError) ?></p>
        <p class="text-xs mt-2">
            If you need to run migration updates, please click <a href="<?= admin_url('database/setup.php') ?>" class="underline font-bold">here to execute migrations</a>.
        </p>
    </div>
<?php endif; ?>

<!-- Quick Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Metric 1: Admissions Applications -->
    <div class="cms-card p-5 border-l-4 border-l-emerald-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Admissions</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <h3 class="text-2xl font-bold text-slate-900"><?= $totalAdmissions ?></h3>
                    <?php if ($newAdmissions > 0): ?>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 animate-pulse">
                            <?= $newAdmissions ?> New
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">how_to_reg</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">Pupil applicants</span>
            <a href="<?= admin_url('admissions/') ?>" class="font-semibold text-emerald-600 hover:text-emerald-700">Review &rarr;</a>
        </div>
    </div>

    <!-- Metric 2: News & Events -->
    <div class="cms-card p-5 border-l-4 border-l-red-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Articles & Events</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <h3 class="text-2xl font-bold text-slate-900"><?= $publishedNews + $upcomingEvents ?></h3>
                    <?php if ($draftNews > 0): ?>
                        <span class="text-[11px] text-slate-400 font-medium">(<?= $draftNews ?> draft)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">newspaper</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">Published posts</span>
            <a href="<?= admin_url('news-events/') ?>" class="font-semibold text-red-600 hover:text-red-700">Manage &rarr;</a>
        </div>
    </div>

    <!-- Metric 3: Media Library & Assets -->
    <div class="cms-card p-5 border-l-4 border-l-indigo-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Media Assets</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-1"><?= $totalMedia ?></h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">photo_library</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">Optimized library files</span>
            <a href="<?= admin_url('media/') ?>" class="font-semibold text-indigo-600 hover:text-indigo-700">Library &rarr;</a>
        </div>
    </div>

    <!-- Metric 4: Testimonials & Staff -->
    <div class="cms-card p-5 border-l-4 border-l-amber-600">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Testimonials</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <h3 class="text-2xl font-bold text-slate-900"><?= $testimonials ?></h3>
                    <span class="text-[11px] text-slate-400 font-medium">&bull; <?= $totalStaff ?> staff</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[26px]">format_quote</span>
            </div>
        </div>
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">Parent feedback</span>
            <a href="<?= admin_url('testimonials/') ?>" class="font-semibold text-amber-600 hover:text-amber-700">View &rarr;</a>
        </div>
    </div>
</div>

<!-- Main Dashboard Grid: Left (8 cols) & Right (4 cols) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
    <!-- Left Column: Quick Actions + Recent Admissions + Latest Articles -->
    <div class="lg:col-span-8 space-y-8">
        
        <!-- Quick Actions Panel -->
        <div class="cms-card p-6">
            <h2 class="text-base font-bold text-slate-900 brand-font mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">bolt</span>
                Quick Administration Actions
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php if (can_manage('admissions')): ?>
                    <a href="<?= admin_url('admissions/') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-emerald-600 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">how_to_reg</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Admissions</span>
                    </a>
                <?php endif; ?>

                <?php if (can_manage('media')): ?>
                    <a href="<?= admin_url('media/upload.php') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-indigo-600 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">add_photo_alternate</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Upload Media</span>
                    </a>
                <?php endif; ?>

                <?php if (can_manage('news_events')): ?>
                    <a href="<?= admin_url('news-events/create.php?type=news') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-red-600 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">post_add</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Create Post</span>
                    </a>
                <?php endif; ?>

                <?php if (can_manage('testimonials')): ?>
                    <a href="<?= admin_url('testimonials/create.php') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-amber-600 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">format_quote</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Add Testimonial</span>
                    </a>
                <?php endif; ?>

                <?php if (can_manage('seo')): ?>
                    <a href="<?= admin_url('seo/') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-blue-600 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">search</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Manage SEO</span>
                    </a>
                <?php endif; ?>

                <?php if (has_role('super_admin')): ?>
                    <a href="<?= admin_url('users/') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-purple-600 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">manage_accounts</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Admin Staff</span>
                    </a>
                <?php endif; ?>

                <?php if (has_role('super_admin')): ?>
                    <a href="<?= admin_url('logs/') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition text-center group">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                            <span class="material-symbols-outlined text-[22px]">history</span>
                        </div>
                        <span class="text-xs font-semibold text-slate-800 block">Audit Logs</span>
                    </a>
                <?php endif; ?>

                <a href="<?= admin_url('profile/') ?>" class="p-3.5 rounded-lg border border-slate-200 hover:border-[#1e2a4a] hover:bg-slate-50 transition text-center group">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 text-[#1e2a4a] flex items-center justify-center mx-auto mb-2 group-hover:scale-105 transition">
                        <span class="material-symbols-outlined text-[22px]">account_circle</span>
                    </div>
                    <span class="text-xs font-semibold text-slate-800 block">My Profile</span>
                </a>
            </div>
        </div>

        <!-- Recent Admission Inquiries -->
        <?php if (can_manage('admissions')): ?>
            <div class="cms-card overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-slate-900 brand-font flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">how_to_reg</span>
                        Recent Pupil Applications
                    </h2>
                    <a href="<?= admin_url('admissions/') ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">View All Applications &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="cms-table">
                        <thead>
                            <tr>
                                <th>App #</th>
                                <th>Pupil Name</th>
                                <th>Class</th>
                                <th>Parent / Guardian</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentAdmissions)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-slate-400 text-xs">
                                        No pupil applications received yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAdmissions as $app): 
                                    $stBadge = match($app['status']) {
                                        'New' => 'bg-amber-100 text-amber-800 border-amber-200 font-bold',
                                        'Under Review' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'Interview Scheduled' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'Accepted' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'Waitlisted' => 'bg-orange-100 text-orange-800 border-orange-200',
                                        'Rejected' => 'bg-red-100 text-red-800 border-red-200',
                                        default => 'bg-slate-100 text-slate-800'
                                    };
                                ?>
                                    <tr class="hover:bg-slate-50/80 transition text-xs">
                                        <td class="font-mono font-bold text-slate-700">
                                            <?= e($app['application_number']) ?>
                                        </td>
                                        <td class="font-semibold text-slate-900">
                                            <?= e($app['pupil_name']) ?>
                                        </td>
                                        <td>
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">
                                                <?= e($app['pupil_class']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-slate-800"><?= e($app['parent_name']) ?></div>
                                            <div class="text-[11px] text-slate-400 font-mono"><?= e($app['mobile']) ?></div>
                                        </td>
                                        <td>
                                            <span class="px-2 py-0.5 rounded-full text-[11px] border <?= $stBadge ?>">
                                                <?= e($app['status']) ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="<?= admin_url('admissions/view.php?id=' . $app['id']) ?>" class="p-1.5 text-slate-500 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Review Application">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Latest Articles / Events Table -->
        <div class="cms-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 brand-font flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-600">article</span>
                    Recent News & Event Posts
                </h2>
                <a href="<?= admin_url('news-events/') ?>" class="text-xs font-semibold text-red-600 hover:text-red-700">View All &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Published Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestArticles)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-8 text-slate-400 text-xs">
                                    No news or events found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($latestArticles as $article): ?>
                                <tr>
                                    <td>
                                        <div class="font-semibold text-slate-900 max-w-xs truncate"><?= e($article['title']) ?></div>
                                        <div class="text-xs text-slate-400">/<?= e($article['slug']) ?></div>
                                    </td>
                                    <td>
                                        <span class="cms-badge badge-<?= e($article['type']) ?>">
                                            <?= e(ucfirst($article['type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cms-badge badge-<?= e($article['status']) ?>">
                                            <?= e(ucfirst($article['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        <?= $article['published_at'] ? date('M j, Y', strtotime($article['published_at'])) : date('M j, Y', strtotime($article['created_at'])) ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= admin_url('news-events/edit.php?id=' . $article['id']) ?>" class="p-1.5 text-slate-500 hover:text-[#1e2a4a] rounded hover:bg-slate-100" title="Edit">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Recent Activity Log & Contact Snapshot -->
    <div class="lg:col-span-4 space-y-8">
        
        <!-- Publishing Status Card -->
        <div class="cms-card p-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Content Publishing Status</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Published Articles
                    </span>
                    <span class="font-bold text-slate-900"><?= $publishedNews + $upcomingEvents ?></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Draft Content
                    </span>
                    <span class="font-bold text-slate-900"><?= $draftNews ?></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Media Library Files
                    </span>
                    <span class="font-bold text-slate-900"><?= $totalMedia ?></span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                        Published Testimonials
                    </span>
                    <span class="font-bold text-slate-900"><?= $testimonials ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Activity Log -->
        <div class="cms-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900 brand-font uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-600 text-[18px]">history</span>
                    Recent Activity
                </h3>
                <?php if (has_role('super_admin')): ?>
                    <a href="<?= admin_url('logs/') ?>" class="text-xs font-semibold text-red-600 hover:text-red-700">All Logs &rarr;</a>
                <?php endif; ?>
            </div>
            <div class="space-y-4">
                <?php if (empty($recentActivities)): ?>
                    <p class="text-xs text-slate-400 py-4 text-center">No recent administrative logs.</p>
                <?php else: ?>
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="flex items-start gap-3 text-xs pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                            <div class="w-2 h-2 rounded-full bg-red-500 mt-1.5 flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-slate-800 truncate"><?= e($activity['action']) ?></div>
                                <?php if ($activity['details']): ?>
                                    <div class="text-slate-500 truncate mt-0.5"><?= e($activity['details']) ?></div>
                                <?php endif; ?>
                                <div class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                                    <span><?= e($activity['admin_name']) ?></span>
                                    <span><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- School Contact Snapshot -->
        <div class="cms-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900 brand-font uppercase tracking-wider flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-600 text-[18px]">phone</span>
                    Live Contact Details
                </h3>
                <?php if (can_manage('contact')): ?>
                    <a href="<?= admin_url('contact/') ?>" class="text-xs font-semibold text-red-600 hover:text-red-700">Edit</a>
                <?php endif; ?>
            </div>
            <?php if ($contactInfo): ?>
                <div class="space-y-3 text-xs">
                    <div class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-slate-400 text-[16px] flex-shrink-0 mt-0.5">call</span>
                        <div>
                            <span class="font-semibold text-slate-800"><?= e($contactInfo['phone']) ?></span>
                            <?php if ($contactInfo['alternative_phone']): ?>
                                <span class="text-slate-500 block"><?= e($contactInfo['alternative_phone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-slate-400 text-[16px] flex-shrink-0 mt-0.5">mail</span>
                        <span class="text-slate-700 break-all"><?= e($contactInfo['email']) ?></span>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-slate-400 text-[16px] flex-shrink-0 mt-0.5">location_on</span>
                        <span class="text-slate-700"><?= e($contactInfo['address']) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-xs text-slate-400">Contact information not initialized.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
