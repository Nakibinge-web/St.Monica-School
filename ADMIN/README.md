# St. Monica Junior School — Admin Panel & CMS (Phase Three)

Welcome to the **St. Monica Junior School Administration Panel and Content Management System (CMS) — Phase Three: Production Readiness & Operations**.

This unified, high-performance CMS empowers school administrators, communications managers, admissions officers, and non-technical staff to manage website content, process pupil enrollment applications, optimize search engine visibility, organize central media assets, curate testimonials, and now also handle day-to-day operations: notifications, email communication, content scheduling, backups, security monitoring, system health, and data recovery — without modifying code.

Phase Three does not replace Phase One or Two. It extends the same `ADMIN/includes/*` helpers, the same `Database::` PDO wrapper, the same CSRF/RBAC/activity-log patterns, and the same UI design system with new operational modules.

---

## 1. Architectural Overview & Structure

All administration logic, security layers, asset handlers, authentication, and RESTful JSON APIs are strictly isolated inside the `ADMIN/` folder, keeping the public website fully decoupled:

```text
St.monica/
├── index.html                     # Public Homepage (hydrated dynamically)
├── about.html                     # Public About Us page (hydrated with admission steps)
├── contact.html                   # Public Contact & Admissions page (with live Application modal)
├── gallery.html                   # Public Gallery page (hydrated dynamically)
├── academics.html                 # Public Academics page (dynamic SEO tags)
├── curriculum.html                # Public Curriculum page (dynamic SEO tags)
├── cocurricular.html              # Public Co-curricular page (dynamic SEO tags)
├── assets/
│   ├── css/style.css              # Public styling & animations
│   ├── js/form-validation.js      # Public form validation & AJAX admissions submission
│   └── js/cms-client.js           # Public dynamic CMS client (SEO, testimonials, admissions, etc.)
└── ADMIN/                         # Complete CMS & Administration System
    ├── index.php                  # Admin dashboard router & gateway
    ├── README.md                  # Comprehensive technical documentation
    ├── assets/
    │   ├── css/admin.css          # Design system & responsive UI styles
    │   └── js/admin.js            # Zero-dependency WYSIWYG editor, modal controller, image previews
    ├── includes/
    │   ├── config.php             # DB credentials, port detection (3306/3307), base URL helpers
    │   ├── database.php           # PDO Singleton wrapper with prepared statements & transactions
    │   ├── auth.php               # Role-based access control (RBAC), session hardening, permission guards
    │   ├── csrf.php               # Cryptographic CSRF token generation & verification
    │   ├── functions.php          # HTML sanitizer, image optimizer, application number generator, pagination
    │   ├── header.php             # Admin layout header, role badge, user profile link
    │   ├── sidebar.php            # Dynamic navigation with real-time New Admissions badge
    │   └── footer.php             # Delete confirmation modal & layout scripts
    ├── database/
    │   ├── schema.sql             # Unified 18-table relational database schema
    │   ├── seed.sql               # Phase One default seed content & admin account
    │   ├── setup.php              # Automated database migration runner & setup CLI/web tool
    │   └── migrations/            # Incremental Phase Two database migrations:
    │       ├── 001_create_admissions_table.sql
    │       ├── 002_create_admission_info_table.sql
    │       ├── 003_create_testimonials_table.sql
    │       ├── 004_create_media_library_table.sql
    │       ├── 005_create_seo_settings_table.sql
    │       ├── 006_update_admins_and_roles.sql
    │       ├── 007_update_activity_logs.sql
    │       └── 008_seed_phase_two_data.sql
    ├── login/
    │   ├── login.php              # Secure login screen
    │   ├── authenticate.php       # POST credential validator & active status checker
    │   └── logout.php             # Session termination & security flush
    ├── dashboard/
    │   └── index.php              # Enhanced metrics, recent applications, content status breakdown
    ├── admissions/                # Pupil Admissions Management
    │   ├── index.php              # Applications list, search, status & class filters, pagination
    │   ├── view.php               # Application dossier, review status workflow, admin remarks
    │   ├── export.php             # Filtered CSV applicant exporter
    │   └── delete.php             # Safe deletion with CSRF protection and audit logging
    ├── admission-info/            # Admission Information CMS
    │   └── index.php              # 4-step procedure editor, requirements whitelist editor, fees policy
    ├── testimonials/              # Parent & Community Reviews CMS
    │   ├── index.php              # Testimonials list with star ratings and display order
    │   ├── create.php             # New testimonial form with photo upload & preview
    │   ├── edit.php               # Edit testimonial & replace/remove photo
    │   └── delete.php             # Safe deletion and photo cleanup
    ├── media/                     # Central Media Library
    │   ├── index.php              # Media asset grid, alt text indicators, usage tracker, copy link
    │   ├── upload.php             # File uploader with automatic image optimization
    │   ├── edit.php               # Alt text, caption, and title metadata editor
    │   └── delete.php             # Media removal with file unlinking
    ├── preview/                   # Secure Public-Style Preview System
    │   └── index.php              # Live preview for draft and scheduled posts with admin banner
    ├── seo/                       # Website Search Engine Optimization
    │   ├── index.php              # 7-page SEO editor with live Google & Social Share previews
    │   └── update.php             # SEO save handler with validation & activity logging
    ├── users/                     # Administrator User Management
    │   ├── index.php              # Administrator staff list, roles, account status, last active
    │   ├── create.php             # Account provisioning form with role assignment
    │   ├── edit.php               # Edit administrator details, role, status, and password
    │   └── delete.php             # Account deletion with self-deletion & super-admin safeguards
    ├── profile/                   # Admin Profile & Password Self-Service
    │   ├── index.php              # Profile overview card & credential forms
    │   ├── update.php             # Name, email, and phone updater
    │   └── change-password.php    # Current password verification, 8-char validation, bcrypt hashing
    ├── logs/                      # System Activity & Audit Logs
    │   └── index.php              # Filterable audit log viewer with search, module filter, and pagination
    ├── homepage/                  # Phase One Homepage CMS (Hero, Director, Features, Statistics)
    ├── staff/                     # Phase One Staff Directory CMS
    ├── news-events/               # Phase One News & Events CMS (Enhanced with rich-text & scheduled publish)
    ├── gallery/                   # Phase One Photo Gallery CMS
    ├── about/                     # Phase One About Us CMS (History, Mission, Core Values, Facilities)
    ├── contact/                   # Phase One Contact Information CMS
    ├── uploads/                   # Secure filesystem storage for uploaded assets
    ├── storage/backups/           # Auto-protected database backup storage (Phase Three)
    ├── services/                  # Phase Three service classes (Email, Notification, Settings, Backup)
    ├── inquiries/                 # Contact form enquiry management (Phase Three)
    ├── notifications/             # Notification center (Phase Three)
    ├── email-templates/           # Editable transactional email templates (Phase Three)
    ├── announcements/             # Short urgent public notices (Phase Three)
    ├── trash/                     # Soft-delete recovery (Phase Three)
    ├── backups/                   # Database backup, download, and restore (Phase Three)
    ├── security/                  # Security Center (Phase Three)
    ├── system/                    # System & public API health checks (Phase Three)
    ├── settings/                  # Website settings & maintenance mode (Phase Three)
    └── api/                       # RESTful Public APIs
        ├── admissions/apply.php   # Public POST application submission endpoint
        ├── admission-info/        # GET admission procedure, requirements & policies
        ├── testimonials/          # GET published parent reviews
        ├── media/                 # GET media library assets
        ├── seo/                   # GET SEO metadata per page
        ├── homepage/              # GET aggregated homepage content
        ├── staff/                 # GET staff directory
        ├── news-events/           # GET published & scheduled news articles
        ├── gallery/               # GET categorized gallery photos
        ├── about/                 # GET about details, core values, facilities
        └── contact/               # GET school contact information
```

