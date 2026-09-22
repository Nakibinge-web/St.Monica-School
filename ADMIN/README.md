# St. Monica Junior School — Admin Panel & CMS (Phase Two)

Welcome to the **St. Monica Junior School Administration Panel and Content Management System (CMS) — Phase Two**.

This unified, high-performance CMS empowers school administrators, communications managers, admissions officers, and non-technical staff to manage website content, process pupil enrollment applications, optimize search engine visibility, organize central media assets, and curate testimonials without modifying code.

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
| **Admin User Management** | Full Access | No Access | No Access |
| **System Audit Logs** | Full Access | No Access | No Access |
| **My Profile & Password** | Personal Account | Personal Account | Personal Account |

### Built-in Safeguards
- **Self-Deletion Guard**: Administrators cannot delete their own active account.
- **Sole Super-Admin Guard**: The system prevents demoting or deactivating the last active Super Administrator.
- **Inactive Account Blocking**: Suspended (`inactive`) accounts are denied access immediately at the authentication gateway.

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
4. Reads all `.sql` files in `ADMIN/database/migrations/` in numerical order (`001_...` through `008_...`) and applies pending migrations safely.

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

---

© 2026 St. Monica Junior School Kasanje. All rights reserved.
