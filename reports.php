<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'گزارشات';

// دریافت تنظیمات
$currentYear = getSetting('current_year', 1403);
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;

// گزارش پرداخت شارژ همه واحدها در سال
$units = $pdo->query("SELECT * FROM units WHERE is_active = 1 ORDER BY CAST(unit_number AS UNSIGNED), unit_number")->fetchAll();

// شارژهای سال
$charges = $pdo->prepare("
    SELECT unit_id, month, is_paid, amount
    FROM charges
    WHERE year = ?
");
$charges->execute([$selectedYear]);
$chargesData = $charges->fetchAll();

// ساخت ماتریس پرداخت
$paymentMatrix = [];
foreach ($chargesData as $charge) {
    $paymentMatrix[$charge['unit_id']][$charge['month']] = [
        'is_paid' => $charge['is_paid'],
        'amount' => $charge['amount']
    ];
}

// آمار کلی سال
$yearStats = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN is_paid = 1 THEN amount ELSE 0 END), 0) as total_paid,
        COALESCE(SUM(CASE WHEN is_paid = 0 THEN amount ELSE 0 END), 0) as total_unpaid,
        COUNT(CASE WHEN is_paid = 1 THEN 1 END) as paid_count,
        COUNT(CASE WHEN is_paid = 0 THEN 1 END) as unpaid_count
    FROM charges
    WHERE year = ?
");
$yearStats->execute([$selectedYear]);
$stats = $yearStats->fetch();

// هزینه‌های سال
$expenseStats = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE year = ?");
$expenseStats->execute([$selectedYear]);
$totalExpenses = $expenseStats->fetchColumn();

