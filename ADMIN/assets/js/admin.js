/**
 * St. Monica Junior School CMS - Admin Interactive JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const backdrop = document.getElementById('sidebarBackdrop');

    function toggleSidebar() {
        if (!sidebar) return;
        const isOpen = sidebar.classList.toggle('open');
        if (backdrop) {
            backdrop.classList.toggle('hidden', !isOpen);
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSidebar);
    }
    if (backdrop) {
        backdrop.addEventListener('click', toggleSidebar);
    }

    // 2. Auto dismiss flash alerts after 6 seconds
    document.querySelectorAll('.cms-alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 6000);
    });

    // 3. Live Image Upload Previews
    document.querySelectorAll('input[type="file"][data-preview-target]').forEach(input => {
        input.addEventListener('change', (e) => {
            const targetId = input.getAttribute('data-preview-target');
            const targetImg = document.getElementById(targetId);
            const container = document.getElementById(targetId + 'Container');
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    if (targetImg) {
                        targetImg.src = event.target.result;
                    }
                    if (container) {
                        container.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    });

    // 4. Slug Generator
    const slugSource = document.querySelector('[data-slug-source]');
    const slugTarget = document.querySelector('[data-slug-target]');

    if (slugSource && slugTarget) {
        let isManuallyEdited = slugTarget.value.trim() !== '';
        
        slugTarget.addEventListener('input', () => {
            isManuallyEdited = true;
        });

        slugSource.addEventListener('input', () => {
            if (!isManuallyEdited || slugTarget.value.trim() === '') {
                slugTarget.value = slugSource.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .trim()
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }

    // 5. Delete Confirmation Modal
    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteForm = document.getElementById('deleteConfirmForm');
    const deleteItemTitle = document.getElementById('deleteItemTitle');
    const deleteCancelBtn = document.getElementById('deleteCancelBtn');

    window.openDeleteModal = function(actionUrl, itemName) {
        if (!deleteModal || !deleteForm) {
            if (confirm(`Are you sure you want to delete "${itemName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = actionUrl;
                document.body.appendChild(form);
                form.submit();
            }
            return;
        }

        deleteForm.action = actionUrl;
        if (deleteItemTitle) {
            deleteItemTitle.textContent = itemName;
        }
        deleteModal.classList.add('active');
    };

    window.closeDeleteModal = function() {
        if (deleteModal) {
            deleteModal.classList.remove('active');
        }
    };

    if (deleteCancelBtn) {
        deleteCancelBtn.addEventListener('click', window.closeDeleteModal);
    }

    if (deleteModal) {
        deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                window.closeDeleteModal();
            }
        });
    }

    // Bind data-delete-btn triggers
    document.querySelectorAll('[data-delete-btn]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const action = btn.getAttribute('data-action');
            const name = btn.getAttribute('data-name') || 'this item';
            window.openDeleteModal(action, name);
        });
    });

    // 6. Accessible Rich-Text Editor Initialization
    document.querySelectorAll('textarea[data-rich-editor]').forEach(textarea => {
        const wrapper = document.createElement('div');
        wrapper.className = 'cms-rich-editor-wrapper mb-2';

        const toolbar = document.createElement('div');
        toolbar.className = 'cms-rich-toolbar flex flex-wrap items-center gap-1 p-2 bg-slate-100 border border-slate-300 rounded-t-lg border-b-0 text-xs select-none';
        toolbar.innerHTML = `
            <button type="button" data-cmd="bold" class="px-2 py-1 hover:bg-white rounded text-slate-700 font-bold border border-transparent hover:border-slate-200 transition" title="Bold (Ctrl+B)">B</button>
            <button type="button" data-cmd="italic" class="px-2 py-1 hover:bg-white rounded text-slate-700 italic border border-transparent hover:border-slate-200 transition" title="Italic (Ctrl+I)">I</button>
            <button type="button" data-cmd="underline" class="px-2 py-1 hover:bg-white rounded text-slate-700 underline border border-transparent hover:border-slate-200 transition" title="Underline">U</button>
            <span class="w-px h-4 bg-slate-300 mx-1"></span>
            <button type="button" data-format="h2" class="px-2 py-1 hover:bg-white rounded text-slate-700 font-bold text-xs border border-transparent hover:border-slate-200 transition" title="Heading 2">H2</button>
            <button type="button" data-format="h3" class="px-2 py-1 hover:bg-white rounded text-slate-700 font-bold text-xs border border-transparent hover:border-slate-200 transition" title="Heading 3">H3</button>
            <button type="button" data-format="p" class="px-2 py-1 hover:bg-white rounded text-slate-700 text-xs border border-transparent hover:border-slate-200 transition" title="Paragraph">¶</button>
            <span class="w-px h-4 bg-slate-300 mx-1"></span>
            <button type="button" data-cmd="insertUnorderedList" class="p-1 hover:bg-white rounded text-slate-700 border border-transparent hover:border-slate-200 transition flex items-center" title="Bullet List"><span class="material-symbols-outlined text-[16px]">format_list_bulleted</span></button>
            <button type="button" data-cmd="insertOrderedList" class="p-1 hover:bg-white rounded text-slate-700 border border-transparent hover:border-slate-200 transition flex items-center" title="Numbered List"><span class="material-symbols-outlined text-[16px]">format_list_numbered</span></button>
            <button type="button" data-format="blockquote" class="p-1 hover:bg-white rounded text-slate-700 border border-transparent hover:border-slate-200 transition flex items-center" title="Blockquote"><span class="material-symbols-outlined text-[16px]">format_quote</span></button>
            <span class="w-px h-4 bg-slate-300 mx-1"></span>
            <button type="button" data-action="link" class="p-1 hover:bg-white rounded text-slate-700 border border-transparent hover:border-slate-200 transition flex items-center" title="Insert Link"><span class="material-symbols-outlined text-[16px]">link</span></button>
            <button type="button" data-cmd="removeFormat" class="p-1 hover:bg-white rounded text-slate-700 border border-transparent hover:border-slate-200 transition flex items-center" title="Clear Formatting"><span class="material-symbols-outlined text-[16px]">format_clear</span></button>
        `;

        const editor = document.createElement('div');
        editor.className = 'cms-rich-content min-h-[160px] max-h-[480px] overflow-y-auto p-4 bg-white border border-slate-300 rounded-b-lg text-slate-800 text-sm focus:outline-none focus:border-[#1e2a4a] focus:ring-1 focus:ring-[#1e2a4a]';
        editor.contentEditable = 'true';
        editor.innerHTML = textarea.value;

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(editor);

        const syncContent = () => {
            textarea.value = editor.innerHTML;
        };

        // The original textarea is hidden, so the browser cannot show its "required" warning and
        // would silently block the submit. Validate the visible editor ourselves instead.
        const isRequired = textarea.hasAttribute('required');
        textarea.removeAttribute('required');

        const errorMsg = document.createElement('p');
        errorMsg.className = 'hidden text-xs font-semibold text-red-600 mt-1.5';
        errorMsg.textContent = 'This field is required. Please write some content before saving.';
        wrapper.after(errorMsg);

        const editorIsEmpty = () =>
            editor.textContent.trim() === '' && !editor.querySelector('img, iframe, video');

        const showRequiredError = (show) => {
            errorMsg.classList.toggle('hidden', !show);
            editor.classList.toggle('border-red-500', show);
            editor.classList.toggle('ring-1', show);
            editor.classList.toggle('ring-red-500', show);
        };

        editor.addEventListener('input', () => {
            syncContent();
            if (!editorIsEmpty()) showRequiredError(false);
        });
        editor.addEventListener('blur', syncContent);
        if (textarea.form) {
            textarea.form.addEventListener('submit', (e) => {
                syncContent();
                if (isRequired && editorIsEmpty()) {
                    e.preventDefault();
                    showRequiredError(true);
                    editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    editor.focus();
                }
            });
        }

        toolbar.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                editor.focus();

                const cmd = btn.getAttribute('data-cmd');
                const format = btn.getAttribute('data-format');
                const action = btn.getAttribute('data-action');

                if (cmd) {
                    document.execCommand(cmd, false, null);
                } else if (format) {
                    document.execCommand('formatBlock', false, `<${format}>`);
                } else if (action === 'link') {
                    const url = prompt('Enter URL address (e.g. https://...):');
                    if (url) {
                        document.execCommand('createLink', false, url);
                    }
                }
                syncContent();
            });
        });
    });

    // 7. Notification Bell Dropdown
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifBellDropdown = document.getElementById('notifBellDropdown');
    if (notifBellBtn && notifBellDropdown) {
        notifBellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifBellDropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
            if (!notifBellDropdown.classList.contains('hidden') && !notifBellDropdown.contains(e.target) && e.target !== notifBellBtn) {
                notifBellDropdown.classList.add('hidden');
            }
        });
    }
});