---

## 2. Role-Based Access Control (RBAC)

The system implements strict role-based access control with three primary administrative roles:

| Module / Feature | Super Admin (`super_admin`) | Content Editor (`editor`) | Admissions Manager (`admissions_manager`) |
| :--- | :---: | :---: | :---: |
| **Main Dashboard** | Full Access | Full Access | Admissions-Focused |
| **Pupil Admissions (`/admissions/`)** | Full Access | No Access | Full Access |
| **Admission Info (`/admission-info/`)**| Full Access | No Access | Full Access |
| **Homepage CMS** | Full Access | Full Access | No Access |
| **About Us CMS** | Full Access | Full Access | No Access |
| **Staff Directory** | Full Access | Full Access | No Access |
| **News & Events** | Full Access | Full Access | No Access |
| **Photo Gallery** | Full Access | Full Access | No Access |
| **Testimonials** | Full Access | Full Access | No Access |
| **Central Media Library** | Full Access | Full Access | No Access |
| **Website SEO** | Full Access | Full Access | No Access |
| **School Contact Info** | Full Access | Full Access | No Access |
| **Announcements** | Full Access | Full Access | No Access |
| **Contact Enquiries** | Full Access | Full Access | Full Access |
| **Notification Center** | Full Access | Full Access | Full Access |
| **Email Templates** | Full Access | No Access | No Access |
| **Admin User Management** | Full Access | No Access | No Access |
| **System Audit Logs** | Full Access | No Access | No Access |
| **Trash & Recovery** | Full Access | No Access | No Access |
| **Backups** | Full Access | No Access | No Access |
| **Security Center** | Full Access | No Access | No Access |
| **System Health** | Full Access | No Access | No Access |
| **Website Settings** | Full Access | No Access | No Access |
| **My Profile & Password** | Personal Account | Personal Account | Personal Account |

