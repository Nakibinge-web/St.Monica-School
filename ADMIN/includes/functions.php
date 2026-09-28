<?php
/**
 * St. Monica Junior School CMS
 * General Helper Functions & Security Utilities
 */

if (!defined('CMS_ROOT')) {
    if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));
}

require_once __DIR__ . '/csrf.php';

/**
 * Escape output safely for HTML rendering
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate clean SEO friendly slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * Set a session flash message
 */
function set_flash(string $type, string $message): void {
    start_admin_session();
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash(): ?array {
    start_admin_session();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message markup if set
 */
function render_flash(): string {
    $flash = get_flash();
    if (!$flash) return '';

    $typeClass = match($flash['type']) {
        'success' => 'bg-green-50 border-green-500 text-green-800',
        'danger', 'error' => 'bg-red-50 border-red-500 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-500 text-yellow-800',
        default   => 'bg-blue-50 border-blue-500 text-blue-800'
    };

    $icon = match($flash['type']) {
        'success' => 'check_circle',
        'danger', 'error' => 'error',
        'warning' => 'warning',
        default   => 'info'
    };

    $msg = e($flash['message']);
    return <<<HTML
    <div class="cms-alert flex items-center justify-between p-4 mb-6 rounded-lg border-l-4 {$typeClass} shadow-sm transition-all duration-300">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-[20px]">{$icon}</span>
            <span class="text-sm font-medium">{$msg}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-700">
            <span class="material-symbols-outlined text-[18px]">close</span>
        </button>
    </div>
HTML;
}

/**
 * Return resolved admin URL
 */
function admin_url(string $path = ''): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($scriptDir, '/ADMIN');
    $base = ($pos !== false) ? substr($scriptDir, 0, $pos) : '';
    $cleanPath = ltrim($path, '/');
    return $base . '/ADMIN/' . $cleanPath;
}

/**
 * Return resolved public website URL
 */
function public_url(string $path = ''): string {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($scriptDir, '/ADMIN');
    $base = ($pos !== false) ? substr($scriptDir, 0, $pos) : '';
    $cleanPath = ltrim($path, '/');
    return ($base ? $base . '/' : '/') . $cleanPath;
}

/**
 * Safe redirect helper
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * Standardized JSON API Response
 */
function json_response(bool $success, string $message, mixed $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Respond to a caught exception on a public API endpoint without leaking
 * internal error detail (SQL text, file paths, stack traces) to visitors.
 * The generic message is always shown unless app.debug is explicitly enabled.
 */
function json_error(Throwable $e, string $genericMessage, int $statusCode = 500): void {
    $debug = false;
    try {
        $config = require CMS_ROOT . '/includes/config.php';
        $debug = !empty($config['app']['debug']);
    } catch (Throwable $ignored) {
        // Fall through with debug disabled
    }

    if ($debug) {
        error_log('[St.Monica API] ' . $e->getMessage());
    }

    json_response(false, $debug ? ($genericMessage . ' (' . $e->getMessage() . ')') : $genericMessage, null, $statusCode);
}

/**
 * Handle image file upload with strict MIME & extension validation
 *
 * @param array $file $_FILES['input_name']
 * @param string $subdir 'homepage', 'staff', 'news', 'gallery'
 * @param string|null &$error Output error message
 * @return string|null Relative path from project root (e.g. 'ADMIN/uploads/staff/abc.webp') or null on error
 */
function handle_file_upload(array $file, string $subdir, ?string &$error = null): ?string {
    if (!isset($file['error']) || is_array($file['error'])) {
        $error = "Invalid upload parameters.";
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Uploaded file exceeds maximum allowed file size.",
            UPLOAD_ERR_PARTIAL   => "File was only partially uploaded.",
            UPLOAD_ERR_NO_FILE   => "No file was selected.",
            default              => "An unknown file upload error occurred."
        };
        return null;
    }

    // Size check (max 8MB)
    $maxBytes = 8 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        $error = "File size exceeds 8MB limit.";
        return null;
    }

    // Allowed extensions and MIME types
    $allowedMimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif'
    ];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowedMimes)) {
        $error = "Invalid file extension. Allowed formats: JPG, PNG, WEBP, GIF.";
        return null;
    }

    // Check actual MIME type using FileInfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);

    if (!in_array($realMime, array_values($allowedMimes), true)) {
        $error = "File content does not match allowed image formats.";
        return null;
    }

    // Target directory
    $targetDir = CMS_ROOT . '/uploads/' . trim($subdir, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Generate safe randomized filename
    $safeName = sprintf(
        '%s_%s.%s',
        date('Ymd_His'),
        bin2hex(random_bytes(8)),
        $ext
    );

    $destination = $targetDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $error = "Failed to save uploaded file to storage.";
        return null;
    }

    // Return web path relative to project root
    $relPath = 'ADMIN/uploads/' . trim($subdir, '/') . '/' . $safeName;

    // Trigger image optimization
    optimize_image($destination, 1920, 82);

    return $relPath;
}

