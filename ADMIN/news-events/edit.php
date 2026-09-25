<?php
/**
 * St. Monica Junior School CMS - Edit News or Event
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('news-events');

$id = (int)($_GET['id'] ?? 0);
$item = Database::fetchOne("SELECT * FROM `news_events` WHERE `id` = :id", ['id' => $id]);

if (!$item) {
    set_flash('danger', 'Post not found.');
    redirect(admin_url('news-events/'));
}

$pageTitle = 'Edit: ' . $item['title'];
$activeMenu = 'news-events';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $type = in_array($_POST['type'] ?? '', ['news', 'event', 'sports']) ? $_POST['type'] : 'news';
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $eventDate = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
    $eventLocation = trim($_POST['event_location'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

    if (empty($title) || empty($content)) {
        set_flash('danger', 'Title and content are required.');
    } else {
        if (empty($slug)) {
            $slug = slugify($title);
        } else {
            $slug = slugify($slug);
        }

        // Ensure unique slug except for this id
        $existing = Database::fetchOne("SELECT `id` FROM `news_events` WHERE `slug` = :slug AND `id` != :id", [
            'slug' => $slug,
            'id'   => $id
        ]);
        if ($existing) {
            $slug .= '-' . time();
        }

        $imagePath = null;
        $uploadError = null;

        if (!empty($_FILES['featured_image']['name'])) {
            $uploaded = handle_file_upload($_FILES['featured_image'], 'news', $uploadError);
            if ($uploaded) {
                $imagePath = $uploaded;
            } else {
                set_flash('danger', 'Image upload failed: ' . $uploadError);
                redirect(admin_url('news-events/edit.php?id=' . $id));
            }
        }

        try {
            $cleanContent = sanitize_html($content);
            $scheduledAt = !empty($_POST['scheduled_publish_at']) ? $_POST['scheduled_publish_at'] : null;
            $expiresAt = !empty($_POST['expires_at']) ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) : null;

            $updateData = [
                'title'          => $title,
                'slug'           => $slug,
                'type'           => $type,
                'excerpt'        => $excerpt,
                'content'        => $cleanContent,
                'event_date'     => $eventDate,
                'event_location' => $eventLocation,
                'status'         => $status,
                'expires_at'     => $expiresAt
            ];

            if ($imagePath) {
                $updateData['featured_image'] = $imagePath;
            }

            if ($status === 'published') {
                if ($scheduledAt) {
                    $updateData['published_at'] = date('Y-m-d H:i:s', strtotime($scheduledAt));
                } elseif (empty($item['published_at'])) {
                    $updateData['published_at'] = date('Y-m-d H:i:s');
                }
            } else {
                // If set back to draft
                $updateData['published_at'] = null;
            }

            Database::update('news_events', $updateData, 'id = :id', ['id' => $id]);

            log_activity('Updated News/Event', "Title: {$title} (Status: {$status}, ID: {$id})", 'news_events', $id);
            set_flash('success', 'Post updated successfully.');
            redirect(admin_url('news-events/'));
        } catch (Exception $e) {
            set_flash('danger', 'Database error: ' . $e->getMessage());
        }
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('news-events/') ?>" class="hover:text-slate-800">News & Events</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Edit Post</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Edit: <?= e($item['title']) ?></h1>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('preview/index.php?type=news&id=' . $item['id']) ?>" target="_blank" class="cms-btn cms-btn-outline text-xs bg-white text-[#1e2a4a] border-[#1e2a4a]/30 hover:bg-slate-50">
            <span class="material-symbols-outlined text-[16px] text-red-600">visibility</span>
            <span>Preview Article</span>
        </a>
        <a href="<?= admin_url('news-events/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">arrow_back</span>
            <span>Back to List</span>
        </a>
    </div>
</div>

<div class="cms-card p-6 sm:p-8 max-w-4xl mx-auto mb-8">
    <form method="POST" action="<?= admin_url('news-events/edit.php?id=' . $id) ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">
            <!-- Title -->
            <div class="sm:col-span-8">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Title *</label>
                <input type="text" name="title" required value="<?= e($item['title']) ?>" class="cms-input">
            </div>

            <!-- Type -->
            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Category Type *</label>
                <select name="type" class="cms-select">
                    <option value="news" <?= $item['type'] === 'news' ? 'selected' : '' ?>>News Announcement</option>
                    <option value="event" <?= $item['type'] === 'event' ? 'selected' : '' ?>>School Event</option>
                    <option value="sports" <?= $item['type'] === 'sports' ? 'selected' : '' ?>>Sports & Athletics</option>
                </select>
            </div>

            <!-- Slug -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">SEO URL Slug</label>
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-2 text-xs bg-slate-100 border border-r-0 border-slate-300 rounded-l-md text-slate-500">
                        /news/
                    </span>
                    <input type="text" name="slug" value="<?= e($item['slug']) ?>" class="cms-input rounded-l-none">
                </div>
            </div>

            <!-- Excerpt -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Short Summary / Excerpt</label>
                <input type="text" name="excerpt" value="<?= e($item['excerpt']) ?>" class="cms-input">
            </div>

            <!-- Full Content with Rich-Text Editor -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Full Story / Article Content *</label>
                <textarea name="content" rows="8" data-rich-editor required class="cms-textarea leading-relaxed text-sm"><?= e($item['content']) ?></textarea>
            </div>

            <!-- Event Date & Location -->
            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Event Date</label>
                <input type="date" name="event_date" value="<?= e($item['event_date'] ?? '') ?>" class="cms-input">
            </div>

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Event Venue / Location</label>
                <input type="text" name="event_location" value="<?= e($item['event_location'] ?? '') ?>" class="cms-input">
            </div>

            <!-- Featured Image -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Featured Image</label>
                
                <?php if (!empty($item['featured_image'])): ?>
                    <div class="flex items-center gap-4 mb-3">
                        <img src="<?= public_url($item['featured_image']) ?>" alt="Current Image" class="h-20 w-32 object-cover rounded-lg border border-slate-200">
                        <div class="text-xs text-slate-500">
                            <span class="font-semibold text-slate-700 block">Current Featured Image</span>
                            Upload below only if you want to replace it.
                        </div>
                    </div>
                <?php endif; ?>

                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" data-preview-target="newsEditPreviewImg" class="cms-input">
                <p class="text-xs text-slate-400 mt-1">Leave empty to keep existing image.</p>

                <div id="newsEditPreviewImgContainer" class="hidden mt-4">
                    <p class="text-xs font-semibold text-slate-600 mb-1">Replacement Preview:</p>
                    <img id="newsEditPreviewImg" src="#" alt="Preview" class="h-36 w-auto object-cover rounded-lg border border-slate-300 shadow-sm">
                </div>
            </div>

            <!-- Publication Status & Scheduling -->
            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Workflow Status</label>
                <select id="editStatusSelect" name="status" class="cms-select font-semibold">
                    <option value="published" <?= $item['status'] === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $item['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hidden from public)</option>
                </select>
            </div>

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Scheduled Publication Date / Time</label>
                <input type="datetime-local" name="scheduled_publish_at"
                       value="<?= !empty($item['published_at']) ? date('Y-m-d\TH:i', strtotime($item['published_at'])) : '' ?>"
                       class="cms-input">
                <p class="text-[11px] text-slate-400 mt-1">Leave blank to keep current publish time.</p>
            </div>

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">
                    Auto-Expire Date / Time
                    <?php if (!empty($item['expires_at']) && strtotime($item['expires_at']) <= time()): ?>
                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 align-middle">Expired</span>
                    <?php endif; ?>
                </label>
                <input type="datetime-local" name="expires_at"
                       value="<?= !empty($item['expires_at']) ? date('Y-m-d\TH:i', strtotime($item['expires_at'])) : '' ?>"
                       class="cms-input">
                <p class="text-[11px] text-slate-400 mt-1">Optional: the post automatically stops appearing on the public site after this time. Leave blank for no expiry.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('news-events/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" onclick="document.getElementById('editStatusSelect').value='draft';" class="cms-btn cms-btn-outline">
                <span class="material-symbols-outlined text-[18px]">draft</span>
                <span>Save as Draft</span>
            </button>
            <button type="submit" onclick="document.getElementById('editStatusSelect').value='published';" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Save & Publish</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