### Built-in Safeguards
- **Self-Deletion Guard**: Administrators cannot delete their own active account.
- **Sole Super-Admin Guard**: The system prevents demoting or deactivating the last active Super Administrator.
- **Inactive Account Blocking**: Suspended (`inactive`) accounts are denied access immediately at the authentication gateway.
- **Server-Side Module Enforcement (Phase Three)**: Every content module route calls `require_module()`/`require_role()`, not just `require_auth()` — a logged-in Editor or Admissions Manager cannot bypass the sidebar and access another role's module by requesting its URL directly.
- **Login Rate Limiting**: 5 failed sign-in attempts for the same email within 15 minutes triggers a temporary lockout, reusing the existing `activity_logs` table rather than a separate attempts store.
- **Session Revocation**: Super Admins can revoke any other administrator's active session from the Security Center; the revocation is checked on every request and takes effect immediately.
- **Remember-Me Token Security**: Uses the selector/validator pattern — only a hash of the validator is stored, tokens rotate on every use, and a mismatched validator purges all of that admin's tokens (possible theft response).

---

## 3. Admissions Workflow & Public Form Submission

1. **Submission**: Parents fill out the modal application form on `contact.html`.
2. **Client Validation**: Form inputs are verified by jQuery Validation for names, Ugandan phone numbers, class selection, and message length.
3. **API Processing**: `POST /ADMIN/api/admissions/apply.php` validates the payload, generates a unique, sequential application reference (format: `SM-YYYY-NNNN`), inserts the record with status `New`, and logs the action to `activity_logs`.
4. **Immediate Feedback**: The applicant receives their official application reference number immediately in a confirmation alert.
5. **Staff Review**: Admissions officers receive a real-time badge alert on the CMS sidebar (`Admissions (N New)`), review applicant details in `/ADMIN/admissions/view.php`, update the status through the lifecycle (`New` &rarr; `Under Review` &rarr; `Interview Scheduled` &rarr; `Accepted` / `Waitlisted` / `Rejected`), and record internal administrative remarks.
6. **Data Export**: Admissions staff can export filtered applicant lists to CSV format for board reporting and enrollment archives.

---

## 4. Central Media Library & Image Optimization

- **Storage**: Uploaded media is centralized in `ADMIN/uploads/media/` and cataloged in the `media_library` table.
- **Metadata**: Each asset records `title`, `alt_text` (with accessibility missing badges), `caption`, `category`, dimensions (`width x height`), and file size.
- **Optimization Pipeline (`optimize_image()`)**:
  - Automatically downsizes oversized photos exceeding 1920px max width while preserving aspect ratio.
  - Re-encodes JPEG/PNG/WebP files at optimal quality (82%) to reduce bandwidth and enhance load speeds.
  - Automatically generates 320px square thumbnails (`thumb_*`) for snappy admin media grid browsing.
  - Gracefully falls back if GD extension is not enabled without interrupting uploads.