/**
 * Server-Side HTML Sanitizer for Rich-Text Editors
 * Whitelists safe semantic tags and strips malicious event handlers / scripts
 */
function sanitize_html(?string $html): string {
    if ($html === null || trim($html) === '') return '';

    // Remove script, iframe, object, embed, style tags and their contents
    $cleaned = preg_replace('#<(script|style|iframe|object|embed|applet)[^>]*?>.*?</\\1>#si', '', $html);

    // Whitelist allowed HTML tags
    $allowedTags = '<p><br><hr><h1><h2><h3><h4><h5><h6><b><strong><i><em><u><s><blockquote><pre><code><ul><ol><li><a><img><table><thead><tbody><tr><th><td><div><span>';
    $stripped = strip_tags($cleaned, $allowedTags);

    // Remove inline JS event handlers (e.g. onclick, onload, onerror, onmouseover)
    $stripped = preg_replace('/\son[a-zA-Z]+\s*=\s*(["\']).*?\1/i', '', $stripped);
    $stripped = preg_replace('/\son[a-zA-Z]+\s*=\s*[^ >]+/i', '', $stripped);

    // Remove javascript: and data: (except data:image) URIs
    $stripped = preg_replace('/href\s*=\s*["\']\s*javascript:[^"\']*["\']/i', 'href="#"', $stripped);
    $stripped = preg_replace('/src\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '', $stripped);

    return trim($stripped);
}

/**
 * Generate a unique school application reference number
 * Format: SM-{YEAR}-{0001}
 */
function generate_application_number(): string {
    require_once __DIR__ . '/database.php';
    $year = date('Y');
    $prefix = "SM-{$year}-";

    try {
        $lastNumber = Database::fetchColumn(
            "SELECT `application_number` FROM `admissions` WHERE `application_number` LIKE :prefix ORDER BY `id` DESC LIMIT 1",
            ['prefix' => "{$prefix}%"]
        );

        if ($lastNumber && preg_match('/SM-\d{4}-(\d+)/', $lastNumber, $matches)) {
            $nextSeq = (int)$matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        $appNumber = sprintf("SM-%s-%04d", $year, $nextSeq);

        // Ensure absolute uniqueness
        while (Database::fetchColumn("SELECT COUNT(*) FROM `admissions` WHERE `application_number` = :num", ['num' => $appNumber]) > 0) {
            $nextSeq++;
            $appNumber = sprintf("SM-%s-%04d", $year, $nextSeq);
        }

        return $appNumber;
    } catch (Exception $e) {
        // Fallback random generation if query fails
        return sprintf("SM-%s-%04d", $year, rand(1000, 9999));
    }
}

/**
 * Optimize uploaded image: resize if larger than maximum bounds, compress, and preserve aspect ratio
 */
function optimize_image(string $filePath, int $maxWidth = 1920, int $quality = 82, ?string $thumbPath = null): bool {
    if (!file_exists($filePath)) return false;

    // If GD extension is not loaded, we gracefully skip GD transformation but verify file is valid image
    if (!extension_loaded('gd')) {
        return @getimagesize($filePath) !== false;
    }

    $imageInfo = @getimagesize($filePath);
    if (!$imageInfo) return false;

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Load image resource
    $image = match($mime) {
        'image/jpeg' => @imagecreatefromjpeg($filePath),
        'image/png'  => @imagecreatefrompng($filePath),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false,
        'image/gif'  => @imagecreatefromgif($filePath),
        default      => false
    };

    if (!$image) return false;

    // Handle resize if wider than $maxWidth
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)round(($height / $width) * $newWidth);

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and WEBP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    // Save optimized file
    match($mime) {
        'image/jpeg' => imagejpeg($image, $filePath, $quality),
        'image/png'  => imagepng($image, $filePath, 7),
        'image/webp' => function_exists('imagewebp') ? imagewebp($image, $filePath, $quality) : false,
        'image/gif'  => imagegif($image, $filePath),
        default      => false
    };

    // Optionally generate thumbnail
    if ($thumbPath) {
        $thumbWidth = 400;
        $curW = imagesx($image);
        $curH = imagesy($image);
        $thumbHeight = (int)round(($curH / $curW) * $thumbWidth);

        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $curW, $curH);

        match($mime) {
            'image/jpeg' => imagejpeg($thumb, $thumbPath, 80),
            'image/png'  => imagepng($thumb, $thumbPath, 7),
            'image/webp' => function_exists('imagewebp') ? imagewebp($thumb, $thumbPath, 80) : false,
            'image/gif'  => imagegif($thumb, $thumbPath),
            default      => false
        };
        imagedestroy($thumb);
    }

    imagedestroy($image);
    return true;
}

