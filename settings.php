<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'تنظیمات';
$message = '';
$error = '';

// ذخیره تنظیمات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $buildingName = trim($_POST['building_name'] ?? '');
        $currentYear = intval($_POST['current_year'] ?? 1403);
        $defaultCharge = intval(str_replace(',', '', $_POST['default_charge'] ?? 500000));

        setSetting('building_name', $buildingName);
        setSetting('current_year', $currentYear);
        setSetting('default_charge', $defaultCharge);

        $message = 'تنظیمات با موفقیت ذخیره شد';
    }

    // تغییر رمز عبور
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $storedPassword = getSetting('admin_password');

        if (!password_verify($currentPassword, $storedPassword)) {
            $error = 'رمز عبور فعلی اشتباه است';
        } elseif (strlen($newPassword) < 4) {
            $error = 'رمز عبور جدید باید حداقل 4 کاراکتر باشد';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'تکرار رمز عبور مطابقت ندارد';
        } else {
            setSetting('admin_password', password_hash($newPassword, PASSWORD_DEFAULT));
            $message = 'رمز عبور با موفقیت تغییر کرد';
        }
    }
}

// خروج
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// دریافت تنظیمات فعلی
$buildingName = getSetting('building_name', 'ساختمان من');
$currentYear = getSetting('current_year', 1403);
$defaultCharge = getSetting('default_charge', 500000);

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">تنظیمات</h1>
    <p class="text-gray-500">مدیریت تنظیمات سیستم</p>
</div>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
    <?= e($message) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
    <?= e($error) ?>
</div>
<?php endif; ?>

<div class="grid md:grid-cols-2 gap-6">
    <!-- تنظیمات عمومی -->
    <div class="bg-white rounded-xl p-6 shadow">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            تنظیمات عمومی
        </h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">نام ساختمان</label>
                <input type="text" name="building_name" value="<?= e($buildingName) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="مثل: ساختمان گلها">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">سال جاری شمسی</label>
                <select name="current_year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?php for ($y = 1400; $y <= 1410; $y++): ?>
                    <option value="<?= $y ?>" <?= $y == $currentYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">مبلغ پیش‌فرض شارژ (تومان)</label>
                <input type="text" name="default_charge" value="<?= number_format($defaultCharge) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    onkeyup="formatMoney(this)">
            </div>
            <button type="submit" name="save_settings"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                ذخیره تنظیمات
            </button>
        </form>
    </div>

    <!-- تغییر رمز عبور -->
    <div class="bg-white rounded-xl p-6 shadow">
        <h2 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            تغییر رمز عبور
        </h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">رمز عبور فعلی</label>
                <input type="password" name="current_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">رمز عبور جدید</label>
                <input type="password" name="new_password" required minlength="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">تکرار رمز عبور جدید</label>
                <input type="password" name="confirm_password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" name="change_password"
                class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                تغییر رمز عبور
            </button>
        </form>
    </div>
</div>

<!-- خروج -->
<div class="mt-6">
    <a href="settings.php?logout=1"
        class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
        </svg>
        خروج از سیستم
    </a>
</div>

<?php include 'includes/footer.php'; ?>
