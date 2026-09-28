<?php
/**
 * St. Monica Junior School CMS - Layout Sidebar (Phase Three)
 */
$activeMenu = $activeMenu ?? '';
$newAdmissionsCount = 0;
$newEnquiriesCount = 0;
$unreadNotifCount = 0;
try {
    require_once CMS_ROOT . '/includes/database.php';
    $newAdmissionsCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `status` = 'New'");
    $newEnquiriesCount = (int)Database::fetchColumn("SELECT COUNT(*) FROM `enquiries` WHERE `status` = 'new'");
    if (!empty($_SESSION['admin_id'])) {
        require_once CMS_ROOT . '/services/NotificationService.php';
        $unreadNotifCount = NotificationService::unreadCount((int)$_SESSION['admin_id']);
    }
} catch (Exception $e) {
    // Database table might not be initialized yet
}
?>
<aside id="adminSidebar" class="admin-sidebar fixed inset-y-0 left-0 top-16 z-40 flex flex-col justify-between py-6 px-4 overflow-y-auto lg:sticky lg:top-16 lg:z-auto lg:h-[calc(100vh-4rem)]">
    <div class="space-y-6">
        <!-- Overview -->
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Overview</div>
            <nav class="space-y-1">
                <a href="<?= admin_url('dashboard/') ?>" class="sidebar-link <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span>
                    <span>Dashboard</span>
                </a>
            </nav>
        </div>

        <!-- Admissions -->
        <?php if (can_manage('admissions')): ?>
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Admissions</div>
            <nav class="space-y-1">
                <a href="<?= admin_url('admissions/') ?>" class="sidebar-link <?= ($activeMenu === 'admissions') ? 'active' : '' ?> flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
                        <span>Applications</span>
                    </div>
                    <?php if ($newAdmissionsCount > 0): ?>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white shadow-sm">
                            <?= $newAdmissionsCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="<?= admin_url('admission-info/') ?>" class="sidebar-link <?= ($activeMenu === 'admission-info') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">menu_book</span>
                    <span>Admission Info</span>
                </a>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Website -->
        <?php if (can_manage('homepage') || can_manage('about') || can_manage('contact') || can_manage('seo')): ?>
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Website</div>
            <nav class="space-y-1">
                <?php if (can_manage('homepage')): ?>
                <a href="<?= admin_url('homepage/') ?>" class="sidebar-link <?= ($activeMenu === 'homepage') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">home</span>
                    <span>Homepage</span>
                </a>
                <?php endif; ?>

                <?php if (can_manage('about')): ?>
                <a href="<?= admin_url('about/') ?>" class="sidebar-link <?= ($activeMenu === 'about') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">info</span>
                    <span>About Us</span>
                </a>
                <?php endif; ?>

                <?php if (can_manage('contact')): ?>
                <a href="<?= admin_url('contact/') ?>" class="sidebar-link <?= ($activeMenu === 'contact') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">contacts</span>
                    <span>Contact Info</span>
                </a>
                <?php endif; ?>

                <?php if (can_manage('seo')): ?>
                <a href="<?= admin_url('seo/') ?>" class="sidebar-link <?= ($activeMenu === 'seo') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">travel_explore</span>
                    <span>Website SEO</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <?php if (can_manage('staff') || can_manage('news-events') || can_manage('testimonials') || can_manage('media') || can_manage('gallery')): ?>
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Content</div>
            <nav class="space-y-1">
                <?php if (can_manage('staff')): ?>
                <a href="<?= admin_url('staff/') ?>" class="sidebar-link <?= ($activeMenu === 'staff') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">badge</span>
                    <span>Staff Team</span>
                </a>
                <?php endif; ?>

                <?php if (can_manage('news-events')): ?>
                <a href="<?= admin_url('news-events/') ?>" class="sidebar-link <?= ($activeMenu === 'news-events') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">event_note</span>
                    <span>News & Events</span>
                </a>
                <?php endif; ?>

                <?php if (can_manage('testimonials')): ?>
                <?php $pendingReviewsCount = pending_reviews_count(); ?>
                <a href="<?= admin_url($pendingReviewsCount > 0 ? 'testimonials/?status=draft' : 'testimonials/') ?>" class="sidebar-link <?= ($activeMenu === 'testimonials') ? 'active' : '' ?> flex items-center justify-between"
                   <?= $pendingReviewsCount > 0 ? 'title="' . $pendingReviewsCount . ' review(s) awaiting approval"' : '' ?>>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">format_quote</span>
                        <span>Testimonials</span>
                    </div>
                    <?php if ($pendingReviewsCount > 0): ?>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white shadow-sm"><?= $pendingReviewsCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (can_manage('gallery')): ?>
                <a href="<?= admin_url('gallery/') ?>" class="sidebar-link <?= ($activeMenu === 'gallery') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">photo_library</span>
                    <span>Gallery Albums</span>
                </a>
                <?php endif; ?>

                <?php if (can_manage('media')): ?>
                <a href="<?= admin_url('media/') ?>" class="sidebar-link <?= ($activeMenu === 'media') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">perm_media</span>
                    <span>Media Library</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Communications -->
        <?php if (can_manage('notifications') || can_manage('inquiries') || can_manage('newsletter') || has_role('super_admin')): ?>
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Communications</div>
            <nav class="space-y-1">
                <?php if (can_manage('notifications')): ?>
                <a href="<?= admin_url('notifications/') ?>" class="sidebar-link <?= ($activeMenu === 'notifications') ? 'active' : '' ?> flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        <span>Notifications</span>
                    </div>
                    <?php if ($unreadNotifCount > 0): ?>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white shadow-sm"><?= $unreadNotifCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (can_manage('inquiries')): ?>
                <a href="<?= admin_url('inquiries/') ?>" class="sidebar-link <?= ($activeMenu === 'inquiries') ? 'active' : '' ?> flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[20px]">mail</span>
                        <span>Enquiries</span>
                    </div>
                    <?php if ($newEnquiriesCount > 0): ?>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-500 text-white shadow-sm"><?= $newEnquiriesCount ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <?php if (can_manage('newsletter')): ?>
                <a href="<?= admin_url('newsletter/') ?>" class="sidebar-link <?= ($activeMenu === 'newsletter') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">campaign</span>
                    <span>Newsletter</span>
                </a>
                <?php endif; ?>

                <?php if (has_role('super_admin')): ?>
                <a href="<?= admin_url('email-templates/') ?>" class="sidebar-link <?= ($activeMenu === 'email-templates') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">forward_to_inbox</span>
                    <span>Email Templates</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <!-- System -->
        <?php if (has_role('super_admin')): ?>
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">System</div>
            <nav class="space-y-1">
                <a href="<?= admin_url('logs/') ?>" class="sidebar-link <?= ($activeMenu === 'logs') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                    <span>Activity Log</span>
                </a>
                <a href="<?= admin_url('trash/') ?>" class="sidebar-link <?= ($activeMenu === 'trash') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                    <span>Trash</span>
                </a>
                <a href="<?= admin_url('backups/') ?>" class="sidebar-link <?= ($activeMenu === 'backups') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">backup</span>
                    <span>Backups</span>
                </a>
                <a href="<?= admin_url('security/') ?>" class="sidebar-link <?= ($activeMenu === 'security') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">security</span>
                    <span>Security Center</span>
                </a>
                <a href="<?= admin_url('system/') ?>" class="sidebar-link <?= ($activeMenu === 'system') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">monitor_heart</span>
                    <span>System Health</span>
                </a>
                <a href="<?= admin_url('settings/') ?>" class="sidebar-link <?= ($activeMenu === 'settings') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">tune</span>
                    <span>Settings</span>
                </a>
            </nav>
        </div>
        <?php endif; ?>

        <!-- Administration -->
        <div>
            <div class="px-3 mb-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Administration</div>
            <nav class="space-y-1">
                <?php if (has_role('super_admin')): ?>
                <a href="<?= admin_url('users/') ?>" class="sidebar-link <?= ($activeMenu === 'users') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">manage_accounts</span>
                    <span>Admin Users</span>
                </a>
                <?php endif; ?>
                <a href="<?= admin_url('profile/') ?>" class="sidebar-link <?= ($activeMenu === 'profile') ? 'active' : '' ?>">
                    <span class="material-symbols-outlined text-[20px]">account_circle</span>
                    <span>My Profile</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Bottom Actions / Quick info -->
    <div class="pt-6 mt-6 border-t border-slate-700/50 space-y-2">
        <?php if (has_role('super_admin')): ?>
        <a href="<?= admin_url('database/setup.php') ?>" class="sidebar-link text-xs opacity-75 hover:opacity-100">
            <span class="material-symbols-outlined text-[18px]">database</span>
            <span>Database Setup</span>
        </a>
        <?php endif; ?>
        <a href="<?= admin_url('login/logout.php') ?>" class="sidebar-link text-red-300 hover:text-red-100 hover:bg-red-900/30">
            <span class="material-symbols-outlined text-[18px]">logout</span>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
