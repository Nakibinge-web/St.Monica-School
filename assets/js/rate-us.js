/**
 * St. Monica Junior School - "Rate Us" Modal Controller
 * Handles the star-rating review form on the homepage testimonials section
 * and submits it to the CMS as a pending (draft) testimonial for review.
 */
(function () {
    'use strict';

    function getSubmitApiUrl() {
        if (window.location.protocol === 'file:') {
            return 'http://localhost/St.monica/ADMIN/api/testimonials/submit.php';
        }
        const loc = window.location.pathname;
        const rootIdx = loc.lastIndexOf('/');
        const pathPrefix = (rootIdx !== -1) ? loc.substring(0, rootIdx + 1) : '/';
        return window.location.origin + pathPrefix + 'ADMIN/api/testimonials/submit.php';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('rateUsModal');
        const openBtn = document.getElementById('rateUsBtn');
        const closeBtn = document.getElementById('closeRateUsModalBtn');
        const cancelBtn = document.getElementById('cancelRateUsBtn');
        const modalBody = document.getElementById('rateUsModalBody');

        if (!modal || !openBtn || !modalBody) return;

        const originalBodyHtml = modalBody.innerHTML;

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            setTimeout(function () {
                modalBody.innerHTML = originalBodyHtml;
                bindFormBehavior();
            }, 250);
        }

        openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        function paintStars(starButtons, value) {
            starButtons.forEach(function (btn) {
                const icon = btn.querySelector('span');
                const val = parseInt(btn.getAttribute('data-value'), 10);
                if (val <= value) {
                    icon.classList.remove('text-[#c6c6cf]');
                    icon.classList.add('text-[#d93633]');
                    icon.style.fontVariationSettings = "'FILL' 1";
                } else {
                    icon.classList.remove('text-[#d93633]');
                    icon.classList.add('text-[#c6c6cf]');
                    icon.style.fontVariationSettings = "'FILL' 0";
                }
            });
        }

        function showFieldError(input, message) {
            if (!input) return;
            input.classList.add('border-[#d93633]');
            const errorEl = input.parentElement.querySelector('.form-error');
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            }
        }

        function clearFieldError(input) {
            if (!input) return;
            input.classList.remove('border-[#d93633]');
            const errorEl = input.parentElement.querySelector('.form-error');
            if (errorEl) errorEl.classList.add('hidden');
        }

        function showToast(type, message) {
            const existing = document.getElementById('rateUsToast');
            if (existing) existing.remove();

            const colorClass = type === 'success'
                ? 'bg-[#e7f5e7] border-[#4caf50] text-[#2e7d32]'
                : 'bg-[#ffebee] border-[#f44336] text-[#c62828]';
            const icon = type === 'success' ? 'check_circle' : 'error';

            const toast = document.createElement('div');
            toast.id = 'rateUsToast';
            toast.className = 'fixed top-4 right-4 z-[100] max-w-md';
            toast.innerHTML =
                '<div class="flex items-start gap-3 p-4 rounded-lg border-l-4 shadow-lg ' + colorClass + '">' +
                '<span class="material-symbols-outlined text-[24px] flex-shrink-0">' + icon + '</span>' +
                '<div class="flex-1"><p class="text-[14px] leading-[20px] font-semibold"></p></div>' +
                '<button type="button" class="flex-shrink-0 hover:opacity-70" aria-label="Dismiss">' +
                '<span class="material-symbols-outlined text-[20px]">close</span></button></div>';
            toast.querySelector('p').textContent = message;
            toast.querySelector('button').addEventListener('click', function () { toast.remove(); });

            document.body.appendChild(toast);
            setTimeout(function () {
                if (toast.parentElement) toast.remove();
            }, 6000);
        }

        function showSuccessState() {
            modalBody.innerHTML =
                '<div class="text-center py-8 px-2 space-y-4">' +
                '<div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-sm">' +
                '<span class="material-symbols-outlined text-4xl">check_circle</span></div>' +
                '<div><h3 class="text-[20px] font-bold text-[#1e2a4a]">Thank You!</h3>' +
                '<p class="text-[14px] text-[#45464e] mt-1 max-w-sm mx-auto">Your review has been received and will be published on our website after being reviewed by our administration.</p></div>' +
                '<div class="pt-2 flex justify-center">' +
                '<button type="button" id="doneRateUsBtn" class="bg-[#1e2a4a] text-white text-[14px] font-semibold py-3 px-8 rounded-full hover:bg-[#0f1830] transition shadow-sm">Close</button>' +
                '</div></div>';
            const doneBtn = document.getElementById('doneRateUsBtn');
            if (doneBtn) doneBtn.addEventListener('click', closeModal);
        }

        function handleSubmit(e) {
            e.preventDefault();

            const nameInput = document.getElementById('raterName');
            const messageInput = document.getElementById('raterMessage');
            const roleSelect = document.getElementById('raterRole');
            const roleOther = document.getElementById('raterRoleOther');
            const ratingInput = document.getElementById('ratingValue');
            const ratingError = document.getElementById('ratingError');
            const submitBtn = document.getElementById('submitRateUsBtn');

            let valid = true;
            clearFieldError(nameInput);
            clearFieldError(messageInput);
            if (ratingError) ratingError.classList.add('hidden');

            const name = nameInput.value.trim();
            const message = messageInput.value.trim();
            const rating = parseInt(ratingInput.value, 10) || 0;
            let role = roleSelect.value;
            if (role === 'Other') {
                role = roleOther.value.trim() || 'Other';
            }

            if (name.length < 3) {
                showFieldError(nameInput, 'Please enter your full name (minimum 3 characters).');
                valid = false;
            }
            if (rating < 1 || rating > 5) {
                if (ratingError) ratingError.classList.remove('hidden');
                valid = false;
            }
            if (message.length < 10) {
                showFieldError(messageInput, 'Please share a bit more detail (minimum 10 characters).');
                valid = false;
            }

            if (!valid) return;

            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="inline-flex items-center gap-2">' +
                '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>' +
                '<span>Submitting...</span></span>';

            fetch(getSubmitApiUrl(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name, role: role, rating: rating, content: message })
            })
                .then(function (res) {
                    return res.json().catch(function () { return {}; });
                })
                .then(function (json) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;

                    if (json && json.success) {
                        showSuccessState();
                    } else {
                        const msg = (json && json.message) ? json.message : 'Unable to submit your review. Please try again.';
                        showToast('error', msg);
                        if (json && json.data && json.data.errors) {
                            const errs = json.data.errors;
                            if (errs.name) showFieldError(nameInput, errs.name);
                            if (errs.content) showFieldError(messageInput, errs.content);
                            if (errs.rating && ratingError) ratingError.classList.remove('hidden');
                        }
                    }
                })
                .catch(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                    showToast('error', 'Failed to connect. Please check your connection and try again.');
                });
        }

        function bindFormBehavior() {
            const form = document.getElementById('rateUsForm');
            if (!form) return;

            const starButtons = Array.prototype.slice.call(modalBody.querySelectorAll('.star-btn'));
            const ratingInput = document.getElementById('ratingValue');
            const ratingError = document.getElementById('ratingError');
            const roleSelect = document.getElementById('raterRole');
            const roleOther = document.getElementById('raterRoleOther');
            const starsContainer = document.getElementById('starRatingInput');

            starButtons.forEach(function (btn) {
                btn.addEventListener('mouseenter', function () {
                    paintStars(starButtons, parseInt(btn.getAttribute('data-value'), 10));
                });
                btn.addEventListener('click', function () {
                    const val = parseInt(btn.getAttribute('data-value'), 10);
                    ratingInput.value = val;
                    paintStars(starButtons, val);
                    if (ratingError) ratingError.classList.add('hidden');
                });
            });
            if (starsContainer) {
                starsContainer.addEventListener('mouseleave', function () {
                    paintStars(starButtons, parseInt(ratingInput.value, 10) || 0);
                });
            }

            if (roleSelect && roleOther) {
                roleSelect.addEventListener('change', function () {
                    if (roleSelect.value === 'Other') {
                        roleOther.classList.remove('hidden');
                    } else {
                        roleOther.classList.add('hidden');
                        roleOther.value = '';
                    }
                });
            }

            form.addEventListener('submit', handleSubmit);
        }

        bindFormBehavior();
    });
})();
