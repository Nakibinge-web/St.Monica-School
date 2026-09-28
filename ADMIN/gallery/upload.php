<?php
/**
 * St. Monica Junior School CMS - Upload Gallery Photos (bulk)
 *
 * The page queues any number of photos and sends each one in its own request (POST, JSON reply),
 * so large batches are not limited by PHP's post_max_size / max_file_uploads and every photo
 * reports its own success or error.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('gallery');

$pageTitle = 'Upload Gallery Photos';
$activeMenu = 'gallery';

$categories = ['Campus Life', 'Academics', 'Co-curricular Activities', 'Special Events', 'Administration'];

// Single-photo upload, called once per queued photo by the page script below
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token()) {
        json_response(false, 'Your session security token has expired. Please refresh the page and try again.', null, 403);
    }
    if (empty($_FILES['image']['name'])) {
        json_response(false, 'No photo was received. It may be larger than the server allows.', null, 422);
    }

    $category     = in_array($_POST['category'] ?? '', $categories, true) ? $_POST['category'] : 'Campus Life';
    $status       = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'published';
    $title        = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 255);   // optional caption
    $description  = trim((string)($_POST['description'] ?? ''));              // optional
    $displayOrder = max(0, (int)($_POST['display_order'] ?? 0));

    $uploadError = null;
    $path = handle_file_upload($_FILES['image'], 'gallery', $uploadError);
    if (!$path) {
        json_response(false, $uploadError ?: 'Upload failed.', null, 422);
    }

    try {
        $newId = Database::insert('gallery', [
            'title'         => $title,
            'description'   => $description,
            'file_path'     => $path,
            'file_type'     => 'image',
            'category'      => $category,
            'display_order' => $displayOrder,
            'status'        => $status
        ]);
    } catch (Exception $e) {
        @unlink(dirname(CMS_ROOT) . '/' . $path);
        json_response(false, 'Could not save the photo: ' . $e->getMessage(), null, 500);
    }

    log_activity('Uploaded Gallery Photo', ($title !== '' ? $title : basename($path)) . " ({$category}, {$status})", 'gallery', $newId);
    json_response(true, 'Photo uploaded.', ['id' => $newId, 'file_path' => $path]);
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
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Upload Gallery Photos</h1>
    </div>
    <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
        <span>Back to Gallery</span>
    </a>
</div>

<div class="cms-card p-6 sm:p-8 max-w-4xl mx-auto mb-8 space-y-6" id="galleryUploader"
     data-endpoint="<?= e(admin_url('gallery/upload.php')) ?>"
     data-csrf="<?= e(csrf_token()) ?>"
     data-gallery-url="<?= e(admin_url('gallery/')) ?>">

    <!-- Drop zone -->
    <label for="galleryFiles" class="image-upload-dropzone block" id="galleryDropzone">
        <span class="material-symbols-outlined text-4xl text-slate-400 mb-2 block">add_photo_alternate</span>
        <p class="text-sm font-semibold text-slate-700">Click to choose photos or drag and drop them here</p>
        <p class="text-xs text-slate-400 mt-1">Select as many as you like. JPG, PNG, WebP or GIF, max 8MB each. Photos are automatically optimized.</p>
        <input type="file" id="galleryFiles" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
    </label>

    <!-- Settings for the whole batch -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2" for="batchCategory">Category *</label>
            <select id="batchCategory" class="cms-select">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2" for="batchStatus">Status</label>
            <select id="batchStatus" class="cms-select">
                <option value="published">Published</option>
                <option value="draft">Draft (hidden)</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2" for="batchOrder">Starting Display Order</label>
            <input type="number" id="batchOrder" value="0" min="0" class="cms-input">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2" for="batchCaption">
                Caption for all photos <span class="normal-case tracking-normal font-normal text-slate-400">(Optional)</span>
            </label>
            <input type="text" id="batchCaption" maxlength="255" placeholder="e.g. Sports Day 2026" class="cms-input">
            <p class="text-xs text-slate-400 mt-1">Used for any photo you don't give its own caption below. Leave empty for no caption.</p>
        </div>
    </div>

    <!-- Queue -->
    <div id="queueWrap" class="hidden">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-900 brand-font" id="queueHeading">Selected Photos</h2>
            <button type="button" id="queueClear" class="text-xs text-slate-500 hover:text-red-600 hover:underline">Clear all</button>
        </div>
        <div id="queueList" class="space-y-3"></div>
    </div>

    <div id="uploadSummary" class="hidden p-4 rounded-lg border text-sm"></div>

    <div class="flex flex-wrap items-center justify-end gap-3 pt-6 border-t border-slate-100">
        <a href="<?= admin_url('gallery/') ?>" class="cms-btn cms-btn-outline" id="doneLink">Cancel</a>
        <button type="button" class="cms-btn cms-btn-accent" id="uploadBtn" disabled>
            <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
            <span id="uploadLabel">Upload to Gallery</span>
        </button>
    </div>
</div>

<script>
(() => {
    const root = document.getElementById('galleryUploader');
    const endpoint = root.dataset.endpoint;
    const csrf = root.dataset.csrf;
    const galleryUrl = root.dataset.galleryUrl;
    const input = document.getElementById('galleryFiles');
    const dropzone = document.getElementById('galleryDropzone');
    const batchCategory = document.getElementById('batchCategory');
    const batchStatus = document.getElementById('batchStatus');
    const batchOrder = document.getElementById('batchOrder');
    const batchCaption = document.getElementById('batchCaption');
    const queueWrap = document.getElementById('queueWrap');
    const queueList = document.getElementById('queueList');
    const queueHeading = document.getElementById('queueHeading');
    const clearBtn = document.getElementById('queueClear');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadLabel = document.getElementById('uploadLabel');
    const summary = document.getElementById('uploadSummary');
    const doneLink = document.getElementById('doneLink');

    const MAX_BYTES = 8 * 1024 * 1024;
    const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    const STATUS = {
        ready:     ['Ready', 'bg-slate-100 text-slate-600'],
        invalid:   ['Skipped', 'bg-amber-100 text-amber-800'],
        uploading: ['Uploading…', 'bg-blue-100 text-blue-700'],
        done:      ['Uploaded', 'bg-emerald-100 text-emerald-800'],
        failed:    ['Failed', 'bg-red-100 text-red-700'],
    };

    let entries = [];
    let busy = false;

    const formatSize = (b) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB';

    function addFiles(fileList) {
        if (busy) return;
        summary.classList.add('hidden');
        Array.from(fileList).forEach((file) => {
            const dup = entries.some((en) => en.file.name === file.name && en.file.size === file.size
                && en.file.lastModified === file.lastModified && en.status !== 'done');
            if (dup) return;
            let problem = '';
            if (!ALLOWED.includes(file.type)) problem = 'Not a supported image type (JPG, PNG, WebP or GIF).';
            else if (file.size > MAX_BYTES) problem = 'Larger than 8MB.';
            entries.push(buildRow(file, problem));
        });
        input.value = '';
        refresh();
    }

    function buildRow(file, problem) {
        const row = document.createElement('div');
        row.className = 'flex flex-col sm:flex-row gap-3 p-3 rounded-lg border border-slate-200 bg-white';

        const thumb = document.createElement('div');
        thumb.className = 'w-full sm:w-28 h-40 sm:h-24 shrink-0 rounded-md overflow-hidden bg-slate-100 flex items-center justify-center';
        if (!problem) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = '';
            img.className = 'w-full h-full object-cover';
            img.onload = () => URL.revokeObjectURL(img.src);
            thumb.appendChild(img);
        } else {
            thumb.innerHTML = '<span class="material-symbols-outlined text-slate-300 text-[32px]">broken_image</span>';
        }

        const body = document.createElement('div');
        body.className = 'flex-1 min-w-0 space-y-2';

        const meta = document.createElement('div');
        meta.className = 'flex items-center justify-between gap-2';
        const name = document.createElement('span');
        name.className = 'text-xs text-slate-500 truncate';
        name.textContent = `${file.name} · ${formatSize(file.size)}`;
        const badge = document.createElement('span');
        meta.append(name, badge);

        const fields = document.createElement('div');
        fields.className = 'grid grid-cols-1 sm:grid-cols-2 gap-2';
        const caption = document.createElement('input');
        caption.type = 'text';
        caption.maxLength = 255;
        caption.className = 'cms-input text-sm';
        caption.placeholder = 'Caption (optional)';
        caption.setAttribute('aria-label', `Caption for ${file.name}`);
        const description = document.createElement('input');
        description.type = 'text';
        description.className = 'cms-input text-sm';
        description.placeholder = 'Description (optional)';
        description.setAttribute('aria-label', `Description for ${file.name}`);
        fields.append(caption, description);

        const progress = document.createElement('div');
        progress.className = 'h-1.5 rounded-full bg-slate-100 overflow-hidden hidden';
        const bar = document.createElement('div');
        bar.className = 'h-full bg-[#1e2a4a] transition-all duration-200';
        bar.style.width = '0%';
        progress.appendChild(bar);

        const message = document.createElement('p');
        message.className = 'text-xs font-semibold hidden';

        body.append(meta, fields, progress, message);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'self-start p-1.5 rounded text-slate-400 hover:text-red-600 hover:bg-red-50';
        remove.title = 'Remove from list';
        remove.setAttribute('aria-label', `Remove ${file.name}`);
        remove.innerHTML = '<span class="material-symbols-outlined text-[18px]">close</span>';

        row.append(thumb, body, remove);
        queueList.appendChild(row);

        const entry = { file, row, caption, description, badge, progress, bar, message, remove, status: 'ready' };
        remove.addEventListener('click', () => {
            if (busy) return;
            row.remove();
            entries = entries.filter((en) => en !== entry);
            refresh();
        });
        setStatus(entry, problem ? 'invalid' : 'ready', problem);
        return entry;
    }

    function setStatus(entry, status, text = '') {
        entry.status = status;
        const [label, cls] = STATUS[status];
        entry.badge.textContent = label;
        entry.badge.className = `text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0 ${cls}`;
        entry.message.textContent = text;
        entry.message.className = `text-xs font-semibold ${status === 'invalid' ? 'text-amber-700' : 'text-red-600'} ${text ? '' : 'hidden'}`;
        const locked = status === 'done' || status === 'uploading';
        entry.caption.disabled = entry.description.disabled = locked;
        entry.remove.classList.toggle('invisible', status === 'uploading');
        entry.progress.classList.toggle('hidden', status !== 'uploading');
    }

    function pending() {
        return entries.filter((en) => en.status === 'ready' || en.status === 'failed');
    }

    function refresh() {
        const todo = pending();
        queueWrap.classList.toggle('hidden', entries.length === 0);
        queueHeading.textContent = `Selected Photos (${entries.length})`;
        uploadBtn.disabled = busy || todo.length === 0;
        const retrying = todo.length > 0 && todo.every((en) => en.status === 'failed');
        uploadLabel.textContent = todo.length === 0
            ? 'Upload to Gallery'
            : `${retrying ? 'Retry' : 'Upload'} ${todo.length} Photo${todo.length === 1 ? '' : 's'}`;
        clearBtn.classList.toggle('hidden', busy);
        [batchCategory, batchStatus, batchOrder, batchCaption].forEach((el) => { el.disabled = busy; });
    }

    function uploadOne(entry, order) {
        return new Promise((resolve) => {
            const data = new FormData();
            data.append('image', entry.file);
            data.append('title', entry.caption.value.trim() || batchCaption.value.trim());
            data.append('description', entry.description.value.trim());
            data.append('category', batchCategory.value);
            data.append('status', batchStatus.value);
            data.append('display_order', String(order));
            data.append('csrf_token', csrf);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', endpoint);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) entry.bar.style.width = Math.round((e.loaded / e.total) * 100) + '%';
            });
            xhr.onload = () => {
                let json = null;
                try { json = JSON.parse(xhr.responseText); } catch (err) { /* handled below */ }
                if (json && json.success) {
                    setStatus(entry, 'done');
                    resolve(true);
                } else {
                    setStatus(entry, 'failed', (json && json.message) || `Server error (${xhr.status}).`);
                    resolve(false);
                }
            };
            xhr.onerror = () => {
                setStatus(entry, 'failed', 'Network error. Check your connection and click Retry.');
                resolve(false);
            };
            setStatus(entry, 'uploading');
            entry.bar.style.width = '0%';
            xhr.send(data);
        });
    }

    async function uploadAll() {
        const queue = pending();
        if (!queue.length || busy) return;
        busy = true;
        summary.classList.add('hidden');
        refresh();

        let order = Math.max(0, parseInt(batchOrder.value, 10) || 0);
        let ok = 0;
        let failed = 0;
        for (const entry of queue) {
            if (await uploadOne(entry, order)) { ok++; order++; } else { failed++; }
        }

        busy = false;
        refresh();

        const category = batchCategory.value;
        summary.className = `p-4 rounded-lg border text-sm ${failed
            ? 'bg-amber-50 border-amber-200 text-amber-900'
            : 'bg-emerald-50 border-emerald-200 text-emerald-900'}`;
        summary.textContent = failed
            ? `${ok} photo${ok === 1 ? '' : 's'} uploaded, ${failed} failed. Remove the failed ones or click Retry.`
            : `${ok} photo${ok === 1 ? '' : 's'} uploaded to "${category}" successfully.`;
        summary.classList.remove('hidden');

        if (ok > 0) {
            doneLink.textContent = 'View Gallery';
            doneLink.href = `${galleryUrl}?category=${encodeURIComponent(category)}`;
            doneLink.className = 'cms-btn cms-btn-primary';
        }
    }

    input.addEventListener('change', () => addFiles(input.files));
    uploadBtn.addEventListener('click', uploadAll);
    clearBtn.addEventListener('click', () => {
        if (busy) return;
        entries = [];
        queueList.innerHTML = '';
        summary.classList.add('hidden');
        refresh();
    });

    ['dragenter', 'dragover'].forEach((evt) => dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    }));
    ['dragleave', 'drop'].forEach((evt) => dropzone.addEventListener(evt, (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
    }));
    dropzone.addEventListener('drop', (e) => {
        if (e.dataTransfer && e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
    });
    // A photo dropped just outside the box shouldn't make the browser navigate away
    ['dragover', 'drop'].forEach((evt) => window.addEventListener(evt, (e) => e.preventDefault()));

    window.addEventListener('beforeunload', (e) => {
        if (busy) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
