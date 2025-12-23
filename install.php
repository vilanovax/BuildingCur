<?php
/**
 * اسکریپت نصب دیتابیس
 * پس از اجرا این فایل را حذف کنید
 */

// تنظیمات دیتابیس
$host = 'atlon.ir';
$port = '3366';
$dbname = 'buld_db';
$username = 'root';
$password = 'dpadba';

try {
    // اتصال بدون انتخاب دیتابیس
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // ایجاد دیتابیس
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    echo "<div dir='rtl' style='font-family: Tahoma; padding: 20px;'>";
    echo "<h2>در حال نصب سیستم مدیریت ساختمان...</h2>";

    // جدول تنظیمات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(100) UNIQUE NOT NULL,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول تنظیمات ایجاد شد</p>";

    // جدول واحدها
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unit_number VARCHAR(20) NOT NULL,
            owner_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            floor INT DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول واحدها ایجاد شد</p>";

    // جدول پرداخت شارژ
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS charges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            unit_id INT NOT NULL,
            year INT NOT NULL,
            month INT NOT NULL,
            amount BIGINT NOT NULL,
            is_paid TINYINT(1) DEFAULT 0,
            paid_at DATETIME,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
            UNIQUE KEY unique_charge (unit_id, year, month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول پرداخت شارژ ایجاد شد</p>";

    // جدول هزینه‌ها
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            amount BIGINT NOT NULL,
            year INT NOT NULL,
            month INT NOT NULL,
            category VARCHAR(100),
            description TEXT,
            image_data LONGBLOB,
            image_type VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول هزینه‌ها ایجاد شد</p>";

    // درج تنظیمات پیش‌فرض
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, value) VALUES (?, ?)");
    $stmt->execute(['current_year', '1403']);
    $stmt->execute(['default_charge', '500000']);
    $stmt->execute(['building_name', 'ساختمان من']);
    $stmt->execute(['admin_password', password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "<p>✅ تنظیمات پیش‌فرض ذخیره شد</p>";

    // درج واحدهای نمونه
    $units = [
        ['1', 'آقای احمدی', '09121234567', 1],
        ['2', 'خانم محمدی', '09131234567', 1],
        ['3', 'آقای رضایی', '09141234567', 2],
        ['4', 'خانم کریمی', '09151234567', 2],
        ['5', 'آقای حسینی', '09161234567', 3],
        ['6', 'خانم علوی', '09171234567', 3],
        ['7', 'آقای موسوی', '09181234567', 4],
        ['8', 'خانم صادقی', '09191234567', 4],
        ['9', 'آقای نوری', '09101234567', 5],
        ['10', 'خانم عباسی', '09111234567', 5],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO units (unit_number, owner_name, phone, floor) VALUES (?, ?, ?, ?)");
    foreach ($units as $unit) {
        $stmt->execute($unit);
    }
    echo "<p>✅ ۱۰ واحد نمونه ایجاد شد</p>";

    echo "<hr>";
    echo "<h3 style='color: green;'>✅ نصب با موفقیت انجام شد!</h3>";
    echo "<p><strong>رمز ورود پیش‌فرض:</strong> admin123</p>";
    echo "<p><a href='login.php' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>ورود به سیستم</a></p>";
    echo "<p style='color: red; margin-top: 20px;'>⚠️ مهم: پس از نصب، این فایل (install.php) را حذف کنید!</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div dir='rtl' style='font-family: Tahoma; padding: 20px; color: red;'>";
    echo "<h2>❌ خطا در نصب</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
