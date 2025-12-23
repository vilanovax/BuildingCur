<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'مدیریت هزینه‌ها';
$message = '';
$error = '';

// دریافت تنظیمات
$currentYear = getSetting('current_year', 1403);

// انتخاب سال و ماه
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0; // 0 = همه ماه‌ها

// حذف هزینه
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        $message = 'هزینه با موفقیت حذف شد';
    }
}

// افزودن یا ویرایش هزینه
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $amount = intval(str_replace(',', '', $_POST['amount'] ?? 0));
    $year = intval($_POST['year'] ?? $currentYear);
    $month = intval($_POST['month'] ?? 1);
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $edit_id = intval($_POST['edit_id'] ?? 0);

    // پردازش تصویر
    $imageData = null;
    $imageType = null;
    $hasNewImage = false;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            $error = 'فقط تصاویر JPG، PNG، GIF و WebP مجاز هستند';
        } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB
            $error = 'حجم تصویر نباید بیشتر از 5 مگابایت باشد';
        } else {
            $imageData = file_get_contents($_FILES['image']['tmp_name']);
            $imageType = $fileType;
            $hasNewImage = true;
        }
    }

    if (empty($title) || $amount <= 0) {
        $error = 'عنوان و مبلغ الزامی است';
    } elseif (empty($error)) {
        if ($edit_id > 0) {
            if ($hasNewImage) {
                $stmt = $pdo->prepare("UPDATE expenses SET title = ?, amount = ?, year = ?, month = ?, category = ?, description = ?, image_data = ?, image_type = ? WHERE id = ?");
                $stmt->execute([$title, $amount, $year, $month, $category, $description, $imageData, $imageType, $edit_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE expenses SET title = ?, amount = ?, year = ?, month = ?, category = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $amount, $year, $month, $category, $description, $edit_id]);
            }
            $message = 'هزینه با موفقیت ویرایش شد';
        } else {
            $stmt = $pdo->prepare("INSERT INTO expenses (title, amount, year, month, category, description, image_data, image_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $amount, $year, $month, $category, $description, $imageData, $imageType])) {
                $message = 'هزینه با موفقیت ثبت شد';
            }
        }
    }
}

// دریافت هزینه برای ویرایش
$editExpense = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editExpense = $stmt->fetch();
}