- **Usage Tracking**: The media manager automatically inspects CMS tables (`hero_slides`, `news_events`, `gallery`, `staff`, `facilities`, `seo_settings`) to detect if an asset is currently in active use across the website.
- **Copy Link**: Provides one-click clipboard copying of relative asset URLs for instant insertion into articles or SEO tags.

---

## 5. Rich-Text Editor & Content Security

- **Zero-Dependency WYSIWYG**: `ADMIN/assets/js/admin.js` targets `textarea[data-rich-editor]`, providing formatting toolbars (Heading 2, Heading 3, Bold, Italic, Underline, Bullet Lists, Numbered Lists, Blockquotes, Links, Clear Formatting).
- **Server-Side Sanitization (`sanitize_html()`)**: Content submitted through rich-text editors is cleansed on the backend using an explicit tag whitelist (`<p>`, `<h2>`, `<h3>`, `<h4>`, `<strong>`, `<b>`, `<em>`, `<i>`, `<u>`, `<ul>`, `<ol>`, `<li>`, `<a>`, `<blockquote>`, `<br>`).
- **XSS Immunity**: Strips all dangerous tags (`<script>`, `<iframe>`, `<object>`, `<embed>`, `<style>`, `onload=`, `onerror=`) and forces `rel="noopener noreferrer"` and `target="_blank"` on external hyperlinks.

---

## 6. Draft, Scheduled Publishing & Preview Workflow

- **Status Controls**: News and event articles support `draft` and `published` states.
- **Scheduled Publishing**: Authors can set a future `published_at` timestamp. Public APIs automatically filter out articles where `published_at > NOW()`.
- **Protected Preview**: Authors can click **Preview** at any time to view how drafts or scheduled posts look on the live site via `ADMIN/preview/index.php?type=news&id={id}`. Access requires active admin authentication and displays a floating CMS status toolbar with direct edit links.

---

## 7. Website SEO & Search Snippet Management

