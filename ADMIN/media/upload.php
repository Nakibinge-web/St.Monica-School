<?php
/**
 * St. Monica Junior School CMS - Upload Media Asset
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('media');

$pageTitle = 'Upload Media Asset';
$activeMenu = 'media';

$categories = ['Campus Life', 'Academics', 'Sports & MDD', 'Special Events', 'Facilities', 'Administration', 'General'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title       = trim($_POST['title'] ?? '');
    $altText     = trim($_POST['alt_text'] ?? '');
    $caption     = trim($_POST['caption'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = trim($_POST['category'] ?? 'General');

    if (empty($_FILES['media_file']['name'])) {
        set_flash('danger', 'Please select a media file to upload.');
    } else {
        $uploadError = null;
        $file = $_FILES['media_file'];

        // Infer title from filename if empty
        if (empty($title)) {
            $title = ucwords(str_replace(['-', '_', '.'], ' ', pathinfo($file['name'], PATHINFO_FILENAME)));
        }

        // Upload and validate
        $uploadedRelPath = handle_file_upload($file, 'gallery', $uploadError);

        if (!$uploadedRelPath) {
            set_flash('danger', 'File upload failed: ' . $uploadError);
        } else {
            $fullPath = dirname(CMS_ROOT) . '/' . $uploadedRelPath;
            $fileSize = (int)filesize($fullPath);
            $dimensions = null;

            $imageInfo = @getimagesize($fullPath);
            if ($imageInfo) {
                $dimensions = "{$imageInfo[0]}x{$imageInfo[1]}";
                // Optimize
                optimize_image($fullPath, 1920, 82);
            }

            try {
                $newId = Database::insert('media_library', [
                    'title'       => $title,
                    'alt_text'    => $altText,
                    'caption'     => $caption,
                    'description' => $description,
                    'file_path'   => $uploadedRelPath,
                    'file_type'   => 'image',
                    'file_size'   => $fileSize,
                    'dimensions'  => $dimensions,
                    'category'    => $category,
                    'status'      => 'active'
                ]);

                log_activity('Uploaded Media Asset', "Title: {$title} (File: {$uploadedRelPath})", 'media', $newId);
                set_flash('success', "Media asset '{$title}' uploaded and optimized successfully.");
                redirect(admin_url('media/'));
            } catch (Exception $e) {
                set_flash('danger', 'Database error saving media asset: ' . $e->getMessage());
            }
        }
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('media/') ?>" class="hover:text-slate-800">Media Library</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Upload Asset</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Upload Media Asset</h1>
    </div>
    <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to Media Library</span>
    </a>
</div>

<div class="cms-card max-w-3xl p-6 sm:p-8">
    <form method="POST" action="<?= admin_url('media/upload.php') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="title">
                Media Title <span class="text-red-600">*</span>
            </label>
            <input type="text" id="title" name="title" placeholder="e.g. Science Laboratory Experiment Session" 
                   class="cms-input" required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="category">
                    Media Category <span class="text-red-600">*</span>
                </label>
                <select id="category" name="category" class="cms-select">
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c ?>"><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="altText">
                    Image Alt Text <span class="text-emerald-700 font-semibold">(SEO & Accessibility)</span>
                </label>
                <input type="text" id="altText" name="alt_text" placeholder="e.g. Pupils conducting chemistry experiment in laboratory" 
                       class="cms-input">
                <span class="text-[11px] text-slate-400 mt-1 block">Describe the image for screen readers and search engines.</span>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="caption">
                Caption <span class="text-slate-400 font-normal">(Optional subtitle / display caption)</span>
            </label>
            <input type="text" id="caption" name="caption" placeholder="Short visible caption..." 
                   class="cms-input">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="description">
                Description / Context Notes <span class="text-slate-400 font-normal">(Optional)</span>
            </label>
            <textarea id="description" name="description" rows="3" placeholder="Context or usage details..." 
                      class="cms-textarea"></textarea>
        </div>

        <!-- File Upload Drag/Drop Zone -->
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                Select Media File <span class="text-red-600">*</span>
            </label>
            <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-[#1e2a4a] transition bg-slate-50">
                <span class="material-symbols-outlined text-4xl text-slate-400 mb-2 block">cloud_upload</span>
                <p class="text-sm font-semibold text-slate-700">Choose an image from your computer</p>
                <p class="text-xs text-slate-400 mt-1">Supported formats: JPG, PNG, WebP, GIF (Max 8MB). Images will be automatically optimized.</p>
                <div class="mt-4">
                    <input type="file" name="media_file" accept="image/*" data-preview-target="mediaPreview" 
                           class="inline-block text-xs text-slate-600" required>
                </div>
            </div>

            <!-- Live Preview -->
            <div id="mediaPreviewContainer" class="hidden mt-4 p-3 bg-white border border-slate-200 rounded-lg max-w-sm">
                <p class="text-xs font-bold text-slate-700 mb-2">Upload Preview:</p>
                <img id="mediaPreview" src="" alt="Preview" class="w-full h-44 object-cover rounded">
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
            <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-primary">
                <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                <span>Upload & Optimize</span>
            </button>
        </div>
    </form>
</div>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
