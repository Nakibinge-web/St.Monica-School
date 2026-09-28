<?php
/**
 * St. Monica Junior School CMS - Upload Media Assets (bulk)
 * Each selected image is sent in its own request to media/picker.php, so large batches
 * are not limited by PHP's post_max_size / max_file_uploads and every file reports its own result.
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('media');

$pageTitle = 'Upload Media Assets';
$activeMenu = 'media';

$categories = media_categories();

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="<?= admin_url('media/') ?>" class="hover:text-slate-800">Media Library</a>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Upload Assets</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Upload Media Assets</h1>
    </div>
    <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline text-xs">
        <span class="material-symbols-outlined text-[16px]">arrow_back</span>
        <span>Back to Media Library</span>
    </a>
</div>

<div class="cms-card max-w-4xl p-6 sm:p-8 space-y-6" id="bulkUploader"
     data-endpoint="<?= e(admin_url('media/picker.php')) ?>"
     data-csrf="<?= e(csrf_token()) ?>">

    <!-- Drop Zone -->
    <label for="bulkFiles" class="image-upload-dropzone block" id="bulkDropzone">
        <span class="material-symbols-outlined text-4xl text-slate-400 mb-2 block">add_photo_alternate</span>
        <p class="text-sm font-semibold text-slate-700">Click to choose images or drag and drop them here</p>
        <p class="text-xs text-slate-400 mt-1">Select as many as you need. JPG, PNG, WebP or GIF, max 8MB each. Images are automatically optimized.</p>
        <input type="file" id="bulkFiles" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only">
    </label>

    <!-- Shared Settings -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="bulkCategory">
                Category for these images <span class="text-red-600">*</span>
            </label>
            <select id="bulkCategory" class="cms-select">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= e($c) ?>" <?= $c === 'General' ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="text-xs text-slate-500 sm:pt-6 leading-relaxed">
            Titles are filled in from the file names. Add <span class="font-semibold text-emerald-700">alt text</span>
            to describe each image for screen readers and search engines. Captions can be added later from
            each image's Edit page.
        </div>
    </div>

    <!-- Selected Files -->
    <div id="bulkListWrap" class="hidden">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-slate-900 brand-font" id="bulkListHeading">Selected Images</h2>
            <button type="button" id="bulkClear" class="text-xs text-slate-500 hover:text-red-600 hover:underline">Clear all</button>
        </div>
        <div id="bulkList" class="space-y-3"></div>
    </div>

    <!-- Result Summary -->
    <div id="bulkSummary" class="hidden p-4 rounded-lg border text-sm"></div>

    <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-end gap-3">
        <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline" id="bulkDoneLink">Cancel</a>
        <button type="button" class="cms-btn cms-btn-primary" id="bulkUploadBtn" disabled>
            <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
            <span id="bulkUploadLabel">Upload Images</span>
        </button>
    </div>
</div>

<script>
(() => {
    const root = document.getElementById('bulkUploader');
    const endpoint = root.dataset.endpoint;
    const csrf = root.dataset.csrf;
    const input = document.getElementById('bulkFiles');
    const dropzone = document.getElementById('bulkDropzone');
    const category = document.getElementById('bulkCategory');
    const listWrap = document.getElementById('bulkListWrap');
    const list = document.getElementById('bulkList');
    const heading = document.getElementById('bulkListHeading');
    const clearBtn = document.getElementById('bulkClear');
    const uploadBtn = document.getElementById('bulkUploadBtn');
    const uploadLabel = document.getElementById('bulkUploadLabel');
    const summary = document.getElementById('bulkSummary');
    const doneLink = document.getElementById('bulkDoneLink');

    const MAX_BYTES = 8 * 1024 * 1024;
    const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    // Each entry: { file, row, titleInput, altInput, status: 'ready'|'invalid'|'uploading'|'done'|'failed' }
    let entries = [];
    let busy = false;

    const formatSize = (bytes) => bytes >= 1048576
        ? (bytes / 1048576).toFixed(1) + ' MB'
        : Math.round(bytes / 1024) + ' KB';

    const titleFromName = (name) => name
        .replace(/\.[^.]+$/, '')
        .replace(/[-_.]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (c) => c.toUpperCase());

    function addFiles(fileList) {
        if (busy) return;
        summary.classList.add('hidden');
        Array.from(fileList).forEach((file) => {
            const duplicate = entries.some((en) => en.file.name === file.name && en.file.size === file.size
                && en.file.lastModified === file.lastModified && en.status !== 'done');
            if (duplicate) return;

            let problem = '';
            if (!ALLOWED.includes(file.type)) problem = 'Not a supported image type';
            else if (file.size > MAX_BYTES) problem = 'Larger than 8MB';

            entries.push(buildRow(file, problem));
        });
        input.value = '';
        refresh();
    }

    function buildRow(file, problem) {
        const row = document.createElement('div');
        row.className = 'flex flex-col sm:flex-row gap-3 p-3 rounded-lg border border-slate-200 bg-white';

        const thumbWrap = document.createElement('div');
        thumbWrap.className = 'w-full sm:w-24 h-32 sm:h-24 shrink-0 rounded-md overflow-hidden bg-slate-100 flex items-center justify-center';
        if (!problem) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.alt = '';
            img.className = 'w-full h-full object-cover';
            img.onload = () => URL.revokeObjectURL(img.src);
            thumbWrap.appendChild(img);
        } else {
            thumbWrap.innerHTML = '<span class="material-symbols-outlined text-slate-300 text-[32px]">broken_image</span>';
        }

        const body = document.createElement('div');
        body.className = 'flex-1 min-w-0 space-y-2';

        const meta = document.createElement('div');
        meta.className = 'flex items-center justify-between gap-2';
        const name = document.createElement('span');
        name.className = 'text-xs text-slate-500 truncate';
        name.textContent = `${file.name} · ${formatSize(file.size)}`;
        const status = document.createElement('span');
        status.className = 'text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0';
        meta.append(name, status);

        const fields = document.createElement('div');
        fields.className = 'grid grid-cols-1 sm:grid-cols-2 gap-2';
        const titleInput = document.createElement('input');
        titleInput.type = 'text';
        titleInput.className = 'cms-input text-sm';
        titleInput.placeholder = 'Title';
        titleInput.value = titleFromName(file.name);
        titleInput.setAttribute('aria-label', `Title for ${file.name}`);
        const altInput = document.createElement('input');
        altInput.type = 'text';
        altInput.className = 'cms-input text-sm';
        altInput.placeholder = 'Alt text (describe the image)';
        altInput.setAttribute('aria-label', `Alt text for ${file.name}`);
        fields.append(titleInput, altInput);

        const progress = document.createElement('div');
        progress.className = 'h-1.5 rounded-full bg-slate-100 overflow-hidden hidden';
        const bar = document.createElement('div');
        bar.className = 'h-full bg-[#1e2a4a] transition-all duration-200';
        bar.style.width = '0%';
        progress.appendChild(bar);

        const message = document.createElement('p');
        message.className = 'text-xs font-semibold text-red-600 hidden';

        body.append(meta, fields, progress, message);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'self-start p-1.5 rounded text-slate-400 hover:text-red-600 hover:bg-red-50';
        removeBtn.title = 'Remove from list';
        removeBtn.setAttribute('aria-label', `Remove ${file.name}`);
        removeBtn.innerHTML = '<span class="material-symbols-outlined text-[18px]">close</span>';

        row.append(thumbWrap, body, removeBtn);
        list.appendChild(row);

        const entry = { file, row, titleInput, altInput, status: problem ? 'invalid' : 'ready',
                        statusEl: status, progress, bar, message, removeBtn };
        removeBtn.addEventListener('click', () => {
            if (busy) return;
            row.remove();
            entries = entries.filter((en) => en !== entry);
            refresh();
        });
        setStatus(entry, entry.status, problem);
        return entry;
    }

    const STATUS_STYLES = {
        ready:     ['Ready', 'bg-slate-100 text-slate-600'],
        invalid:   ['Skipped', 'bg-amber-100 text-amber-800'],
        uploading: ['Uploading…', 'bg-blue-100 text-blue-700'],
        done:      ['Uploaded', 'bg-emerald-100 text-emerald-800'],
        failed:    ['Failed', 'bg-red-100 text-red-700'],
    };

    function setStatus(entry, status, text = '') {
        entry.status = status;
        const [label, cls] = STATUS_STYLES[status];
        entry.statusEl.textContent = label;
        entry.statusEl.className = `text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0 ${cls}`;
        entry.message.textContent = text;
        entry.message.classList.toggle('hidden', !text);
        entry.message.classList.toggle('text-red-600', status !== 'invalid');
        entry.message.classList.toggle('text-amber-700', status === 'invalid');
        const locked = status === 'done' || status === 'uploading';
        entry.titleInput.disabled = locked;
        entry.altInput.disabled = locked;
        entry.progress.classList.toggle('hidden', status !== 'uploading');
    }

    function refresh() {
        const pending = entries.filter((en) => en.status === 'ready' || en.status === 'failed');
        listWrap.classList.toggle('hidden', entries.length === 0);
        heading.textContent = `Selected Images (${entries.length})`;
        uploadBtn.disabled = busy || pending.length === 0;
        const retrying = pending.length > 0 && pending.every((en) => en.status === 'failed');
        uploadLabel.textContent = pending.length === 0
            ? 'Upload Images'
            : `${retrying ? 'Retry' : 'Upload'} ${pending.length} Image${pending.length === 1 ? '' : 's'}`;
        clearBtn.classList.toggle('hidden', busy);
    }

    // One request per file, with upload progress
    function uploadOne(entry) {
        return new Promise((resolve) => {
            const data = new FormData();
            data.append('media_file', entry.file);
            data.append('title', entry.titleInput.value.trim());
            data.append('alt_text', entry.altInput.value.trim());
            data.append('category', category.value);
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
                setStatus(entry, 'failed', 'Network error. Check your connection and retry.');
                resolve(false);
            };
            setStatus(entry, 'uploading');
            entry.bar.style.width = '0%';
            xhr.send(data);
        });
    }

    async function uploadAll() {
        const queue = entries.filter((en) => en.status === 'ready' || en.status === 'failed');
        if (!queue.length || busy) return;

        busy = true;
        category.disabled = true;
        summary.classList.add('hidden');
        refresh();

        let ok = 0;
        let failed = 0;
        for (const entry of queue) {
            if (await uploadOne(entry)) ok++; else failed++;
        }

        busy = false;
        category.disabled = false;
        refresh();

        summary.className = `p-4 rounded-lg border text-sm ${failed
            ? 'bg-amber-50 border-amber-200 text-amber-900'
            : 'bg-emerald-50 border-emerald-200 text-emerald-900'}`;
        summary.textContent = failed
            ? `${ok} image${ok === 1 ? '' : 's'} uploaded, ${failed} failed. Fix or remove the failed ones and click Retry.`
            : `${ok} image${ok === 1 ? '' : 's'} uploaded to the Media Library successfully.`;
        summary.classList.remove('hidden');

        if (ok > 0) {
            doneLink.textContent = 'Go to Media Library';
            doneLink.className = 'cms-btn cms-btn-accent';
        }
    }

    input.addEventListener('change', () => addFiles(input.files));
    uploadBtn.addEventListener('click', uploadAll);
    clearBtn.addEventListener('click', () => {
        if (busy) return;
        entries = [];
        list.innerHTML = '';
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

    // Warn before leaving mid-upload
    window.addEventListener('beforeunload', (e) => {
        if (busy) { e.preventDefault(); e.returnValue = ''; }
    });
})();
</script>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