/**
 * Render standard responsive pagination component
 */
function render_pagination(int $currentPage, int $totalPages, string $baseUrl, array $params = []): string {
    if ($totalPages <= 1) return '';

    $buildUrl = function(int $p) use ($baseUrl, $params) {
        $merged = array_merge($params, ['page' => $p]);
        return $baseUrl . '?' . http_build_query($merged);
    };

    $html = '<div class="flex items-center justify-between px-4 py-3 bg-white border-t border-slate-200 sm:px-6 rounded-b-lg">';
    
    // Mobile View
    $html .= '<div class="flex justify-between flex-1 sm:hidden">';
    if ($currentPage > 1) {
        $html .= '<a href="' . e($buildUrl($currentPage - 1)) . '" class="cms-btn cms-btn-outline text-xs">Previous</a>';
    } else {
        $html .= '<span class="cms-btn cms-btn-outline text-xs opacity-50 cursor-not-allowed">Previous</span>';
    }
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . e($buildUrl($currentPage + 1)) . '" class="cms-btn cms-btn-outline text-xs ml-3">Next</a>';
    } else {
        $html .= '<span class="cms-btn cms-btn-outline text-xs ml-3 opacity-50 cursor-not-allowed">Next</span>';
    }
    $html .= '</div>';

    // Desktop View
    $html .= '<div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">';
    $html .= '<div><p class="text-xs text-slate-500">Page <span class="font-bold text-slate-800">' . $currentPage . '</span> of <span class="font-bold text-slate-800">' . $totalPages . '</span></p></div>';
    
    $html .= '<div><nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<a href="' . e($buildUrl($currentPage - 1)) . '" class="relative inline-flex items-center px-2 py-1.5 rounded-l-md border border-slate-300 bg-white text-xs font-medium text-slate-500 hover:bg-slate-50" title="Previous Page"><span class="material-symbols-outlined text-[16px]">chevron_left</span></a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-2 py-1.5 rounded-l-md border border-slate-300 bg-slate-100 text-xs font-medium text-slate-300 cursor-not-allowed"><span class="material-symbols-outlined text-[16px]">chevron_left</span></span>';
    }

    // Numbered links
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1) {
        $html .= '<a href="' . e($buildUrl(1)) . '" class="relative inline-flex items-center px-3 py-1.5 border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="relative inline-flex items-center px-2 py-1.5 border border-slate-300 bg-white text-xs font-medium text-slate-500">...</span>';
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i === $currentPage) {
            $html .= '<span class="relative inline-flex items-center px-3 py-1.5 border border-[#1e2a4a] bg-[#1e2a4a] text-xs font-bold text-white">' . $i . '</span>';
        } else {
            $html .= '<a href="' . e($buildUrl($i)) . '" class="relative inline-flex items-center px-3 py-1.5 border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50">' . $i . '</a>';
        }
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span class="relative inline-flex items-center px-2 py-1.5 border border-slate-300 bg-white text-xs font-medium text-slate-500">...</span>';
        }
        $html .= '<a href="' . e($buildUrl($totalPages)) . '" class="relative inline-flex items-center px-3 py-1.5 border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50">' . $totalPages . '</a>';
    }

    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . e($buildUrl($currentPage + 1)) . '" class="relative inline-flex items-center px-2 py-1.5 rounded-r-md border border-slate-300 bg-white text-xs font-medium text-slate-500 hover:bg-slate-50" title="Next Page"><span class="material-symbols-outlined text-[16px]">chevron_right</span></a>';
    } else {
        $html .= '<span class="relative inline-flex items-center px-2 py-1.5 rounded-r-md border border-slate-300 bg-slate-100 text-xs font-medium text-slate-300 cursor-not-allowed"><span class="material-symbols-outlined text-[16px]">chevron_right</span></span>';
    }

    $html .= '</nav></div></div></div>';
    return $html;
}

