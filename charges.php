<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'مدیریت شارژ';
$message = '';
$error = '';

// دریافت تنظیمات
$currentYear = getSetting('current_year', 1403);
$defaultCharge = getSetting('default_charge', 500000);

// انتخاب سال و ماه
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : getTodayJalali()[1];

// تغییر وضعیت پرداخت
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE charges SET is_paid = NOT is_paid, paid_at = IF(is_paid = 0, NOW(), NULL) WHERE id = ?");
    if ($stmt->execute([$_GET['toggle']])) {
        $message = 'وضعیت پرداخت تغییر کرد';
    }
    header("Location: charges.php?year=$selectedYear&month=$selectedMonth");
    exit;
}

// تابع تبدیل ارقام فارسی به انگلیسی
function convertPersianNumbers($string) {
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persianDigits, $englishDigits, $string);
}

// ایجاد شارژ برای همه واحدها در یک ماه
if (isset($_POST['generate_charges'])) {
    $month = intval($_POST['month']);
    $year = intval($_POST['year']);
    $amountRaw = convertPersianNumbers($_POST['amount'] ?? '0');
    $amount = intval(preg_replace('/[^0-9]/', '', $amountRaw));

    $units = $pdo->query("SELECT id FROM units WHERE is_active = 1")->fetchAll();
    $stmt = $pdo->prepare("INSERT IGNORE INTO charges (unit_id, year, month, amount, is_paid) VALUES (?, ?, ?, ?, 0)");

    $count = 0;
    foreach ($units as $unit) {
        if ($stmt->execute([$unit['id'], $year, $month, $amount])) {
            $count += $stmt->rowCount();
        }
    }

    $message = "$count شارژ جدید ایجاد شد";
    $selectedMonth = $month;
    $selectedYear = $year;
}

// ویرایش مبلغ شارژ
if (isset($_POST['update_charge'])) {
    $chargeId = intval($_POST['charge_id']);
    $amountRaw = convertPersianNumbers($_POST['amount'] ?? '0');
    $amount = intval(preg_replace('/[^0-9]/', '', $amountRaw));
    $notes = trim($_POST['notes'] ?? '');

    $stmt = $pdo->prepare("UPDATE charges SET amount = ?, notes = ? WHERE id = ?");
    if ($stmt->execute([$amount, $notes, $chargeId])) {
        $message = 'شارژ با موفقیت ویرایش شد';
    }
}