- **Page Coverage**: Centralized SEO management for all public pages (`homepage`, `about`, `academics`, `curriculum`, `cocurricular`, `gallery`, `contact`).
- **Metadata Controlled**:
  - Page title & Meta Title (with live 60-character counter)
  - Meta Description (with live 160-character counter)
  - Meta Keywords (comma-delimited)
  - Canonical URL (to prevent duplicate content penalties)
  - Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`) for rich social previews on WhatsApp, Facebook, LinkedIn, and Twitter/X.
- **Interactive Live Preview**: Real-time Google Desktop Search Result card and Social Share Card update dynamically as administrators type.
- **Dynamic Frontend Hydration**: `assets/js/cms-client.js` automatically fetches the corresponding page's SEO metadata from `ADMIN/api/seo/?page={key}` and updates `<title>`, `<meta>`, `<link rel="canonical">`, and Open Graph tags in the browser DOM.

---

## 8. Database Migrations & Installation

### Automatic Migration Execution
The system uses an auto-discovering incremental migration engine in `ADMIN/database/setup.php`:
1. Verifies database connection on port 3306 or 3307.
2. Creates the `st_monica` database if it does not already exist.
3. Imports the core schema and seed data.
4. Reads all `.sql` files in `ADMIN/database/migrations/` in numerical order (`001_...` through `019_...`) and applies pending migrations safely. All migrations are idempotent (`IF NOT EXISTS` / `ON DUPLICATE KEY UPDATE`) and safe to re-run.

### Phase Three Migrations (009–019)
| Migration | Adds |
| :--- | :--- |
| `009_create_admission_notes_table.sql` | Append-only internal notes timeline for applications |
| `010_create_enquiries_table.sql` | Public contact form submissions |
| `011_create_notifications_table.sql` | Admin notification center |
| `012_create_announcements_table.sql` | Short urgent public notices |
| `013_add_expires_at_to_news_events.sql` | Automatic content expiration |
| `014_create_site_settings_table.sql` | Key/value site settings (maintenance mode, pagination, etc.) |
| `015_create_email_templates_table.sql` | Editable transactional email templates |
| `016_add_soft_delete_columns.sql` | `deleted_at` on News & Events, Staff, Gallery, Testimonials (Trash) |
| `017_create_admin_sessions_table.sql` | Active session tracking for the Security Center |
| `018_create_password_resets_table.sql` | Secure password reset tokens |
| `019_create_remember_tokens_table.sql` | Selector/validator "Remember Me" tokens |

### Running Setup
From PowerShell / Command Line:
```powershell
php ADMIN/database/setup.php
```
Or via web browser:
```text
http://localhost/ADMIN/database/setup.php
```

### Default Super Administrator Credentials
- **Email**: `admin@stmonicakasanje.ac.ug`
- **Password**: `Admin@2026!`
- **Role**: `super_admin`

---

## 9. Public RESTful JSON API Reference

| Endpoint | Method | Purpose | Sample Parameters |
| :--- | :---: | :--- | :--- |
| `/ADMIN/api/admissions/apply.php` | `POST` | Public admission application submission | `parentName`, `mobile`, `emailAddress`, `pupilName`, `pupilClass`, `location`, `applicationMessage` |
| `/ADMIN/api/contact/submit.php` | `POST` | Public contact form submission (Phase Three) | `name`, `email`, `phone`, `subject`, `message` |
| `/ADMIN/api/admission-info/` | `GET` | Admission steps, requirements & policies | None |
| `/ADMIN/api/testimonials/` | `GET` | Published parent reviews and ratings | None |
| `/ADMIN/api/media/` | `GET` | Central media library assets | `?category=...&limit=...` |
| `/ADMIN/api/seo/` | `GET` | Search engine & social meta tags | `?page=homepage` (or `about`, `contact`, etc.) |
| `/ADMIN/api/homepage/` | `GET` | Aggregated homepage CMS dataset | None |
| `/ADMIN/api/news-events/` | `GET` | Published news and scheduled events | `?type=news` or `?type=event` |
| `/ADMIN/api/staff/` | `GET` | Staff directory | `?department=...` |
| `/ADMIN/api/gallery/` | `GET` | Categorized gallery photos | `?category=...` |
| `/ADMIN/api/about/` | `GET` | History, mission, core values, facilities | None |
| `/ADMIN/api/contact/` | `GET` | School contact information & coordinates | None |

All endpoints return a standardized JSON format:
```json
{
  "success": true,
  "message": "Operation description",
  "data": { ... },
  "timestamp": "2026-09-18 12:00:00"
}
```

On failure, internal exception detail (SQL text, file paths, stack traces) is **never** echoed to the public. Every public endpoint calls `json_error($exception, 'Generic message')` (`ADMIN/includes/functions.php`), which only includes the raw exception message when `APP_DEBUG=true`.

---

## 10. Phase Three: Advanced Dashboard & Reporting

`ADMIN/dashboard/` now includes, alongside the existing metrics:
- **Date-bucketed admissions counters**: applications this week / month / year.
- **Applications Over Time** chart (last 6 months, real `GROUP BY` data).
- **Application Status Distribution** chart, colored to match the same status colors used throughout the Admissions module.
- **Content Activity** chart, sourced from the existing `activity_logs` table (no synthetic data).

Charts use [Chart.js](https://www.chartjs.org/) via CDN, consistent with the project's existing CDN-based frontend dependencies (Tailwind, jQuery).

## 11. Phase Three: Admissions Improvements

- **Internal Notes Timeline** (`admission_notes` table): append-only notes with admin name + timestamp, replacing the old single overwritable `admin_notes` field (still shown as a "legacy note" for records created before this change).
- **Status History Timeline**: reuses the existing `activity_logs` table (`module = 'admissions'`), avoiding a duplicate audit system.
- **Date-range filtering** added to search/filter and CSV export, alongside the existing search/status/class filters.
- **Optional applicant email notification** on status change, sent via the `application_status_update` email template.

## 12. Phase Three: Communications

- **Contact Enquiries** (`ADMIN/inquiries/`): the public contact form on `contact.html` now actually submits to `ADMIN/api/contact/submit.php` (previously it only validated client-side and went nowhere). Submissions are listed, searchable, filterable by status (`new`/`read`/`replied`/`archived`), and rate-limited (5 per IP per 10 minutes).
- **Notification Center** (`ADMIN/notifications/` + header bell dropdown): triggered on new admission applications and new enquiries. `admin_id = NULL` broadcasts to all admins.
- **Email Service** (`ADMIN/services/EmailService.php`): SMTP via [PHPMailer](https://github.com/PHPMailer/PHPMailer), configured entirely through environment variables (see §14). If `MAIL_HOST` is unset, emails are logged to `activity_logs` instead of sent, so nothing breaks in development.
- **Email Templates** (`ADMIN/email-templates/`, Super Admin only): editable subject/body with `{{placeholder}}` substitution only — no code execution is ever possible through a template.

## 13. Phase Three: Content Scheduling, Expiration & Announcements

- **Automatic Expiration**: News & Events gained an `expires_at` field alongside the existing scheduled-publish `published_at`. Expired posts stop appearing in the public API but remain editable in the admin list with an "Expired" badge — nothing is auto-deleted.
- **Announcements** (`ADMIN/announcements/`): short, urgent notices with a start/end date window, kept visually distinct from full News & Events articles. Active announcements are exposed via the existing `/ADMIN/api/homepage/` endpoint (`announcements` key) rather than a new endpoint.

## 14. Phase Three: Advanced Media Management

- **Search & Filters**: `ADMIN/media/` gained upload-date range filtering alongside the existing search/type/category filters.
- **Usage Tracking** (`get_media_usage()`, `ADMIN/includes/functions.php`): shared by the Media Library, the delete confirmation, and the cleanup tool — a file is never silently deleted while still referenced by hero slides, staff, news, gallery, facilities, SEO social images, or testimonials.
- **Media Cleanup Tool** (`ADMIN/media/cleanup.php`): identifies unused files, missing/broken references, and true duplicate files (by content hash). Nothing is ever deleted automatically — every removal requires an explicit, checked selection and confirmation.

## 15. Phase Three: Trash & Recovery (Soft Delete)

News & Events, Staff, Gallery, and Testimonials use soft deletion (`deleted_at` column) instead of immediate `DELETE`. Deleting an item now hides it from admin lists and public APIs but keeps the record and its uploaded file recoverable.

- `ADMIN/trash/` (Super Admin only) lists everything currently in the trash across all four modules.
- **Restore** clears `deleted_at`, making the item immediately visible again everywhere.
- **Delete Forever** permanently removes the database row and its uploaded file — this cannot be undone.

Soft delete was deliberately *not* applied to every table — only where recovering an accidental deletion has real value.

## 16. Phase Three: Security Center & Login Hardening

`ADMIN/security/` (Super Admin only) surfaces:
- HTTPS status, session security configuration, and password policy.
- Last database backup time and SMTP configuration status.
- **Active administrator sessions**, each with a **Revoke** action that force-signs-out that session on its very next request.
- **Recent failed / blocked sign-in attempts**, sourced from `activity_logs`.

Also new: a full **secure password reset flow** (`ADMIN/login/forgot-password.php` → emailed link → `ADMIN/login/reset-password.php`), single-use expiring tokens, and responses that never reveal whether a given email has an account (anti-enumeration).

## 17. Phase Three: System Health

`ADMIN/system/` (Super Admin only) reports PHP version, database connectivity/version, upload directory writability, GD availability, media storage usage, and disk usage — with clear ✓/⚠/✗ indicators and no internal server configuration exposed.

`ADMIN/system/api-health.php` pings all 10 public JSON API endpoints and reports Operational / Responding with Errors / Unreachable for each, without exposing the underlying error detail.

## 18. Phase Three: Website Settings & Maintenance Mode

`ADMIN/settings/` (Super Admin only, `ADMIN/services/SettingsService.php`) covers General (links to Homepage/SEO), Contact (links to the existing Contact module), Website (maintenance mode, default pagination, date format), and Email (sender name/address — SMTP host/credentials stay in `.env`, never displayed here).

**Maintenance Mode**: because the public site is static HTML with no PHP request gate, maintenance mode is implemented as a settings flag read by every public page via the existing `/ADMIN/api/contact/` call (already fetched on every page load) — when enabled, `assets/js/cms-client.js` replaces the page with a maintenance notice for visitors. The `ADMIN/` panel is architecturally separate from the public pages, so administrators are **never** blocked by this flag.

## 19. Phase Three: Database Backups

`ADMIN/services/BackupService.php` prefers the `mysqldump`/`mysql` CLI tools (credentials passed via the `MYSQL_PWD` environment variable so they never appear in a process list) and automatically falls back to a pure-PHP dumper/importer when those binaries aren't available on the host.

- `ADMIN/backups/` (Super Admin only): create, list, download (authenticated stream, never a direct public link), and delete backups. The storage directory (`ADMIN/storage/backups/` by default) is auto-protected with a generated `.htaccess` denying all direct access.
- **Restore** (`ADMIN/backups/restore.php`) requires typing `RESTORE` exactly, and *always* creates a fresh safety backup of the current database before overwriting it.
- **Scheduled backups**: run `php ADMIN/backups/cli-backup.php` on a schedule using your host's own scheduler — PHP cannot reliably self-schedule:
  - **Windows (XAMPP)**: Task Scheduler → Create Basic Task → Trigger: Daily → Action: `Start a program` → Program: `D:\xampp\php\php.exe` → Arguments: `D:\xampp\htdocs\St.monica\ADMIN\backups\cli-backup.php`.
  - **Linux/shared hosting**: crontab entry, e.g. `0 2 * * * /usr/bin/php /path/to/St.monica/ADMIN/backups/cli-backup.php`.

---

## 20. Environment Configuration (Production Readiness)

Copy `.env.example` (project root) to `.env` and fill in real values. `.env` is git-ignored and must never be committed.

| Variable | Purpose |
| :--- | :--- |
| `APP_ENV` | `production` or `local` — controls default debug behavior |
| `APP_DEBUG` | `true`/`false` — when false (the default in production), public API errors and the login page's dev-credentials hint are both suppressed |
| `APP_TIMEZONE` | Defaults to `Africa/Kampala`; applied via `date_default_timezone_set()` for consistent timestamps across applications, news, activity logs, and scheduling |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Database connection |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | SMTP configuration for `EmailService` |
| `BACKUP_DIR` | Optional custom backup storage path (outside the web root recommended in production) |
| `MYSQLDUMP_BIN`, `MYSQL_BIN` | Optional explicit paths to the `mysqldump`/`mysql` CLI binaries if not on `PATH` |

If `ADMIN/includes/config.php` finds no `.env` file, it falls back to safe production defaults (`APP_ENV=production`, `APP_DEBUG=false`) rather than the old hardcoded `debug = true`.

### Email (SMTP) via Composer/PHPMailer
```powershell
composer install
```
This installs `phpmailer/phpmailer` into `vendor/`. On hosts without SSH/Composer access, the `vendor/` directory is committed to this repository so the CMS works out of the box — `composer install` simply regenerates it if needed.

## 21. Production Deployment Checklist

1. **PHP 8.0+** with `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `curl` extensions (GD recommended for image optimization — checked live on `ADMIN/system/`).
2. **MySQL/MariaDB** database created; run `php ADMIN/database/setup.php` once to apply the schema and all migrations.
3. Copy `.env.example` → `.env`, set `APP_ENV=production`, `APP_DEBUG=false`, and real database/SMTP credentials.
4. Ensure `ADMIN/uploads/` and `ADMIN/storage/backups/` are writable by the web server user.
5. Confirm `.htaccess` protection is in place (already included) for `ADMIN/uploads/` (deny script execution), `ADMIN/database/` (deny `.sql` downloads), `ADMIN/services/` (deny all), and the backups directory (deny all).
6. **Enable HTTPS** — the Security Center will flag it if not detected.
7. Change the default Super Administrator password immediately after first login (or use the new "Forgot Password" flow).
8. Configure a scheduled task for `ADMIN/backups/cli-backup.php` (see §19).
9. Set `APP_TIMEZONE` if the deployment target is not `Africa/Kampala`.
10. Review `ADMIN/security/` and `ADMIN/system/` after deployment to confirm a healthy baseline.

---

© 2026 St. Monica Junior School Kasanje. All rights reserved.
