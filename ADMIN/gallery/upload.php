<?php
/**
 * St. Monica Junior School CMS - Upload Gallery Media
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_auth();

$pageTitle = 'Upload Gallery Photos';
$activeMenu = 'gallery';

$categories = ['Campus Life', 'Academics', 'Co-curricular Activities', 'Special Events', 'Administration'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $category = in_array($_POST['category'] ?? '', $categories) ? $_POST['category'] : 'Campus Life';
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'published';

    // Handle single or multiple file uploads
    $filesUploaded = 0;
    $errors = [];

    if (!empty($_FILES['images']['name'])) {
        // Normalize $_FILES if single or multiple
        $names = (array)$_FILES['images']['name'];
        $tmpNames = (array)$_FILES['images']['tmp_name'];
        $sizes = (array)$_FILES['images']['size'];
        $errorCodes = (array)$_FILES['images']['error'];

        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            if ($errorCodes[$i] === UPLOAD_ERR_NO_FILE) continue;

            $singleFile = [
                'name'     => $names[$i],
                'tmp_name' => $tmpNames[$i],
                'size'     => $sizes[$i],
                'error'    => $errorCodes[$i]
            ];

            $uploadError = null;
            $uploadedPath = handle_file_upload($singleFile, 'gallery', $uploadError);

            if ($uploadedPath) {
                $itemTitle = !empty($title) ? $title : pathinfo($names[$i], PATHINFO_FILENAME);
                if ($count > 1) {
                    $itemTitle .= ' (' . ($filesUploaded + 1) . ')';
                }

                Database::insert('gallery', [
                    'title'         => $itemTitle,
                    'description'   => $description,
                    'file_path'     => $uploadedPath,
                    'file_type'     => 'image',
                    'category'      => $category,
                    'display_order' => $displayOrder + $filesUploaded,
                    'status'        => $status
                ]);
                $filesUploaded++;
            } else {
                $errors[] = "File '{$names[$i]}': " . $uploadError;
            }
        }
    }

    if ($filesUploaded > 0) {
        log_activity('Uploaded Gallery Media', "{$filesUploaded} photo(s) into category: {$category}");
        set_flash('success', "{$filesUploaded} image(s) uploaded and saved to gallery.");
        redirect(admin_url('gallery/?category=' . urlencode($category)));
    } else {
        $errorMsg = !empty($errors) ? implode('<br>', $errors) : 'Please select at least one valid image to upload.';
        set_flash('danger', $errorMsg);
    }
}

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('gallery/') ?>" class="hover:text-slate-800">Media Gallery</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Upload</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Upload Gallery Media</h1>
    </div>
    <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to Gallery</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-2xl mx-auto mb-8">
    <form method="POST" action="<?= admin_url('gallery/upload.php') ?>" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>

        <div class="space-y-5">
            <!-- Category Selection -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Category *</label>
                <select name="category" required class="cms-select">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Title / Caption -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Default Title / Caption</label>
                <input type="text" name="title" placeholder="e.g. Science Fair Presentation" class="cms-input">
                <p class="text-xs text-slate-400 mt-1">If left blank, the original filename will be used as the title.</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Description / Notes</label>
                <textarea name="description" rows="3" placeholder="Brief caption describing the moment..." class="cms-textarea"></textarea>
            </div>

            <!-- Display Order & Status -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Display Order</label>
                    <input type="number" name="display_order" value="0" min="0" class="cms-input">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Status</label>
                    <select name="status" class="cms-select">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <!-- Files Upload -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Select Photos *</label>
                <div class="image-upload-dropzone" onclick="document.getElementById('galleryFiles').click()">
                    <span class="material-symbols-outlined text-4xl text-slate-400 mb-2">add_photo_alternate</span>
                    <p class="text-sm font-semibold text-slate-700">Click to browse or drop images here</p>
                    <p class="text-xs text-slate-400 mt-1">Supports JPG, PNG, WEBP (under 8MB each). You can select multiple files at once.</p>
                    <input type="file" id="galleryFiles" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="hidden">
                </div>
                <div id="selectedFilesInfo" class="mt-2 text-xs text-slate-600 font-semibold hidden"></div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
            <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline">Cancel</a>
            <button type="submit" class="cms-btn cms-btn-accent">
                <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                <span>Upload to Gallery</span>
            </button>
        </div>
    </form>
</div>

<script>
    const fileInput = document.getElementById('galleryFiles');
    const filesInfo = document.getElementById('selectedFilesInfo');
    if (fileInput && filesInfo) {
        fileInput.addEventListener('change', () => {
            const count = fileInput.files.length;
            if (count > 0) {
                filesInfo.textContent = `${count} file(s) selected for upload.`;
                filesInfo.classList.remove('hidden');
            } else {
                filesInfo.classList.add('hidden');
            }
        });
    }
</script>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
