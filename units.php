<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'مدیریت واحدها';
$message = '';
$error = '';

// حذف واحد
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM units WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        $message = 'واحد با موفقیت حذف شد';
    } else {
        $error = 'خطا در حذف واحد';
    }
}

// افزودن یا ویرایش واحد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_number = trim($_POST['unit_number'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $floor = intval($_POST['floor'] ?? 1);
    $edit_id = intval($_POST['edit_id'] ?? 0);

    if (empty($unit_number) || empty($owner_name)) {
        $error = 'شماره واحد و نام مالک الزامی است';
    } else {
        if ($edit_id > 0) {
            // ویرایش
            $stmt = $pdo->prepare("UPDATE units SET unit_number = ?, owner_name = ?, phone = ?, floor = ? WHERE id = ?");
            if ($stmt->execute([$unit_number, $owner_name, $phone, $floor, $edit_id])) {
                $message = 'واحد با موفقیت ویرایش شد';
            } else {
                $error = 'خطا در ویرایش واحد';
            }
        } else {
            // افزودن
            $stmt = $pdo->prepare("INSERT INTO units (unit_number, owner_name, phone, floor) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$unit_number, $owner_name, $phone, $floor])) {
                $message = 'واحد با موفقیت اضافه شد';
            } else {
                $error = 'خطا در افزودن واحد';
            }
        }
    }
}

// دریافت واحد برای ویرایش
$editUnit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM units WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUnit = $stmt->fetch();
}

// لیست واحدها
$units = $pdo->query("SELECT * FROM units WHERE is_active = 1 ORDER BY CAST(unit_number AS UNSIGNED), unit_number")->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">مدیریت واحدها</h1>
    <p class="text-gray-500">افزودن، ویرایش و حذف واحدهای ساختمان</p>
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

<!-- فرم افزودن/ویرایش -->
<div class="bg-white rounded-xl p-4 shadow mb-6">
    <h2 class="font-bold text-gray-800 mb-4"><?= $editUnit ? 'ویرایش واحد' : 'افزودن واحد جدید' ?></h2>
    <form method="POST" class="space-y-4">
        <?php if ($editUnit): ?>
        <input type="hidden" name="edit_id" value="<?= $editUnit['id'] ?>">
        <?php endif; ?>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">شماره واحد *</label>
                <input type="text" name="unit_number" required
                    value="<?= e($editUnit['unit_number'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="مثل: 1">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">نام مالک *</label>
                <input type="text" name="owner_name" required
                    value="<?= e($editUnit['owner_name'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="نام و نام خانوادگی">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">تلفن</label>
                <input type="tel" name="phone"
                    value="<?= e($editUnit['phone'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="09121234567">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">طبقه</label>
                <input type="number" name="floor" min="0"
                    value="<?= e($editUnit['floor'] ?? 1) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                <?= $editUnit ? 'ذخیره تغییرات' : 'افزودن واحد' ?>
            </button>
            <?php if ($editUnit): ?>
            <a href="units.php"
                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition duration-200">
                انصراف
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- لیست واحدها -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">واحد</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">مالک</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600 hidden md:table-cell">تلفن</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600 hidden md:table-cell">طبقه</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($units)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        واحدی ثبت نشده است
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($units as $unit): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full font-bold">
                            <?= e($unit['unit_number']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800"><?= e($unit['owner_name']) ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell" dir="ltr"><?= e($unit['phone'] ?: '-') ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell"><?= $unit['floor'] ?></td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="units.php?edit=<?= $unit['id'] ?>"
                                class="text-blue-500 hover:text-blue-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <a href="units.php?delete=<?= $unit['id'] ?>"
                                onclick="return confirmDelete('آیا از حذف این واحد مطمئن هستید؟')"
                                class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
