<?php
/**
 * نمایش تصویر از دیتابیس
 */
require_once 'includes/config.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(404);
    exit('تصویر یافت نشد');
}

$stmt = $pdo->prepare("SELECT image_data, image_type FROM expenses WHERE id = ? AND image_data IS NOT NULL");
$stmt->execute([$id]);
$image = $stmt->fetch();

if (!$image || empty($image['image_data'])) {
    http_response_code(404);
    exit('تصویر یافت نشد');
}

// تنظیم هدرهای HTTP
header('Content-Type: ' . $image['image_type']);
header('Content-Length: ' . strlen($image['image_data']));
header('Cache-Control: public, max-age=86400'); // کش 1 روز

echo $image['image_data'];
