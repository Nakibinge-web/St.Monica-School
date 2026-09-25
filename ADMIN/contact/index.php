<?php
/**
 * St. Monica Junior School CMS - Contact Information Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('contact');

$pageTitle = 'Manage Contact Information';
$activeMenu = 'contact';

$contact = Database::fetchOne("SELECT * FROM `contact_information` LIMIT 1");

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Global Contact Information</h1>
        <p class="text-sm text-slate-500 mt-1">Changes made here automatically propagate across all public website pages and footers.</p>
    </div>
    <a href="<?= public_url('contact.html') ?>" target="_blank" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">visibility</span>
        <span>View Contact Page</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <div class="lg:col-span-8">
        <div class="cms-card p-6 sm:p-8">
            <h2 class="text-lg font-bold text-slate-900 brand-font mb-6 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">contact_phone</span>
                School Contact & Location Details
            </h2>

            <form method="POST" action="<?= admin_url('contact/update.php') ?>" class="space-y-6">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <!-- School Official Name -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">School Name *</label>
                        <input type="text" name="school_name" required value="<?= e($contact['school_name'] ?? 'St. Monica Junior School Kasanje') ?>" class="cms-input">
                    </div>

                    <!-- Phone Numbers -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Primary Phone *</label>
                        <input type="text" name="phone" required value="<?= e($contact['phone'] ?? '+256 752 406176') ?>" class="cms-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Alternative Phone</label>
                        <input type="text" name="alternative_phone" value="<?= e($contact['alternative_phone'] ?? '+256 762636213') ?>" class="cms-input">
                    </div>

                    <!-- Emails -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">General Email *</label>
                        <input type="email" name="email" required value="<?= e($contact['email'] ?? 'stmonicajuniorschool2012@gmail.com') ?>" class="cms-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Admissions Email</label>
                        <input type="email" name="admissions_email" value="<?= e($contact['admissions_email'] ?? 'info@stmonicakasanje.ac.ug') ?>" class="cms-input">
                    </div>

                    <!-- WhatsApp -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">WhatsApp Number</label>
                        <input type="text" name="whatsapp" value="<?= e($contact['whatsapp'] ?? '+256752406176') ?>" placeholder="+256752406176" class="cms-input">
                    </div>

                    <!-- Opening Hours -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">School Office Hours</label>
                        <input type="text" name="opening_hours" value="<?= e($contact['opening_hours'] ?? 'Mon - Fri: 7:00 AM - 5:00 PM | Saturday: 8:00 AM - 1:00 PM') ?>" class="cms-input">
                    </div>

                    <!-- Physical Address -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Full Address Description *</label>
                        <input type="text" name="address" required value="<?= e($contact['address'] ?? 'Kasanje Village, Wakiso District, Uganda') ?>" class="cms-input">
                    </div>

                    <!-- Village & District -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Village / Location</label>
                        <input type="text" name="village" value="<?= e($contact['village'] ?? 'Kkoba village, Kasanje') ?>" class="cms-input">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">District & Country</label>
                        <input type="text" name="district" value="<?= e($contact['district'] ?? 'Wakiso District, Uganda') ?>" class="cms-input">
                    </div>

                    <!-- Social Media Links -->
                    <div class="sm:col-span-2 pt-4 border-t border-slate-100">
                        <h3 class="text-sm font-bold text-slate-900 brand-font mb-4">Social Media Channels</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Facebook URL</label>
                                <input type="url" name="facebook" value="<?= e($contact['facebook'] ?? '') ?>" placeholder="https://facebook.com/..." class="cms-input text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Instagram URL</label>
                                <input type="url" name="instagram" value="<?= e($contact['instagram'] ?? '') ?>" placeholder="https://instagram.com/..." class="cms-input text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">YouTube URL</label>
                                <input type="url" name="youtube" value="<?= e($contact['youtube'] ?? '') ?>" placeholder="https://youtube.com/..." class="cms-input text-xs">
                            </div>
                        </div>
                    </div>

                    <!-- Google Maps Embed Link -->
                    <div class="sm:col-span-2 pt-4 border-t border-slate-100">
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Google Maps Embed URL</label>
                        <textarea name="map_url" rows="3" placeholder="https://www.google.com/maps/embed?..." class="cms-textarea text-xs"><?= e($contact['map_url'] ?? '') ?></textarea>
                        <p class="text-xs text-slate-400 mt-1">Paste either the full iframe code or the https://www.google.com/maps/embed?... URL.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                    <button type="submit" class="cms-btn cms-btn-accent text-base px-8 py-3">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        <span>Save Contact Information</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Preview Sidebar (4 cols) -->
    <div class="lg:col-span-4 space-y-6">
        <div class="cms-card p-6">
            <h3 class="text-sm font-bold text-slate-900 brand-font uppercase tracking-wider mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">info</span>
                Live Public Reflection
            </h3>
            <p class="text-xs text-slate-500 leading-relaxed mb-4">
                Updating these contact fields instantly updates:
            </p>
            <ul class="text-xs text-slate-700 space-y-2 list-disc list-inside">
                <li>Homepage "Visit Us" cards</li>
                <li>Contact page location & call cards</li>
                <li>All website footers site-wide</li>
                <li>School administration direct contacts</li>
            </ul>
        </div>

        <?php if (!empty($contact['map_url'])): ?>
            <div class="cms-card overflow-hidden">
                <div class="p-3 bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-700">
                    Google Maps Preview
                </div>
                <div class="h-60 w-full">
                    <iframe src="<?= e($contact['map_url']) ?>" class="w-full h-full border-0" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