// دریافت لیست شارژها
$charges = $pdo->prepare("
    SELECT c.*, u.unit_number, u.owner_name, u.phone
    FROM charges c
    JOIN units u ON c.unit_id = u.id
    WHERE c.year = ? AND c.month = ?
    ORDER BY CAST(u.unit_number AS UNSIGNED), u.unit_number
");
$charges->execute([$selectedYear, $selectedMonth]);
$chargesList = $charges->fetchAll();

// واحدهایی که شارژ ندارند
$unitsWithoutCharge = $pdo->prepare("
    SELECT u.*
    FROM units u
    LEFT JOIN charges c ON u.id = c.unit_id AND c.year = ? AND c.month = ?
    WHERE u.is_active = 1 AND c.id IS NULL
");
$unitsWithoutCharge->execute([$selectedYear, $selectedMonth]);
$missingUnits = $unitsWithoutCharge->fetchAll();

// آمار
$stats = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_count,
        SUM(CASE WHEN is_paid = 1 THEN amount ELSE 0 END) as paid_amount,
        SUM(CASE WHEN is_paid = 0 THEN amount ELSE 0 END) as unpaid_amount
    FROM charges
    WHERE year = ? AND month = ?
");
$stats->execute([$selectedYear, $selectedMonth]);
$monthStats = $stats->fetch();

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">مدیریت شارژ</h1>
    <p class="text-gray-500">ثبت و پیگیری پرداخت شارژ ماهیانه</p>
</div>

<?php if ($message): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
    <?= e($message) ?>
</div>
<?php endif; ?>

<!-- انتخاب ماه و سال -->
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
                <?php foreach ($persian_months as $num => $name): ?>
                <option value="<?= $num ?>" <?= $num == $selectedMonth ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
            نمایش
        </button>
    </form>
</div>

<!-- آمار ماه -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">کل شارژها</p>
        <p class="text-xl font-bold text-gray-800"><?= $monthStats['total'] ?? 0 ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">پرداخت شده</p>
        <p class="text-xl font-bold text-green-600"><?= $monthStats['paid_count'] ?? 0 ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">پرداخت نشده</p>
        <p class="text-xl font-bold text-red-600"><?= $monthStats['unpaid_count'] ?? 0 ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">مجموع دریافتی</p>
        <p class="text-lg font-bold text-green-600"><?= number_format($monthStats['paid_amount'] ?? 0) ?></p>
    </div>
</div>

<!-- ایجاد شارژ جدید -->
<?php if (!empty($missingUnits)): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
    <h3 class="font-bold text-yellow-800 mb-2"><?= count($missingUnits) ?> واحد بدون شارژ</h3>
    <form method="POST" class="flex flex-wrap items-end gap-4">
        <input type="hidden" name="year" value="<?= $selectedYear ?>">
        <input type="hidden" name="month" value="<?= $selectedMonth ?>">
        <div>
            <label class="block text-gray-700 text-sm font-medium mb-1">مبلغ شارژ</label>
            <input type="text" name="amount" value="<?= number_format($defaultCharge) ?>"
                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                onkeyup="formatMoney(this)">
        </div>
        <button type="submit" name="generate_charges"
            class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg">
            ایجاد شارژ برای همه
        </button>
    </form>
</div>
<?php endif; ?>

<!-- لیست شارژها -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="p-4 bg-gray-50 border-b">
        <h2 class="font-bold text-gray-800"><?= $persian_months[$selectedMonth] ?> <?= $selectedYear ?></h2>
    </div>

    <?php if (empty($chargesList)): ?>
    <div class="p-8 text-center text-gray-500">
        شارژی برای این ماه ثبت نشده است
    </div>
    <?php else: ?>
    <div class="divide-y divide-gray-200">
        <?php foreach ($chargesList as $charge): ?>
        <div class="p-4 hover:bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-10 h-10 bg-blue-100 text-blue-600 rounded-full font-bold">
                        <?= e($charge['unit_number']) ?>
                    </span>
                    <div>
                        <p class="font-medium text-gray-800"><?= e($charge['owner_name']) ?></p>
                        <p class="text-sm text-gray-500"><?= number_format($charge['amount']) ?> تومان</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="charges.php?toggle=<?= $charge['id'] ?>&year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>"
                        class="<?= $charge['is_paid'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> px-3 py-1 rounded-full text-sm font-medium">
                        <?= $charge['is_paid'] ? 'پرداخت شده' : 'پرداخت نشده' ?>
                    </a>
                    <button onclick="editCharge(<?= htmlspecialchars(json_encode($charge)) ?>)"
                        class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <?php if ($charge['notes']): ?>
            <p class="text-sm text-gray-500 mt-2 mr-13"><?= e($charge['notes']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- مودال ویرایش -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="font-bold text-gray-800 mb-4">ویرایش شارژ</h3>
        <form method="POST">
            <input type="hidden" name="charge_id" id="editChargeId">
            <div class="space-y-4">
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">مبلغ</label>
                    <input type="text" name="amount" id="editAmount"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        onkeyup="formatMoney(this)">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">یادداشت</label>
                    <textarea name="notes" id="editNotes" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit" name="update_charge"
                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
                    ذخیره
                </button>
                <button type="button" onclick="closeEditModal()"
                    class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-lg">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editCharge(charge) {
    document.getElementById('editChargeId').value = charge.id;
    document.getElementById('editAmount').value = parseInt(charge.amount).toLocaleString('fa-IR');
    document.getElementById('editNotes').value = charge.notes || '';
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php include 'includes/footer.php'; ?>
