<?php
/**
 * St. Monica Junior School CMS - Create News or Event
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('news-events');

$pageTitle = 'Create News or Event';
$activeMenu = 'news-events';

$defaultType = $_GET['type'] ?? 'news';
if (!in_array($defaultType, ['news', 'event', 'sports'])) {
    $defaultType = 'news';
}

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
        set_flash('danger', 'Title and full content are required.');
    } else {
        if (empty($slug)) {
            $slug = slugify($title);
        } else {
            $slug = slugify($slug);
        }

        // Ensure unique slug
        $existing = Database::fetchOne("SELECT `id` FROM `news_events` WHERE `slug` = :slug", ['slug' => $slug]);
        if ($existing) {
            $slug .= '-' . time();
        }

        $imagePath = 'assets/imgz/3 graduants.webp'; // default fallback
        $uploadError = null;

        if (!empty($_FILES['featured_image']['name'])) {
            $uploaded = handle_file_upload($_FILES['featured_image'], 'news', $uploadError);
            if ($uploaded) {
                $imagePath = $uploaded;
            } else {
                set_flash('danger', 'Image upload failed: ' . $uploadError);
                redirect(admin_url('news-events/create.php?type=' . $type));
            }
        }

        try {
            // Sanitize content
            $cleanContent = sanitize_html($content);

            $scheduledAt = !empty($_POST['scheduled_publish_at']) ? $_POST['scheduled_publish_at'] : null;
            $publishedAt = ($status === 'published')
                ? ($scheduledAt ? date('Y-m-d H:i:s', strtotime($scheduledAt)) : date('Y-m-d H:i:s'))
                : null;
            $expiresAt = !empty($_POST['expires_at']) ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) : null;

            $newId = Database::insert('news_events', [
                'title'          => $title,
                'slug'           => $slug,
                'type'           => $type,
                'excerpt'        => $excerpt,
                'content'        => $cleanContent,
                'featured_image' => $imagePath,
                'event_date'     => $eventDate,
                'event_location' => $eventLocation,
                'status'         => $status,
                'published_at'   => $publishedAt,
                'expires_at'     => $expiresAt
            ]);

            log_activity('Created News/Event', "Title: {$title} (Status: {$status}, Type: {$type})", 'news_events', $newId);
            set_flash('success', ucfirst($type) . ' post saved successfully (' . ucfirst($status) . ').');
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
            <span class="text-slate-800 font-semibold">New Post</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Create News or Event</h1>
    </div>
    <a href="<?= admin_url('news-events/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to List</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-4xl mx-auto mb-8">
    <form method="POST" action="<?= admin_url('news-events/create.php') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">
            <!-- Title -->
            <div class="sm:col-span-8">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Title *</label>
                <input type="text" name="title" required data-slug-source placeholder="e.g. Annual Speech Day & Prize Giving" class="cms-input">
            </div>

            <!-- Type -->
            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Category Type *</label>
                <select name="type" class="cms-select">
                    <option value="news" <?= $defaultType === 'news' ? 'selected' : '' ?>>News Announcement</option>
                    <option value="event" <?= $defaultType === 'event' ? 'selected' : '' ?>>School Event</option>
                    <option value="sports" <?= $defaultType === 'sports' ? 'selected' : '' ?>>Sports & Athletics</option>
                </select>
            </div>

            <!-- Slug -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">SEO URL Slug</label>
                <div class="flex items-center">
                    <span class="inline-flex items-center px-3 py-2 text-xs bg-slate-100 border border-r-0 border-slate-300 rounded-l-md text-slate-500">
                        /news/
                    </span>
                    <input type="text" name="slug" data-slug-target placeholder="annual-speech-day" class="cms-input rounded-l-none">
                </div>
                <p class="text-xs text-slate-400 mt-1">Generated automatically from the title, or customize as desired.</p>
            </div>

            <!-- Excerpt -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Short Summary / Excerpt *</label>
                <input type="text" name="excerpt" placeholder="A 1-2 sentence preview for cards and homepage..." class="cms-input">
            </div>

            <!-- Full Content with Rich-Text Editor -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Full Story / Article Content *</label>
                <textarea name="content" rows="8" data-rich-editor required placeholder="Write the complete article or event details..." class="cms-textarea leading-relaxed text-sm"></textarea>
            </div>

            <!-- Event Date & Location -->
            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Event Date (if applicable)</label>
                <input type="date" name="event_date" class="cms-input">
            </div>

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Event Venue / Location</label>
                <input type="text" name="event_location" placeholder="e.g. St. Monica Main Quadrangle" class="cms-input">
            </div>

            <!-- Featured Image -->
            <div class="sm:col-span-12">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Featured Image</label>
                <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" data-preview-target="newsPreviewImg" class="cms-input">
                <p class="text-xs text-slate-400 mt-1">Landscape photo (JPG, PNG, or WEBP under 8MB). Automatically compressed and optimized.</p>

                <div id="newsPreviewImgContainer" class="hidden mt-4">
                    <p class="text-xs font-semibold text-slate-600 mb-1">Image Preview:</p>
                    <img id="newsPreviewImg" src="#" alt="Preview" class="h-36 w-auto object-cover rounded-lg border border-slate-300 shadow-sm">
                </div>
            </div>

            <!-- Publication Status & Scheduling -->
            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Workflow Status</label>
                <select id="statusSelect" name="status" class="cms-select font-semibold">
                    <option value="published" selected>Published (Live)</option>
                    <option value="draft">Draft (Hidden from public)</option>
                </select>
            </div>

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Scheduled Publication Date / Time</label>
                <input type="datetime-local" name="scheduled_publish_at" class="cms-input">
                <p class="text-[11px] text-slate-400 mt-1">Optional: Leave blank to publish immediately.</p>
            </div>

            <div class="sm:col-span-6">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Auto-Expire Date / Time</label>
                <input type="datetime-local" name="expires_at" class="cms-input">
                <p class="text-[11px] text-slate-400 mt-1">Optional: post automatically stops appearing on the public site after this time (it stays here, editable).</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('news-events/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" onclick="document.getElementById('statusSelect').value='draft';" class="cms-btn cms-btn-outline">
                <span class="material-symbols-outlined text-[18px]">draft</span>
                <span>Save Draft</span>
            </button>
            <button type="submit" onclick="document.getElementById('statusSelect').value='published';" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">publish</span>
                <span>Publish Post</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
