<?php
require_once __DIR__ . '/bootstrap.php';
if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD'); http_response_code(405); exit;
}
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(404); exit; }
try {
    $query = $pdo->prepare('SELECT image_url FROM produits WHERE id = ? AND archive_le IS NULL');
    $query->execute([$id]);
    $filename = $query->fetchColumn();
    if (!is_string($filename) || !preg_match('/\A[a-zA-Z0-9_.-]+\.(?:jpe?g|png|webp)\z/i', $filename)) {
        http_response_code(404); exit;
    }
    $private = preg_match('/\Alot_[a-f0-9]{32}\.(?:jpg|png|webp)\z/', $filename);
    $path = ($private ? app_upload_dir() : __DIR__ . '/img-prod') . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) { http_response_code(404); exit; }
    session_write_close();
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="photo-lot-' . $id . '.' . ($mime === 'image/jpeg' ? 'jpg' : ($mime === 'image/png' ? 'png' : 'webp')) . '"');
    header('Cache-Control: private, no-cache');
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') { readfile($path); }
} catch (Throwable $error) {
    error_log('Lecture de photo impossible : ' . $error->getMessage()); http_response_code(503);
}
