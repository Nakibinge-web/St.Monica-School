/**
 * St. Monica Junior School CMS - Media Library Picker
 *
 * Lets image fields be filled from the Media Library instead of a file on the computer.
 *
 * Image fields: markup rendered by render_media_picker() in includes/functions.php
 *   [data-media-picker]  wrapper with data-endpoint / data-csrf / data-original-src
 *   [data-media-input]   hidden input that receives the chosen library file path
 *   [data-media-preview] preview <img>, [data-media-empty] placeholder icon
 *   [data-media-open] / [data-media-reset] buttons, [data-media-status] caption
 *
 * Plain text inputs: a button with data-media-picker-for="<input id>" plus
 *   data-endpoint / data-csrf fills that input with the chosen file path.
 */
(() => {
    let modal = null;
    let els = {};
    let config = null;          // { endpoint, csrf, onPick(item) } for the field being edited
    let selected = null;        // currently highlighted library item
    let page = 1;
    let requestId = 0;
    let searchTimer = null;
    let categoriesLoaded = false;
    let activeCategory = '';

    const MAX_UPLOAD_BYTES = 8 * 1024 * 1024;
    const HINT_TEXT = 'Click an image to select it. Double-click to use it straight away.';

    function buildModal() {
        modal = document.createElement('div');
        modal.className = 'cms-modal-backdrop';
        modal.id = 'mediaPickerModal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'mediaPickerTitle');
        modal.innerHTML = `
            <div class="cms-modal media-picker-modal">
                <!-- Header -->
                <div class="mp-header">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="mp-header-icon">
                            <span class="material-symbols-outlined text-[22px]">photo_library</span>
                        </span>
                        <div class="min-w-0">
                            <h3 id="mediaPickerTitle" class="text-base font-bold text-slate-900 brand-font leading-tight">Media Library</h3>
                            <p class="text-xs text-slate-500 truncate">Choose an image, or upload a new one to the library.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="mp-segment" role="tablist">
                            <button type="button" class="mp-segment-btn is-active" data-mp-tab="library" role="tab">
                                <span class="material-symbols-outlined text-[18px]">grid_view</span>
                                <span class="hidden sm:inline">Library</span>
                            </button>
                            <button type="button" class="mp-segment-btn" data-mp-tab="upload" role="tab">
                                <span class="material-symbols-outlined text-[18px]">cloud_upload</span>
                                <span class="hidden sm:inline">Upload</span>
                            </button>
                        </div>
                        <button type="button" class="mp-icon-btn" data-mp-close aria-label="Close">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>
                </div>

                <!-- Library pane -->
                <div class="media-picker-pane" data-mp-pane="library">
                    <div class="mp-library">
                        <div class="mp-browser">
                            <div class="mp-toolbar">
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
                                    <input type="search" class="cms-input pl-10 text-sm bg-white" placeholder="Search images by title, alt text or file name" data-mp-search aria-label="Search images">
                                </div>
                                <div class="mp-chips" data-mp-chips role="group" aria-label="Filter by category">
                                    <button type="button" class="mp-chip is-active" data-mp-chip="">All</button>
                                </div>
                            </div>

                            <div class="media-picker-scroll mp-grid-area">
                                <p class="mp-count hidden" data-mp-count></p>
                                <div class="media-picker-grid" data-mp-grid></div>
                                <div class="hidden" data-mp-message></div>
                                <div class="text-center mt-5 hidden" data-mp-more-wrap>
                                    <button type="button" class="cms-btn cms-btn-outline text-xs" data-mp-more>
                                        <span class="material-symbols-outlined text-[16px]">expand_more</span>
                                        <span>Load More Images</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Details panel (wide screens) -->
                        <aside class="mp-details" data-mp-details aria-live="polite">
                            <div class="mp-details-empty" data-mp-details-empty>
                                <span class="material-symbols-outlined text-[36px] text-slate-300">touch_app</span>
                                <p class="text-sm font-semibold text-slate-600 mt-2">No image selected</p>
                                <p class="text-xs text-slate-400 mt-1">Select an image to preview it here.</p>
                            </div>
                            <div class="hidden" data-mp-details-body>
                                <div class="mp-details-preview">
                                    <img alt="" data-mp-details-img>
                                </div>
                                <h4 class="text-sm font-bold text-slate-900 mt-4 break-words" data-mp-details-title></h4>
                                <dl class="mp-details-list">
                                    <div><dt>Dimensions</dt><dd data-mp-details-dims></dd></div>
                                    <div><dt>Category</dt><dd data-mp-details-cat></dd></div>
                                    <div><dt>Alt text</dt><dd data-mp-details-alt></dd></div>
                                </dl>
                            </div>
                        </aside>
                    </div>
                </div>

                <!-- Upload pane -->
                <div class="media-picker-pane hidden" data-mp-pane="upload">
                    <form class="media-picker-scroll mp-upload" data-mp-upload-form novalidate>
                        <label class="mp-dropzone" data-mp-dropzone>
                            <input type="file" name="media_file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-mp-file>
                            <img class="mp-dropzone-preview hidden" alt="Selected image preview" data-mp-file-preview>
                            <span class="mp-dropzone-prompt" data-mp-drop-prompt>
                                <span class="mp-dropzone-icon">
                                    <span class="material-symbols-outlined text-[28px]">add_photo_alternate</span>
                                </span>
                                <span class="block text-sm font-semibold text-slate-800 mt-3">Drag an image here or <span class="text-[#d93633] underline underline-offset-2">browse</span></span>
                                <span class="block text-xs text-slate-400 mt-1">JPG, PNG, WebP or GIF · up to 8MB · automatically optimized</span>
                            </span>
                            <span class="mp-dropzone-change hidden" data-mp-drop-change>
                                <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
                                <span>Choose a different image</span>
                            </span>
                        </label>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="mpUploadTitle">Title</label>
                                <input type="text" id="mpUploadTitle" name="title" class="cms-input" placeholder="Defaults to the file name" data-mp-upload-title>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="mpUploadCategory">Category</label>
                                <select id="mpUploadCategory" name="category" class="cms-select" data-mp-upload-category></select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="mpUploadAlt">
                                    Alt text <span class="font-normal text-emerald-700">· helps SEO &amp; screen readers</span>
                                </label>
                                <textarea id="mpUploadAlt" name="alt_text" rows="3" class="cms-textarea text-sm" placeholder="e.g. Pupils reading together in the school library"></textarea>
                            </div>
                            <p class="hidden mp-error" data-mp-upload-error></p>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="mp-footer">
                    <div class="flex items-center gap-3 min-w-0" data-mp-selected-wrap>
                        <img class="mp-footer-thumb hidden" alt="" data-mp-footer-thumb>
                        <p class="text-xs text-slate-500 truncate" data-mp-selected-label>${HINT_TEXT}</p>
                    </div>
                    <div class="flex items-center justify-end gap-2 shrink-0">
                        <button type="button" class="cms-btn cms-btn-outline text-xs" data-mp-close>Cancel</button>
                        <button type="button" class="cms-btn cms-btn-accent text-xs" data-mp-confirm disabled>
                            <span class="material-symbols-outlined text-[16px]">check</span>
                            <span>Use Selected Image</span>
                        </button>
                        <button type="button" class="cms-btn cms-btn-accent text-xs hidden" data-mp-upload-btn>
                            <span class="material-symbols-outlined text-[16px]">cloud_upload</span>
                            <span data-mp-upload-label>Upload &amp; Use Image</span>
                        </button>
                    </div>
                </div>
            </div>`;
        document.body.appendChild(modal);

        const q = (sel) => modal.querySelector(sel);
        els = {
            grid: q('[data-mp-grid]'),
            count: q('[data-mp-count]'),
            message: q('[data-mp-message]'),
            moreWrap: q('[data-mp-more-wrap]'),
            more: q('[data-mp-more]'),
            search: q('[data-mp-search]'),
            chips: q('[data-mp-chips]'),
            confirm: q('[data-mp-confirm]'),
            selectedLabel: q('[data-mp-selected-label]'),
            footerThumb: q('[data-mp-footer-thumb]'),
            detailsEmpty: q('[data-mp-details-empty]'),
            detailsBody: q('[data-mp-details-body]'),
            detailsImg: q('[data-mp-details-img]'),
            detailsTitle: q('[data-mp-details-title]'),
            detailsDims: q('[data-mp-details-dims]'),
            detailsCat: q('[data-mp-details-cat]'),
            detailsAlt: q('[data-mp-details-alt]'),
            uploadForm: q('[data-mp-upload-form]'),
            uploadTitle: q('[data-mp-upload-title]'),
            uploadCategory: q('[data-mp-upload-category]'),
            uploadError: q('[data-mp-upload-error]'),
            uploadBtn: q('[data-mp-upload-btn]'),
            uploadLabel: q('[data-mp-upload-label]'),
            dropzone: q('[data-mp-dropzone]'),
            dropPrompt: q('[data-mp-drop-prompt]'),
            dropChange: q('[data-mp-drop-change]'),
            file: q('[data-mp-file]'),
            filePreview: q('[data-mp-file-preview]'),
        };

        modal.querySelectorAll('[data-mp-close]').forEach(btn => btn.addEventListener('click', close));
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) close();
        });

        modal.querySelectorAll('[data-mp-tab]').forEach(tab => {
            tab.addEventListener('click', () => showTab(tab.dataset.mpTab));
        });

        els.search.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => load(true), 300);
        });
        els.chips.addEventListener('click', (e) => {
            const chip = e.target.closest('[data-mp-chip]');
            if (!chip || chip.dataset.mpChip === activeCategory) return;
            activeCategory = chip.dataset.mpChip;
            els.chips.querySelectorAll('[data-mp-chip]').forEach(c => c.classList.toggle('is-active', c === chip));
            load(true);
        });
        els.more.addEventListener('click', () => load(false));
        els.confirm.addEventListener('click', () => { if (selected) pick(selected); });

        // Upload: file chooser + drag and drop
        els.file.addEventListener('change', () => setUploadFile(els.file.files && els.file.files[0]));
        ['dragenter', 'dragover'].forEach(evt => els.dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            els.dropzone.classList.add('is-dragover');
        }));
        ['dragleave', 'drop'].forEach(evt => els.dropzone.addEventListener(evt, (e) => {
            e.preventDefault();
            els.dropzone.classList.remove('is-dragover');
        }));
        els.dropzone.addEventListener('drop', (e) => {
            const file = e.dataTransfer && e.dataTransfer.files[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            els.file.files = transfer.files;
            setUploadFile(file);
        });
        els.uploadForm.addEventListener('submit', upload);
        els.uploadBtn.addEventListener('click', upload);
    }

    function showTab(name) {
        modal.querySelectorAll('[data-mp-tab]').forEach(t => {
            const active = t.dataset.mpTab === name;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        modal.querySelectorAll('[data-mp-pane]').forEach(p => p.classList.toggle('hidden', p.dataset.mpPane !== name));

        // Footer swaps between "use selected" (library) and "upload" (upload tab)
        const isLibrary = name === 'library';
        els.confirm.classList.toggle('hidden', !isLibrary);
        els.uploadBtn.classList.toggle('hidden', isLibrary);
        els.footerThumb.classList.toggle('hidden', !isLibrary || !selected);
        els.selectedLabel.textContent = isLibrary
            ? selectedLabelText()
            : 'The image is added to the Media Library, then used for this field.';
        if (isLibrary) els.search.focus();
    }

    function open(cfg) {
        if (!modal) buildModal();
        config = cfg;
        setSelected(null);
        resetUpload();
        showTab('library');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        load(true);
    }

    function close() {
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
        config = null;
    }

    function setMessage(html) {
        els.message.innerHTML = html;
        els.message.classList.toggle('hidden', !html);
    }

    function stateMessage(icon, title, text, action) {
        return `
            <div class="mp-state">
                <span class="mp-state-icon"><span class="material-symbols-outlined text-[28px]">${icon}</span></span>
                <p class="text-sm font-semibold text-slate-700 mt-3">${title}</p>
                <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">${text}</p>
                ${action ? `<button type="button" class="cms-btn cms-btn-accent text-xs mt-4" data-mp-goto-upload>
                    <span class="material-symbols-outlined text-[16px]">cloud_upload</span><span>${action}</span></button>` : ''}
            </div>`;
    }

    function showSkeletons(count = 10) {
        els.grid.innerHTML = Array.from({ length: count }, () => `
            <div class="mp-skeleton" aria-hidden="true">
                <span class="mp-skeleton-thumb"></span>
                <span class="mp-skeleton-line"></span>
                <span class="mp-skeleton-line is-short"></span>
            </div>`).join('');
    }

    function fillCategories(categories) {
        if (categoriesLoaded || !Array.isArray(categories)) return;
        categories.forEach(cat => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'mp-chip';
            chip.dataset.mpChip = cat;
            chip.textContent = cat;
            els.chips.appendChild(chip);
            els.uploadCategory.appendChild(new Option(cat, cat, cat === 'General', cat === 'General'));
        });
        categoriesLoaded = true;
    }

    async function load(reset) {
        if (reset) {
            page = 1;
            setSelected(null);
            showSkeletons();
            els.count.classList.add('hidden');
            els.moreWrap.classList.add('hidden');
        }
        const thisRequest = ++requestId;
        setMessage('');
        els.more.disabled = true;

        const params = new URLSearchParams({
            search: els.search.value.trim(),
            category: activeCategory,
            page: String(page),
        });

        try {
            const res = await fetch(`${config.endpoint}?${params}`, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const json = await res.json();
            if (thisRequest !== requestId) return; // a newer search replaced this one
            if (!json.success) throw new Error(json.message || 'Could not load images.');

            const data = json.data;
            fillCategories(data.categories);
            if (reset) els.grid.innerHTML = '';
            data.items.forEach(item => els.grid.appendChild(renderTile(item)));

            const shown = els.grid.children.length;
            els.count.textContent = `${shown} of ${data.total} image${data.total === 1 ? '' : 's'}`;
            els.count.classList.toggle('hidden', shown === 0);

            if (shown === 0) {
                const filtered = els.search.value.trim() || activeCategory;
                setMessage(filtered
                    ? stateMessage('search_off', 'No matching images', 'Try a different search term or category.')
                    : stateMessage('photo_library', 'Your media library is empty', 'Upload the first image and it will be available for every section of the website.', 'Upload an Image'));
                const goUpload = els.message.querySelector('[data-mp-goto-upload]');
                if (goUpload) goUpload.addEventListener('click', () => showTab('upload'));
            }
            els.moreWrap.classList.toggle('hidden', !data.has_more || shown === 0);
            page = data.page + 1;
        } catch (err) {
            if (thisRequest !== requestId) return;
            if (reset) els.grid.innerHTML = '';
            setMessage(stateMessage('cloud_off', 'Could not load images', escapeText(err.message || 'Please check your connection and try again.')));
            els.moreWrap.classList.add('hidden');
        } finally {
            els.more.disabled = false;
        }
    }

    function escapeText(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderTile(item) {
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'media-picker-tile';
        tile.title = item.dimensions ? `${item.title} (${item.dimensions})` : item.title;
        tile.setAttribute('aria-pressed', 'false');

        const thumb = document.createElement('span');
        thumb.className = 'media-picker-thumb';
        const img = document.createElement('img');
        img.src = item.url;
        img.alt = item.alt_text || item.title;
        img.loading = 'lazy';
        thumb.appendChild(img);

        const caption = document.createElement('span');
        caption.className = 'media-picker-caption';
        const title = document.createElement('span');
        title.className = 'media-picker-title';
        title.textContent = item.title;
        const meta = document.createElement('span');
        meta.className = 'media-picker-meta';
        meta.textContent = [item.dimensions, item.category].filter(Boolean).join(' · ');
        caption.append(title, meta);

        const check = document.createElement('span');
        check.className = 'media-picker-check';
        check.innerHTML = '<span class="material-symbols-outlined text-[16px]">check</span>';

        tile.append(thumb, caption, check);
        tile.addEventListener('click', () => setSelected(item, tile));
        tile.addEventListener('dblclick', () => pick(item));
        return tile;
    }

    function selectedLabelText() {
        return selected
            ? `${selected.title}${selected.dimensions ? ' · ' + selected.dimensions : ''}`
            : HINT_TEXT;
    }

    function setSelected(item, tile) {
        selected = item;
        els.grid.querySelectorAll('.media-picker-tile.is-selected').forEach(t => {
            t.classList.remove('is-selected');
            t.setAttribute('aria-pressed', 'false');
        });
        if (tile) {
            tile.classList.add('is-selected');
            tile.setAttribute('aria-pressed', 'true');
        }
        els.confirm.disabled = !item;
        els.selectedLabel.textContent = selectedLabelText();

        // Footer thumbnail
        els.footerThumb.classList.toggle('hidden', !item);
        if (item) els.footerThumb.src = item.url;

        // Details panel
        els.detailsEmpty.classList.toggle('hidden', !!item);
        els.detailsBody.classList.toggle('hidden', !item);
        if (item) {
            els.detailsImg.src = item.url;
            els.detailsImg.alt = item.alt_text || item.title;
            els.detailsTitle.textContent = item.title;
            els.detailsDims.textContent = item.dimensions || 'Unknown';
            els.detailsCat.textContent = item.category || 'General';
            els.detailsAlt.textContent = item.alt_text || 'Not set. Add it from the Media Library edit page.';
            els.detailsAlt.classList.toggle('is-missing', !item.alt_text);
        }
    }

    function setUploadFile(file) {
        els.uploadError.classList.add('hidden');
        if (!file) return;
        if (!/^image\/(jpeg|png|webp|gif)$/.test(file.type)) {
            showUploadError('Please choose a JPG, PNG, WebP or GIF image.');
            return;
        }
        if (file.size > MAX_UPLOAD_BYTES) {
            showUploadError('That image is larger than 8MB. Please choose a smaller file.');
            return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
            els.filePreview.src = ev.target.result;
            els.filePreview.classList.remove('hidden');
            els.dropPrompt.classList.add('hidden');
            els.dropChange.classList.remove('hidden');
            els.dropzone.classList.add('has-file');
        };
        reader.readAsDataURL(file);
        if (!els.uploadTitle.value.trim()) {
            els.uploadTitle.placeholder = file.name.replace(/\.[^.]+$/, '').replace(/[-_.]+/g, ' ');
        }
    }

    function resetUpload() {
        els.uploadForm.reset();
        els.filePreview.classList.add('hidden');
        els.filePreview.removeAttribute('src');
        els.dropPrompt.classList.remove('hidden');
        els.dropChange.classList.add('hidden');
        els.dropzone.classList.remove('has-file');
        els.uploadTitle.placeholder = 'Defaults to the file name';
        els.uploadError.classList.add('hidden');
    }

    function pick(item) {
        if (config && typeof config.onPick === 'function') config.onPick(item);
        close();
    }

    async function upload(e) {
        e.preventDefault();
        if (!els.file.files || !els.file.files[0]) {
            showUploadError('Please choose an image file to upload.');
            return;
        }

        const body = new FormData(els.uploadForm);
        body.append('csrf_token', config.csrf);
        els.uploadBtn.disabled = true;
        els.uploadLabel.textContent = 'Uploading…';
        els.uploadError.classList.add('hidden');

        try {
            const res = await fetch(config.endpoint, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Upload failed.');
            pick(json.data);
        } catch (err) {
            showUploadError(err.message || 'Upload failed. Please try again.');
        } finally {
            els.uploadBtn.disabled = false;
            els.uploadLabel.textContent = 'Upload & Use Image';
        }
    }

    function showUploadError(text) {
        els.uploadError.textContent = text;
        els.uploadError.classList.remove('hidden');
    }

    // Image fields rendered by render_media_picker()
    function initField(field) {
        const input = field.querySelector('[data-media-input]');
        const preview = field.querySelector('[data-media-preview]');
        const empty = field.querySelector('[data-media-empty]');
        const status = field.querySelector('[data-media-status]');
        const resetBtn = field.querySelector('[data-media-reset]');
        const originalSrc = field.dataset.originalSrc || '';
        const originalStatus = status.textContent.trim();

        function showPreview(src) {
            preview.src = src;
            preview.classList.toggle('hidden', !src);
            empty.classList.toggle('hidden', !!src);
        }

        field.querySelector('[data-media-open]').addEventListener('click', () => {
            open({
                endpoint: field.dataset.endpoint,
                csrf: field.dataset.csrf,
                onPick(item) {
                    input.value = item.file_path;
                    showPreview(item.url);
                    status.textContent = `New image: ${item.title}`;
                    resetBtn.classList.remove('hidden');
                },
            });
        });

        resetBtn.addEventListener('click', () => {
            input.value = '';
            showPreview(originalSrc);
            status.textContent = originalStatus;
            resetBtn.classList.add('hidden');
        });
    }

    // Buttons that fill an existing text input (e.g. SEO social image path)
    function initTextTarget(btn) {
        const target = document.getElementById(btn.dataset.mediaPickerFor);
        if (!target) return;
        btn.addEventListener('click', () => {
            open({
                endpoint: btn.dataset.endpoint,
                csrf: btn.dataset.csrf,
                onPick(item) {
                    target.value = item.file_path;
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                },
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-media-picker]').forEach(initField);
        document.querySelectorAll('[data-media-picker-for]').forEach(initTextTarget);
    });
})();
