<?php
/**
 * آپدیت دیتابیس - اضافه کردن ستون تصویر به جدول هزینه‌ها
 * پس از اجرا این فایل را حذف کنید
 */
require_once 'includes/config.php';

echo "<div dir='rtl' style='font-family: Tahoma; padding: 20px;'>";
echo "<h2>در حال آپدیت دیتابیس...</h2>";

try {
    // بررسی وجود ستون image_data
    $stmt = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'image_data'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN image_data LONGBLOB AFTER description");
        echo "<p>✅ ستون image_data اضافه شد</p>";
    } else {
        echo "<p>✅ ستون image_data از قبل وجود دارد</p>";
    }

    // بررسی وجود ستون image_type
    $stmt = $pdo->query("SHOW COLUMNS FROM expenses LIKE 'image_type'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE expenses ADD COLUMN image_type VARCHAR(50) AFTER image_data");
        echo "<p>✅ ستون image_type اضافه شد</p>";
    } else {
        echo "<p>✅ ستون image_type از قبل وجود دارد</p>";
    }

    echo "<hr>";
    echo "<h3 style='color: green;'>✅ آپدیت با موفقیت انجام شد!</h3>";
    echo "<p><a href='expenses.php' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>بازگشت به هزینه‌ها</a></p>";
    echo "<p style='color: red; margin-top: 20px;'>⚠️ مهم: پس از آپدیت، این فایل (update_db.php) را حذف کنید!</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ خطا: " . $e->getMessage() . "</p>";
}

echo "</div>";
