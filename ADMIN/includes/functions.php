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
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function get_flash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
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
 * Log an administrative activity
 */
function log_activity(string $action, ?string $details = null, ?string $module = null, ?int $recordId = null): void {
    try {
        if (session_status() === PHP_SESSION_NONE) session_start();
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
