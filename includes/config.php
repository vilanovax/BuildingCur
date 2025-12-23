<?php
/**
 * تنظیمات اصلی سیستم مدیریت ساختمان
 */

// تنظیمات دیتابیس
define('DB_HOST', 'atlon.ir');
define('DB_PORT', '3366');
define('DB_NAME', 'buld_db');
define('DB_USER', 'root');
define('DB_PASS', 'dpadba');
define('DB_CHARSET', 'utf8mb4');

// تنظیمات امنیتی
define('ADMIN_PASSWORD', 'admin123'); // رمز ورود - حتما تغییر دهید!
define('SESSION_NAME', 'building_session');

// تنظیمات پیش‌فرض
define('DEFAULT_CHARGE', 500000); // مبلغ پیش‌فرض شارژ (تومان)
define('CURRENT_YEAR', 1403); // سال شمسی جاری

// ماه‌های شمسی
$persian_months = [
    1 => 'فروردین',
    2 => 'اردیبهشت',
    3 => 'خرداد',
    4 => 'تیر',
    5 => 'مرداد',
    6 => 'شهریور',
    7 => 'مهر',
    8 => 'آبان',
    9 => 'آذر',
    10 => 'دی',
    11 => 'بهمن',
    12 => 'اسفند'
];

// شروع سشن
session_name(SESSION_NAME);
session_start();

// اتصال به دیتابیس
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('خطا در اتصال به دیتابیس: ' . $e->getMessage());
}

// توابع کمکی

/**
 * بررسی لاگین بودن کاربر
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * ریدایرکت به صفحه لاگین
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * فرمت کردن مبلغ به تومان
 */
function formatMoney($amount) {
    return number_format($amount) . ' تومان';
}

/**
 * تبدیل تاریخ میلادی به شمسی
 */
function gregorianToJalali($gy, $gm, $gd) {
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return array($jy, $jm, $jd);
}

/**
 * دریافت تاریخ شمسی امروز
 */
function getTodayJalali() {
    $today = getdate();
    return gregorianToJalali($today['year'], $today['mon'], $today['mday']);
}

/**
 * دریافت تنظیمات از دیتابیس
 */
function getSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * ذخیره تنظیمات در دیتابیس
 */
function setSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
    return $stmt->execute([$key, $value, $value]);
}

/**
 * Escape خروجی HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
