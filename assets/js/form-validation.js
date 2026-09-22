/**
 * Form Validation Script for St Monica Junior School
 * Handles jQuery Validation for Contact Form and Application Form (Join Us)
 * Automatically syncs admissions applications to the Admin Panel
 */

$(document).ready(function() {

    // ==========================================
    // 1. Configure jQuery Validation Defaults
    // ==========================================
    if (typeof $.validator !== 'undefined') {
        // Set default required error message across all forms
        $.extend($.validator.messages, {
            required: "Please fill this field"
        });

        // Phone number validation for Uganda & International formats
        $.validator.addMethod("phoneNumber", function(value, element) {
            if (value === "" || value === null) return true; // Let 'required' handle empty values
            // Accepts formats like: +256XXXXXXXXX, 0XXXXXXXXX, 256XXXXXXXXX, with optional spaces/dashes
            return /^(\+?256|0)?[0-9\s\-]{9,15}$/.test(value.trim());
        }, "Please enter a valid phone number (e.g., +256 700 000 000)");
    }

    // Dynamic API URL resolver for Admissions Endpoint
    function getApplyApiUrl() {
        if (window.location.protocol === 'file:') {
            return 'http://localhost/St.monica/ADMIN/api/admissions/apply.php';
        }
        const origin = window.location.origin;
        const pathname = window.location.pathname;
        const stMonicaIdx = pathname.toLowerCase().indexOf('/st.monica');
        if (stMonicaIdx !== -1) {
            return origin + pathname.substring(0, stMonicaIdx) + '/St.monica/ADMIN/api/admissions/apply.php';
        }
        const lastSlash = pathname.lastIndexOf('/');
        const base = (lastSlash !== -1) ? pathname.substring(0, lastSlash + 1) : '/';
        return origin + base + 'ADMIN/api/admissions/apply.php';
    }

    // ==========================================
    // 2. Contact Form Validation
    // ==========================================
    if ($('#contactForm').length) {
        $('#contactForm').validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                email: {
                    required: true,
                    email: true
                },
                subject: {
                    required: true,
                    minlength: 3,
                    maxlength: 200
                },
                message: {
                    required: true,
                    minlength: 5,
                    maxlength: 1000
                }
            },
            messages: {
                name: {
                    required: "Please fill this field",
                    minlength: "Name must be at least 3 characters",
                    maxlength: "Name cannot exceed 100 characters"
                },
                email: {
                    required: "Please fill this field",
                    email: "Please enter a valid email address"
                },
                subject: {
                    required: "Please fill this field",
                    minlength: "Subject must be at least 3 characters",
                    maxlength: "Subject cannot exceed 200 characters"
                },
                message: {
                    required: "Please fill this field",
                    minlength: "Message must be at least 5 characters",
                    maxlength: "Message cannot exceed 1000 characters"
                }
            },
            errorElement: 'span',
            errorClass: 'form-error',
            highlight: function(element) {
                $(element).addClass('is-invalid')
                          .removeClass('border-[#c6c6cf]');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid')
                          .addClass('border-[#c6c6cf]');
            },
            submitHandler: function(form) {
                showAlert('success', 'Thank you for reaching out! Your message has been received by our administration.');
                form.reset();
            }
        });
    }

    // ==========================================
    // 3. Application Form Validation & Submission
    // ==========================================
    function initApplicationValidation() {
        if (!$('#applicationForm').length) return;

        $('#applicationForm').validate({
            rules: {
                parentName: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                mobile: {
                    required: true,
                    phoneNumber: true
                },
                emailAddress: {
                    email: true
                },
                pupilName: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                pupilClass: {
                    required: true
                },
                location: {
                    required: true,
                    minlength: 2,
                    maxlength: 200
                },
                applicationMessage: {
                    required: true,
                    minlength: 5,
                    maxlength: 1000
                }
            },
            messages: {
                parentName: {
                    required: "Please fill this field",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name cannot exceed 100 characters"
                },
                mobile: {
                    required: "Please fill this field",
                    phoneNumber: "Please enter a valid phone number"
                },
                emailAddress: {
                    email: "Please enter a valid email address"
                },
                pupilName: {
                    required: "Please fill this field",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name cannot exceed 100 characters"
                },
                pupilClass: {
                    required: "Please fill this field"
                },
                location: {
                    required: "Please fill this field",
                    minlength: "Location must be at least 2 characters",
                    maxlength: "Location cannot exceed 200 characters"
                },
                applicationMessage: {
                    required: "Please fill this field",
                    minlength: "Message must be at least 5 characters",
                    maxlength: "Message cannot exceed 1000 characters"
                }
            },
            errorElement: 'span',
            errorClass: 'form-error',
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            highlight: function(element) {
                $(element).addClass('is-invalid')
                          .removeClass('border-[#c6c6cf]');
            },
            unhighlight: function(element) {
                $(element).removeClass('is-invalid')
                          .addClass('border-[#c6c6cf]');
            },
            submitHandler: function(form) {
                const formData = {
                    parentName: $('#parentName').val().trim(),
                    mobile: $('#mobile').val().trim(),
                    emailAddress: $('#emailAddress').val().trim(),
                    pupilName: $('#pupilName').val().trim(),
                    pupilClass: $('#pupilClass').val(),
                    location: $('#location').val().trim(),
                    applicationMessage: $('#applicationMessage').val().trim()
                };

                const $submitBtn = $(form).find('button[type="submit"]');
                const originalBtnHtml = $submitBtn.html();
                $submitBtn.prop('disabled', true).html(`
                    <span class="inline-flex items-center justify-center gap-2">
                        <span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span>Submitting Application...</span>
                    </span>
                `);

                const apiUrl = getApplyApiUrl();

                $.ajax({
                    url: apiUrl,
                    method: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function(response) {
                        $submitBtn.prop('disabled', false).html(originalBtnHtml);

                        if (response && response.success) {
                            const appNum = response.data && response.data.application_number ? response.data.application_number : '';
                            const pupil = response.data && response.data.pupil_name ? response.data.pupil_name : formData.pupilName;
                            const pClass = response.data && response.data.pupil_class ? response.data.pupil_class : formData.pupilClass;

                            // Show top-right confirmation alert
                            showAlert('success', `Application received! Application Reference: <strong>${appNum}</strong> for <strong>${pupil}</strong>. The application has been sent to the Admissions office in the admin panel.`);

                            // Show inline modal success view
                            showModalSuccess(appNum, pupil, pClass);
                        } else {
                            const msg = (response && response.message) ? response.message : 'Unable to process application. Please verify all details.';
                            showAlert('error', msg);
                        }
                    },
                    error: function(xhr) {
                        $submitBtn.prop('disabled', false).html(originalBtnHtml);

                        let errMsg = 'Failed to connect to the admissions system. Please ensure your web server is running and try again.';
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res && res.message) {
                                errMsg = res.message;
                            }
                            if (res && res.data && res.data.errors) {
                                $('#applicationForm').validate().showErrors(res.data.errors);
                            }
                        } catch (e) {}

                        showAlert('error', errMsg);
                    }
                });
                return false;
            }
        });
    }

    // Modal success confirmation view
    function showModalSuccess(appNum, pupil, pClass) {
        const formContainer = $('#applicationModal .p-6');
        const originalFormHtml = formContainer.html();

        formContainer.html(`
            <div class="text-center py-6 px-2 space-y-4">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto shadow-sm">
                    <span class="material-symbols-outlined text-4xl">check_circle</span>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-900">Application Submitted!</h3>
                    <p class="text-sm text-slate-600 mt-1 max-w-md mx-auto">Your application details have been recorded and sent directly to the <strong>Admissions Office</strong> in the Admin Panel.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 text-left max-w-md mx-auto space-y-2">
                    <div class="flex justify-between items-center py-1 text-xs border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Tracking Reference:</span>
                        <span class="font-mono font-bold text-[#1e2a4a] text-sm bg-slate-200/60 px-2 py-0.5 rounded">${appNum}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 text-xs border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Pupil Name:</span>
                        <span class="font-semibold text-slate-800">${pupil}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 text-xs border-b border-slate-200">
                        <span class="text-slate-500 font-medium">Class Applied:</span>
                        <span class="font-semibold text-slate-800">${pClass}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 text-xs">
                        <span class="text-slate-500 font-medium">Status:</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">New Application</span>
                    </div>
                </div>
                <div class="pt-2 flex justify-center">
                    <button type="button" id="doneModalBtn" class="bg-[#1e2a4a] text-white text-xs font-semibold py-3 px-8 rounded-full hover:bg-slate-800 transition shadow-sm">
                        Close & Continue
                    </button>
                </div>
            </div>
        `);

        $('#doneModalBtn').click(function() {
            closeModal();
            setTimeout(function() {
                formContainer.html(originalFormHtml);
                initApplicationValidation();
            }, 300);
        });
    }

    // Initialize application form validation
    initApplicationValidation();

    // ==========================================
    // 4. Modal Window Controls
    // ==========================================
    window.closeModal = function() {
        const modal = $('#applicationModal');
        modal.addClass('hidden').removeClass('flex');
        $('body').css('overflow', '');
        if ($('#applicationForm').length) {
            $('#applicationForm')[0].reset();
            if ($('#applicationForm').data('validator')) {
                $('#applicationForm').validate().resetForm();
            }
            $('#applicationForm').find('.is-invalid').removeClass('is-invalid');
        }
    };

    // Open Modal Triggers
    $('#joinUsBtn, #joinUsBtnMobile, a[href="#apply"], a[href="#applicationModal"]').click(function(e) {
        e.preventDefault();
        $('#applicationModal').removeClass('hidden').addClass('flex');
        $('body').css('overflow', 'hidden');
    });

    $('#closeModalBtn, #cancelBtn').click(function() {
        closeModal();
    });

    $('#applicationModal').click(function(e) {
        if (e.target.id === 'applicationModal') {
            closeModal();
        }
    });

    $(document).keydown(function(e) {
        if (e.key === 'Escape' && !$('#applicationModal').hasClass('hidden')) {
            closeModal();
        }
    });

    // Handle deep-link to #apply hash
    if (window.location.hash === '#apply' || window.location.hash === '#applicationModal') {
        setTimeout(function() {
            $('#applicationModal').removeClass('hidden').addClass('flex');
            $('body').css('overflow', 'hidden');
        }, 200);
    }

    // ==========================================
    // 5. Toast Alert Notifications
    // ==========================================
    function showAlert(type, message) {
        const alertClass = type === 'success' 
            ? 'bg-[#e7f5e7] border-[#4caf50] text-[#2e7d32]' 
            : 'bg-[#ffebee] border-[#f44336] text-[#c62828]';
        
        const iconName = type === 'success' ? 'check_circle' : 'error';
        
        const alertHtml = `
            <div class="fixed top-4 right-4 z-[100] max-w-md animate-slide-in" id="customAlert">
                <div class="flex items-start gap-3 p-4 rounded-lg border-l-4 shadow-lg ${alertClass}">
                    <span class="material-symbols-outlined text-[24px] flex-shrink-0">${iconName}</span>
                    <div class="flex-1">
                        <p class="text-[14px] leading-[20px] font-semibold">${message}</p>
                    </div>
                    <button onclick="$('#customAlert').remove()" class="flex-shrink-0 hover:opacity-70">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
            </div>
        `;
        
        $('#customAlert').remove();
        $('body').append(alertHtml);
        
        setTimeout(function() {
            $('#customAlert').fadeOut(300, function() {
                $(this).remove();
            });
        }, 6000);
    }

});
