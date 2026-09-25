-- Migration 015: Create Email Templates Table
CREATE TABLE IF NOT EXISTS `email_templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `template_key` VARCHAR(100) NOT NULL UNIQUE,
    `label` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `body_html` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email_templates_key` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `email_templates` (`template_key`, `label`, `subject`, `body_html`) VALUES
('new_application_staff', 'New Application (Staff Alert)', 'New Admission Application: {{application_number}}', '<p>A new admission application has been submitted.</p><p><strong>Application Number:</strong> {{application_number}}<br><strong>Pupil Name:</strong> {{pupil_name}}<br><strong>Class Applied For:</strong> {{pupil_class}}<br><strong>Parent/Guardian:</strong> {{parent_name}}</p><p>Please review it in the {{school_name}} admin panel.</p>'),
('application_received', 'Application Received', 'We Received Your Application — {{application_number}}', '<p>Dear {{parent_name}},</p><p>Thank you for applying to {{school_name}}. Your application for <strong>{{pupil_name}}</strong> has been received.</p><p>Your application reference number is <strong>{{application_number}}</strong>. Please keep it for your records.</p><p>Our admissions team will be in touch soon.</p>'),
('application_status_update', 'Application Status Update', 'Update on Application {{application_number}}', '<p>Dear {{parent_name}},</p><p>Your application <strong>{{application_number}}</strong> for {{pupil_name}} has been updated to: <strong>{{application_status}}</strong>.</p><p>If you have any questions, please contact the {{school_name}} admissions office.</p>'),
('new_enquiry_staff', 'New Contact Enquiry (Staff Alert)', 'New Website Enquiry from {{enquiry_name}}', '<p>A new contact enquiry has been submitted on the website.</p><p><strong>Name:</strong> {{enquiry_name}}<br><strong>Email:</strong> {{enquiry_email}}<br><strong>Subject:</strong> {{enquiry_subject}}</p><p>Please review it in the {{school_name}} admin panel.</p>'),
('password_reset', 'Password Reset', 'Reset Your {{school_name}} Admin Password', '<p>Hello {{admin_name}},</p><p>We received a request to reset your administrator password. Click the link below to choose a new password. This link expires in 60 minutes.</p><p><a href="{{reset_url}}">{{reset_url}}</a></p><p>If you did not request this, you can safely ignore this email.</p>'),
('admin_invitation', 'Admin Invitation', 'You have been added to the {{school_name}} Admin Panel', '<p>Hello {{admin_name}},</p><p>An administrator account has been created for you on the {{school_name}} CMS with the role of <strong>{{admin_role}}</strong>.</p><p>Login URL: {{login_url}}</p>')
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