// لیست هزینه‌ها
if ($selectedMonth > 0) {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE year = ? AND month = ? ORDER BY id DESC");
    $stmt->execute([$selectedYear, $selectedMonth]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE year = ? ORDER BY month DESC, id DESC");
    $stmt->execute([$selectedYear]);
}
$expenses = $stmt->fetchAll();

// آمار
$statsQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE year = ?";
$statsParams = [$selectedYear];
if ($selectedMonth > 0) {
    $statsQuery .= " AND month = ?";
    $statsParams[] = $selectedMonth;
}
$stmt = $pdo->prepare($statsQuery);
$stmt->execute($statsParams);
$totalExpenses = $stmt->fetchColumn();

// دسته‌بندی‌ها
$categories = ['برق', 'آب', 'گاز', 'نظافت', 'تعمیرات', 'آسانسور', 'باغبانی', 'موتورخانه', 'متفرقه'];

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">مدیریت هزینه‌ها</h1>
    <p class="text-gray-500">ثبت و پیگیری هزینه‌های ساختمان</p>
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
    <h2 class="font-bold text-gray-800 mb-4"><?= $editExpense ? 'ویرایش هزینه' : 'ثبت هزینه جدید' ?></h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <?php if ($editExpense): ?>
        <input type="hidden" name="edit_id" value="<?= $editExpense['id'] ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-medium mb-1">عنوان هزینه *</label>
                <input type="text" name="title" required
                    value="<?= e($editExpense['title'] ?? '') ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="مثل: قبض برق">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">مبلغ (تومان) *</label>
                <input type="text" name="amount" required
                    value="<?= $editExpense ? number_format($editExpense['amount']) : '' ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="500,000"
                    onkeyup="formatMoney(this)">
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">سال</label>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?php for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++): ?>
                    <option value="<?= $y ?>" <?= ($editExpense['year'] ?? $currentYear) == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-medium mb-1">ماه</label>
                <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($persian_months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= ($editExpense['month'] ?? getTodayJalali()[1]) == $num ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-gray-700 text-sm font-medium mb-1">دسته‌بندی</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= ($editExpense['category'] ?? '') == $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-medium mb-1">توضیحات</label>
            <textarea name="description" rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="توضیحات اضافی..."><?= e($editExpense['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-gray-700 text-sm font-medium mb-1">تصویر (اختیاری)</label>
            <div class="flex items-center gap-4">
                <input type="file" name="image" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:ml-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <?php if ($editExpense && $editExpense['image_data']): ?>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-green-600">تصویر موجود است</span>
                    <a href="image.php?id=<?= $editExpense['id'] ?>" target="_blank" class="text-blue-500 hover:text-blue-700 text-sm">مشاهده</a>
                </div>
                <?php endif; ?>
            </div>
            <p class="text-xs text-gray-400 mt-1">فرمت‌های مجاز: JPG، PNG، GIF، WebP - حداکثر 5 مگابایت</p>
        </div>

        <div class="flex gap-2">
            <button type="submit"
                class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                <?= $editExpense ? 'ذخیره تغییرات' : 'ثبت هزینه' ?>
            </button>
            <?php if ($editExpense): ?>
            <a href="expenses.php"
                class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg transition duration-200">
                انصراف
            </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- فیلتر -->
<div class="bg-white rounded-xl p-4 shadow mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-1">سال</label>
            <select name="year" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <?php for ($y = $currentYear - 2; $y <= $currentYear + 1; $y++): ?>
                <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-1">ماه</label>
            <select name="month" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="0">همه ماه‌ها</option>
                <?php foreach ($persian_months as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num == $selectedMonth ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
            فیلتر
        </button>
    </form>
</div>

<!-- مجموع -->
<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6">
    <div class="flex items-center justify-between">
        <span class="text-orange-800 font-medium">مجموع هزینه‌ها:</span>
        <span class="text-xl font-bold text-orange-600"><?= number_format($totalExpenses) ?> تومان</span>
    </div>
</div>

<!-- لیست هزینه‌ها -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <?php if (empty($expenses)): ?>
    <div class="p-8 text-center text-gray-500">
        هزینه‌ای ثبت نشده است
    </div>
    <?php else: ?>
    <div class="divide-y divide-gray-200">
        <?php foreach ($expenses as $expense): ?>
        <div class="p-4 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                    <?php if ($expense['image_data']): ?>
                    <a href="image.php?id=<?= $expense['id'] ?>" target="_blank" class="flex-shrink-0">
                        <img src="image.php?id=<?= $expense['id'] ?>" alt="تصویر" class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                    </a>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="font-medium text-gray-800"><?= e($expense['title']) ?></p>
                            <?php if ($expense['category']): ?>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded"><?= e($expense['category']) ?></span>
                            <?php endif; ?>
                            <?php if ($expense['image_data']): ?>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <p class="text-sm text-gray-500"><?= $persian_months[$expense['month']] ?> <?= $expense['year'] ?></p>
                        <?php if ($expense['description']): ?>
                        <p class="text-sm text-gray-400 mt-1"><?= e($expense['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-lg font-bold text-orange-600"><?= number_format($expense['amount']) ?></span>
                    <div class="flex items-center gap-2">
                        <a href="expenses.php?edit=<?= $expense['id'] ?>" class="text-blue-500 hover:text-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        <a href="expenses.php?delete=<?= $expense['id'] ?>&year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>"
                            onclick="return confirmDelete('آیا از حذف این هزینه مطمئن هستید؟')"
                            class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
