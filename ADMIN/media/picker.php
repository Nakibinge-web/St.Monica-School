<?php
/**
 * St. Monica Junior School CMS - Media Library Picker Endpoint
 * Backs the "Choose from Media Library" modal used by image fields across the admin panel,
 * and receives each file from the bulk uploader on media/upload.php (one request per image).
 *
 * GET  ?search=&category=&page=  -> paginated list of active image assets
 * POST (multipart: media_file, title, alt_text, category, csrf_token) -> upload into the library
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';

// JSON-friendly guards (require_module() would redirect to an HTML page)
if (!is_logged_in()) {
    json_response(false, 'Your session has expired. Please sign in again.', null, 401);
}
if (!can_manage('media')) {
    json_response(false, 'You do not have permission to use the media library.', null, 403);
}

// Upload a new image straight into the library
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf_token()) {
        json_response(false, 'Invalid or expired security token. Please refresh the page and try again.', null, 403);
    }
    if (empty($_FILES['media_file']['name'])) {
        json_response(false, 'Please select an image to upload.', null, 422);
    }

    $uploadError = null;
    $row = store_media_upload($_FILES['media_file'], [
        'title'    => $_POST['title'] ?? '',
        'alt_text' => $_POST['alt_text'] ?? '',
        'category' => $_POST['category'] ?? 'General',
    ], $uploadError);

    if (!$row) {
        json_response(false, 'Upload failed: ' . $uploadError, null, 422);
    }

    json_response(true, "'{$row['title']}' was added to the media library.", [
        'id'         => (int)$row['id'],
        'title'      => $row['title'],
        'alt_text'   => $row['alt_text'],
        'file_path'  => $row['file_path'],
        'url'        => public_url($row['file_path']),
        'dimensions' => $row['dimensions'],
        'category'   => $row['category'],
    ]);
}

// List library images
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 24;

$where  = ["`file_type` = 'image'", "`status` = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(`title` LIKE :s1 OR `alt_text` LIKE :s2 OR `caption` LIKE :s3 OR `file_path` LIKE :s4)";
    $params += ['s1' => "%{$search}%", 's2' => "%{$search}%", 's3' => "%{$search}%", 's4' => "%{$search}%"];
}
if ($category !== '') {
    $where[] = "`category` = :cat";
    $params['cat'] = $category;
}

$whereSql = implode(' AND ', $where);
$offset   = ($page - 1) * $perPage;

try {
    $total = (int)Database::fetchColumn("SELECT COUNT(*) FROM `media_library` WHERE {$whereSql}", $params);
    $rows  = Database::fetchAll(
        "SELECT `id`, `title`, `alt_text`, `file_path`, `dimensions`, `category`
         FROM `media_library` WHERE {$whereSql}
         ORDER BY `id` DESC LIMIT {$perPage} OFFSET {$offset}",
        $params
    );
} catch (Exception $e) {
    json_response(false, 'Could not load the media library.', null, 500);
}

$items = array_map(fn($r) => [
    'id'         => (int)$r['id'],
    'title'      => $r['title'],
    'alt_text'   => $r['alt_text'],
    'file_path'  => $r['file_path'],
    'url'        => public_url($r['file_path']),
    'dimensions' => $r['dimensions'],
    'category'   => $r['category'],
], $rows);

json_response(true, 'Media library images retrieved.', [
    'items'      => $items,
    'page'       => $page,
    'has_more'   => ($offset + count($items)) < $total,
    'total'      => $total,
    'categories' => media_categories(),
]);