// هزینه‌ها به تفکیک ماه
$monthlyExpenses = $pdo->prepare("
    SELECT month, SUM(amount) as total
    FROM expenses
    WHERE year = ?
    GROUP BY month
    ORDER BY month
");
$monthlyExpenses->execute([$selectedYear]);
$expensesByMonth = $monthlyExpenses->fetchAll(PDO::FETCH_KEY_PAIR);

// هزینه‌ها به تفکیک دسته‌بندی
$categoryExpenses = $pdo->prepare("
    SELECT COALESCE(category, 'متفرقه') as category, SUM(amount) as total
    FROM expenses
    WHERE year = ?
    GROUP BY category
    ORDER BY total DESC
");
$categoryExpenses->execute([$selectedYear]);
$expensesByCategory = $categoryExpenses->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">گزارشات</h1>
    <p class="text-gray-500">مشاهده گزارش‌های مالی ساختمان</p>
</div>

<!-- انتخاب سال -->
<div class="bg-white rounded-xl p-4 shadow mb-6">
    <form method="GET" class="flex items-center gap-4">
        <label class="text-gray-700 font-medium">سال:</label>
        <select name="year" onchange="this.form.submit()"
            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            <?php for ($y = $currentYear - 3; $y <= $currentYear + 1; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- آمار کلی -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">دریافتی</p>
        <p class="text-xl font-bold text-green-600"><?= number_format($stats['total_paid']) ?></p>
        <p class="text-xs text-gray-400"><?= $stats['paid_count'] ?> پرداخت</p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">بدهی‌ها</p>
        <p class="text-xl font-bold text-red-600"><?= number_format($stats['total_unpaid']) ?></p>
        <p class="text-xs text-gray-400"><?= $stats['unpaid_count'] ?> معوق</p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">هزینه‌ها</p>
        <p class="text-xl font-bold text-orange-600"><?= number_format($totalExpenses) ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 shadow">
        <p class="text-gray-500 text-sm">تراز</p>
        <?php $balance = $stats['total_paid'] - $totalExpenses; ?>
        <p class="text-xl font-bold <?= $balance >= 0 ? 'text-green-600' : 'text-red-600' ?>">
            <?= number_format(abs($balance)) ?>
            <?= $balance >= 0 ? '+' : '-' ?>
        </p>
    </div>
</div>

<!-- جدول وضعیت پرداخت -->
<div class="bg-white rounded-xl shadow mb-6 overflow-hidden">
    <div class="p-4 bg-gray-50 border-b">
        <h2 class="font-bold text-gray-800">وضعیت پرداخت شارژ سال <?= $selectedYear ?></h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-right font-medium text-gray-600 sticky right-0 bg-gray-50">واحد</th>
                    <?php foreach ($persian_months as $num => $name): ?>
                    <th class="px-2 py-2 text-center font-medium text-gray-600 whitespace-nowrap"><?= mb_substr($name, 0, 3) ?></th>
                    <?php endforeach; ?>
                    <th class="px-3 py-2 text-center font-medium text-gray-600">جمع</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($units as $unit): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 sticky right-0 bg-white">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-100 text-blue-600 rounded text-xs font-bold">
                                <?= e($unit['unit_number']) ?>
                            </span>
                            <span class="text-gray-700 hidden md:inline"><?= e($unit['owner_name']) ?></span>
                        </div>
                    </td>
                    <?php
                    $unitTotal = 0;
                    $unitPaid = 0;
                    foreach ($persian_months as $month => $name):
                        $payment = $paymentMatrix[$unit['id']][$month] ?? null;
                        if ($payment) {
                            $unitTotal += $payment['amount'];
                            if ($payment['is_paid']) $unitPaid += $payment['amount'];
                        }
                    ?>
                    <td class="px-2 py-2 text-center">
                        <?php if ($payment): ?>
                            <?php if ($payment['is_paid']): ?>
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-green-100 text-green-600 rounded-full text-xs">✓</span>
                            <?php else: ?>
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-red-100 text-red-600 rounded-full text-xs">✗</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-300">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td class="px-3 py-2 text-center">
                        <span class="text-xs <?= $unitPaid == $unitTotal && $unitTotal > 0 ? 'text-green-600' : 'text-gray-500' ?>">
                            <?= number_format($unitPaid) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 bg-gray-50 border-t flex items-center gap-4 text-sm">
        <span class="flex items-center gap-1">
            <span class="inline-flex items-center justify-center w-4 h-4 bg-green-100 text-green-600 rounded-full text-xs">✓</span>
            پرداخت شده
        </span>
        <span class="flex items-center gap-1">
            <span class="inline-flex items-center justify-center w-4 h-4 bg-red-100 text-red-600 rounded-full text-xs">✗</span>
            پرداخت نشده
        </span>
        <span class="flex items-center gap-1">
            <span class="text-gray-300">-</span>
            ثبت نشده
        </span>
    </div>
</div>

<!-- گزارش هزینه‌ها -->
<div class="grid md:grid-cols-2 gap-6">
    <!-- هزینه به تفکیک ماه -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 bg-gray-50 border-b">
            <h2 class="font-bold text-gray-800">هزینه‌ها به تفکیک ماه</h2>
        </div>
        <div class="p-4">
            <?php if (empty($expensesByMonth)): ?>
            <p class="text-gray-500 text-center py-4">هزینه‌ای ثبت نشده</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($persian_months as $month => $name): ?>
                <?php $amount = $expensesByMonth[$month] ?? 0; ?>
                <?php if ($amount > 0): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700"><?= $name ?></span>
                    <span class="font-medium text-orange-600"><?= number_format($amount) ?></span>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- هزینه به تفکیک دسته‌بندی -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 bg-gray-50 border-b">
            <h2 class="font-bold text-gray-800">هزینه‌ها به تفکیک نوع</h2>
        </div>
        <div class="p-4">
            <?php if (empty($expensesByCategory)): ?>
            <p class="text-gray-500 text-center py-4">هزینه‌ای ثبت نشده</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($expensesByCategory as $cat): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-700"><?= e($cat['category']) ?></span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 bg-gray-200 rounded-full h-2">
                            <?php $percentage = $totalExpenses > 0 ? ($cat['total'] / $totalExpenses) * 100 : 0; ?>
                            <div class="bg-orange-500 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                        </div>
                        <span class="font-medium text-orange-600 text-sm"><?= number_format($cat['total']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
