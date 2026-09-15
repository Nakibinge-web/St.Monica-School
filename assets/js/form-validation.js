/**
 * Form Validation Script for St Monica Junior School
 * Handles validation for Contact Form and Application Form (Join Us)
 */

$(document).ready(function() {
    
    // ===========================
    // Contact Form Validation
    // ===========================
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
                minlength: 5,
                maxlength: 200
            },
            message: {
                required: true,
                minlength: 10,
                maxlength: 1000
            }
        },
        messages: {
            name: {
                required: "Please enter your full name",
                minlength: "Name must be at least 3 characters",
                maxlength: "Name cannot exceed 100 characters"
            },
            email: {
                required: "Please enter your email address",
                email: "Please enter a valid email address"
            },
            subject: {
                required: "Please enter a subject",
                minlength: "Subject must be at least 5 characters",
                maxlength: "Subject cannot exceed 200 characters"
            },
            message: {
                required: "Please enter your message",
                minlength: "Message must be at least 10 characters",
                maxlength: "Message cannot exceed 1000 characters"
            }
        },
        errorElement: 'span',
        errorClass: 'text-[#ba1a1a] text-[12px] leading-[16px] mt-1 block',
        highlight: function(element) {
            $(element).addClass('border-[#ba1a1a] focus:border-[#ba1a1a] focus:ring-[#ba1a1a]')
                     .removeClass('border-[#c6c6cf]');
        },
        unhighlight: function(element) {
            $(element).removeClass('border-[#ba1a1a] focus:border-[#ba1a1a] focus:ring-[#ba1a1a]')
                     .addClass('border-[#c6c6cf]');
        },
        submitHandler: function(form) {
            // Get form data
            const formData = {
                name: $('#contactForm #name').val(),
                email: $('#contactForm #email').val(),
                subject: $('#contactForm #subject').val(),
                message: $('#contactForm #message').val()
            };

            console.log('Contact Form submitted:', formData);

            // Show success message
            showAlert('success', 'Thank you for contacting us! We will get back to you soon.');

            // Reset form
            form.reset();
            
            // Here you would typically send data to server
            // $.ajax({
            //     url: 'your-server-endpoint.php',
            //     method: 'POST',
            //     data: formData,
            //     success: function(response) {
            //         showAlert('success', 'Message sent successfully!');
            //     },
            //     error: function() {
            //         showAlert('error', 'Failed to send message. Please try again.');
            //     }
            // });
        }
    });

    // ===========================
    // Application Form Validation
    // ===========================
    $('#applicationForm').validate({
        rules: {
            parentName: {
                required: true,
                minlength: 3,
                maxlength: 100
            },
            mobile: {
                required: true,
                minlength: 10,
                maxlength: 15,
                phoneNumber: true
            },
            emailAddress: {
                email: true
            },
            pupilName: {
                required: true,
                minlength: 3,
                maxlength: 100
            },
            pupilClass: {
                required: true
            },
            location: {
                required: true,
                minlength: 5,
                maxlength: 200
            },
            applicationMessage: {
                required: true,
                minlength: 20,
                maxlength: 1000
            }
        },
        messages: {
            parentName: {
                required: "Please enter parent's full name",
                minlength: "Name must be at least 3 characters",
                maxlength: "Name cannot exceed 100 characters"
            },
            mobile: {
                required: "Please enter your mobile number",
                minlength: "Please enter a valid mobile number",
                maxlength: "Mobile number is too long",
                phoneNumber: "Please enter a valid phone number (e.g., +256 XXX XXX XXX)"
            },
            emailAddress: {
                email: "Please enter a valid email address"
            },
            pupilName: {
                required: "Please enter pupil's full name",
                minlength: "Name must be at least 3 characters",
                maxlength: "Name cannot exceed 100 characters"
            },
            pupilClass: {
                required: "Please select a class"
            },
            location: {
                required: "Please enter your location",
                minlength: "Location must be at least 5 characters",
                maxlength: "Location cannot exceed 200 characters"
            },
            applicationMessage: {
                required: "Please enter a message about your application",
                minlength: "Message must be at least 20 characters",
                maxlength: "Message cannot exceed 1000 characters"
            }
        },
        errorElement: 'span',
        errorClass: 'text-[#ba1a1a] text-[12px] leading-[16px] mt-1 block',
        highlight: function(element) {
            $(element).addClass('border-[#ba1a1a] focus:border-[#ba1a1a] focus:ring-[#ba1a1a]')
                     .removeClass('border-[#c6c6cf]');
        },
        unhighlight: function(element) {
            $(element).removeClass('border-[#ba1a1a] focus:border-[#ba1a1a] focus:ring-[#ba1a1a]')
                     .addClass('border-[#c6c6cf]');
        },
        submitHandler: function(form) {
            // Get form data
            const formData = {
                parentName: $('#parentName').val(),
                mobile: $('#mobile').val(),
                emailAddress: $('#emailAddress').val(),
                pupilName: $('#pupilName').val(),
                pupilClass: $('#pupilClass').val(),
                location: $('#location').val(),
                message: $('#applicationMessage').val()
            };

            console.log('Application Form submitted:', formData);

            // Show success message
            showAlert('success', 'Thank you! Your application has been submitted successfully. We will contact you soon.');

            // Close modal
            closeModal();
            
            // Here you would typically send data to server
            // $.ajax({
            //     url: 'your-application-endpoint.php',
            //     method: 'POST',
            //     data: formData,
            //     success: function(response) {
            //         showAlert('success', 'Application submitted successfully!');
            //         closeModal();
            //     },
            //     error: function() {
            //         showAlert('error', 'Failed to submit application. Please try again.');
            //     }
            // });
        }
    });

    // ===========================
    // Custom Validation Methods
    // ===========================
    
    // Phone number validation for Uganda
    $.validator.addMethod("phoneNumber", function(value, element) {
        if (value === "") return true; // Let 'required' handle empty values
        // Accepts formats like: +256XXXXXXXXX, 0XXXXXXXXX, 256XXXXXXXXX
        return /^(\+?256|0)?[0-9]{9,10}$/.test(value.replace(/[\s\-]/g, ''));
    }, "Please enter a valid phone number");

    // ===========================
    // Modal Functions
    // ===========================
    
    window.closeModal = function() {
        const modal = $('#applicationModal');
        modal.addClass('hidden').removeClass('flex');
        $('body').css('overflow', '');
        $('#applicationForm')[0].reset();
        $('#applicationForm').validate().resetForm();
    };

    // ===========================
    // Alert Function
    // ===========================
    
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
        
        // Remove existing alerts
        $('#customAlert').remove();
        
        // Add new alert
        $('body').append(alertHtml);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $('#customAlert').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // ===========================
    // Modal Controls
    // ===========================
    
    $('#joinUsBtn').click(function() {
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

});