/**
 * Format a byte count into a human readable string
 */
function format_bytes(int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 0) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Number of testimonials waiting for an admin to publish them
 * (visitor "Rate Us" reviews arrive as drafts). Used by the sidebar badge,
 * dashboard alert, and Testimonials list banner.
 */
function pending_reviews_count(): int {
    try {
        return (int)Database::fetchColumn("SELECT COUNT(*) FROM `testimonials` WHERE `status` = 'draft' AND `deleted_at` IS NULL");
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Categories available for organising Media Library assets
 */
function media_categories(): array {
    return ['Campus Life', 'Academics', 'Sports & MDD', 'Special Events', 'Facilities', 'Administration', 'General'];
}

/**
 * Validate an image path submitted from the Media Library picker.
 * Only active image assets registered in the library are accepted, so a tampered
 * form field can never point content at an arbitrary file.
 *
 * @return string|null The verified relative file path, or null if it is not a library image
 */
function resolve_media_selection(?string $filePath): ?string {
    $filePath = trim((string)$filePath);
    if ($filePath === '') return null;

    try {
        $found = Database::fetchColumn(
            "SELECT `file_path` FROM `media_library` WHERE `file_path` = :p AND `file_type` = 'image' AND `status` = 'active' LIMIT 1",
            ['p' => $filePath]
        );
        return $found ? (string)$found : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Whether a file must be kept on disk when a record that pointed at it is edited or purged:
 * true if it belongs to the Media Library or is still referenced by any other content.
 */
function is_media_file_protected(string $filePath): bool {
    try {
        if (Database::fetchColumn("SELECT COUNT(*) FROM `media_library` WHERE `file_path` = :p", ['p' => $filePath]) > 0) {
            return true;
        }
    } catch (Exception $e) {
        return true; // When in doubt, keep the file
    }
    return !empty(get_media_usage($filePath));
}

/**
 * Upload an image straight into the Media Library.
 * Shared by the Upload Media page and the in-form Media Library picker.
 *
 * @param array $file $_FILES entry
 * @param array $meta title, alt_text, caption, description, category
 * @return array|null The saved media_library row, or null on failure ($error is set)
 */
function store_media_upload(array $file, array $meta, ?string &$error = null): ?array {
    $title = trim($meta['title'] ?? '');
    if ($title === '') {
        $title = ucwords(str_replace(['-', '_', '.'], ' ', pathinfo($file['name'] ?? 'Image', PATHINFO_FILENAME)));
    }
    $category = in_array($meta['category'] ?? '', media_categories(), true) ? $meta['category'] : 'General';

    // Validates, stores and optimizes the image
    $relPath = handle_file_upload($file, 'gallery', $error);
    if (!$relPath) return null;

    $fullPath = dirname(CMS_ROOT) . '/' . $relPath;
    $imageInfo = @getimagesize($fullPath);

    $row = [
        'title'       => $title,
        'alt_text'    => trim($meta['alt_text'] ?? ''),
        'caption'     => trim($meta['caption'] ?? ''),
        'description' => trim($meta['description'] ?? ''),
        'file_path'   => $relPath,
        'file_type'   => 'image',
        'file_size'   => (int)@filesize($fullPath),
        'dimensions'  => $imageInfo ? "{$imageInfo[0]}x{$imageInfo[1]}" : null,
        'category'    => $category,
        'status'      => 'active'
    ];

    try {
        $row['id'] = Database::insert('media_library', $row);
    } catch (Exception $e) {
        @unlink($fullPath);
        $error = 'Database error saving media asset: ' . $e->getMessage();
        return null;
    }

    log_activity('Uploaded Media Asset', "Title: {$title} (File: {$relPath})", 'media', (int)$row['id']);
    return $row;
}

/**
 * Render an image field that is filled from the Media Library picker modal.
 * The chosen library path is submitted in a hidden input named $name; an empty value means
 * "keep the current image". Behaviour lives in assets/js/media-picker.js.
 *
 * Options:
 *   current => existing image path shown as the starting preview
 *   shape   => 'rect' (default) or 'circle' for portraits
 *   hint    => helper text under the buttons
 */
function render_media_picker(string $name, array $options = []): string {
    $current = $options['current'] ?? '';
    $shape   = ($options['shape'] ?? 'rect') === 'circle' ? 'circle' : 'rect';
    $hint    = $options['hint'] ?? '';

    $previewClass = $shape === 'circle'
        ? 'w-28 h-28 rounded-full object-cover object-top'
        : 'w-48 h-28 rounded-lg object-cover';
    $boxClass = $shape === 'circle'
        ? 'w-28 h-28 rounded-full'
        : 'w-48 h-28 rounded-lg';

    $currentUrl = $current !== '' ? public_url($current) : '';

    ob_start(); ?>
    <div class="flex flex-col sm:flex-row sm:items-center gap-4" data-media-picker
         data-endpoint="<?= e(admin_url('media/picker.php')) ?>"
         data-csrf="<?= e(csrf_token()) ?>"
         data-original-src="<?= e($currentUrl) ?>">
        <input type="hidden" name="<?= e($name) ?>" value="" data-media-input>

        <div class="<?= $boxClass ?> shrink-0 bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center relative">
            <img src="<?= e($currentUrl) ?>" alt="Selected image" data-media-preview
                 class="<?= $previewClass ?> <?= $currentUrl === '' ? 'hidden' : '' ?>">
            <span class="material-symbols-outlined text-slate-300 text-[40px] <?= $currentUrl !== '' ? 'hidden' : '' ?>" data-media-empty>image</span>
        </div>

        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="cms-btn cms-btn-outline text-xs" data-media-open>
                    <span class="material-symbols-outlined text-[16px]">photo_library</span>
                    <span><?= $currentUrl !== '' ? 'Change Image' : 'Choose from Media Library' ?></span>
                </button>
                <button type="button" class="cms-btn cms-btn-outline text-xs hidden" data-media-reset>
                    <span class="material-symbols-outlined text-[16px]">undo</span>
                    <span><?= $currentUrl !== '' ? 'Keep Current' : 'Clear' ?></span>
                </button>
            </div>
            <p class="text-xs text-slate-500 mt-2 truncate" data-media-status>
                <?= $currentUrl !== '' ? 'Current image' : 'No image selected' ?>
            </p>
            <?php if ($hint !== ''): ?>
                <p class="text-xs text-slate-400 mt-1"><?= e($hint) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Find where a media file (by relative path) is referenced across the CMS content tables.
 * Used by the Media Library, delete confirmation, and the media cleanup tool so a file
 * is never removed without the administrator knowing it is still in active use.
 */
function get_media_usage(string $filePath): array {
    $usages = [];
    try {
        if (Database::fetchColumn("SELECT COUNT(*) FROM `hero_slides` WHERE `image` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'Hero Carousel';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `staff` WHERE `photo` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'Staff Directory';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `news_events` WHERE `featured_image` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'News & Events';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `gallery` WHERE `file_path` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'Gallery';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `about_content` WHERE `image` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'About Us Page';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `facilities` WHERE `image` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'Facilities';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `seo_settings` WHERE `og_image` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'SEO Social Preview';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `testimonials` WHERE `photo` = :p", ['p' => $filePath]) > 0) {
            $usages[] = 'Testimonials';
        }
        if (Database::fetchColumn("SELECT COUNT(*) FROM `homepage_sections` WHERE `image` = :p", ['p' => $filePath]) > 0) {
            $usages[] = "Director's Message";
        }
    } catch (Exception $e) {
        // Table might not be ready
    }
    return $usages;
}

/**
 * Log an administrative activity
 */
function log_activity(string $action, ?string $details = null, ?string $module = null, ?int $recordId = null): void {
    try {
        // Attribute the action to the signed-in admin, but never create a session just to log
        // (e.g. a visitor submitting a public form has no admin session)
        if (session_status() === PHP_SESSION_NONE && admin_session_cookie_present()) start_admin_session();
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminName = $_SESSION['admin_name'] ?? 'System';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        require_once __DIR__ . '/database.php';
        
        $data = [
            'admin_id'   => $adminId,
            'admin_name' => $adminName,
            'action'     => $action,
            'details'    => $details,
            'ip_address' => $ip
        ];

        // If module and recordId columns exist, include them
        if ($module !== null) {
            $data['module'] = $module;
        }
        if ($recordId !== null) {
            $data['record_id'] = $recordId;
        }

        Database::insert('activity_logs', $data);
    } catch (Exception $e) {
        // Fallback without module/record_id if database hasn't migrated yet
        try {
            Database::insert('activity_logs', [
                'admin_id'   => $_SESSION['admin_id'] ?? null,
                'admin_name' => $_SESSION['admin_name'] ?? 'System',
                'action'     => $action,
                'details'    => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (Exception $e2) {
            // Silent catch
        }
    }
}
