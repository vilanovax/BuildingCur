<?php
require_once 'includes/config.php';
requireLogin();

$pageTitle = 'داشبورد';

// دریافت تنظیمات
$currentYear = getSetting('current_year', 1403);
$buildingName = getSetting('building_name', 'ساختمان من');

// آمار کلی
$totalUnits = $pdo->query("SELECT COUNT(*) FROM units WHERE is_active = 1")->fetchColumn();

// شارژهای پرداخت شده این سال
$paidCharges = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM charges WHERE year = ? AND is_paid = 1");
$paidCharges->execute([$currentYear]);
$totalPaid = $paidCharges->fetchColumn();

// شارژهای پرداخت نشده این سال
$unpaidCharges = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM charges WHERE year = ? AND is_paid = 0");
$unpaidCharges->execute([$currentYear]);
$totalUnpaid = $unpaidCharges->fetchColumn();

// هزینه‌های این سال
$expenses = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE year = ?");
$expenses->execute([$currentYear]);
$totalExpenses = $expenses->fetchColumn();

// ماه جاری شمسی
$today = getTodayJalali();
$currentMonth = $today[1];

// وضعیت پرداخت ماه جاری
$monthlyStatus = $pdo->prepare("
    SELECT
        COUNT(CASE WHEN c.is_paid = 1 THEN 1 END) as paid_count,
        COUNT(CASE WHEN c.is_paid = 0 THEN 1 END) as unpaid_count
    FROM units u
    LEFT JOIN charges c ON u.id = c.unit_id AND c.year = ? AND c.month = ?
    WHERE u.is_active = 1
");
$monthlyStatus->execute([$currentYear, $currentMonth]);
$monthStatus = $monthlyStatus->fetch();

// واحدهای بدهکار
$debtors = $pdo->prepare("
    SELECT u.unit_number, u.owner_name, COUNT(*) as unpaid_months, SUM(c.amount) as total_debt
    FROM units u
    JOIN charges c ON u.id = c.unit_id
    WHERE c.is_paid = 0 AND c.year = ? AND u.is_active = 1
    GROUP BY u.id
    ORDER BY total_debt DESC
    LIMIT 5
");
$debtors->execute([$currentYear]);
$topDebtors = $debtors->fetchAll();

// آخرین هزینه‌ها
$recentExpenses = $pdo->prepare("SELECT * FROM expenses WHERE year = ? ORDER BY id DESC LIMIT 5");
$recentExpenses->execute([$currentYear]);
$latestExpenses = $recentExpenses->fetchAll();

include 'includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800"><?= e($buildingName) ?></h1>
    <p class="text-gray-500">سال <?= e($currentYear) ?> - <?= $persian_months[$currentMonth] ?></p>
</div>

<!-- کارت‌های آماری -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">تعداد واحدها</p>
                <p class="text-2xl font-bold text-gray-800"><?= $totalUnits ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">دریافتی امسال</p>
                <p class="text-lg font-bold text-green-600"><?= number_format($totalPaid) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">بدهی‌ها</p>
                <p class="text-lg font-bold text-red-600"><?= number_format($totalUnpaid) ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl p-4 shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">هزینه‌ها</p>
                <p class="text-lg font-bold text-orange-600"><?= number_format($totalExpenses) ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- وضعیت ماه جاری -->
<div class="bg-white rounded-xl p-4 shadow mb-6">
    <h2 class="font-bold text-gray-800 mb-4">وضعیت <?= $persian_months[$currentMonth] ?> <?= $currentYear ?></h2>
    <div class="flex items-center gap-4">
        <div class="flex-1">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-500">پرداخت شده</span>
                <span class="text-green-600"><?= $monthStatus['paid_count'] ?? 0 ?> واحد</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <?php
                $total = ($monthStatus['paid_count'] ?? 0) + ($monthStatus['unpaid_count'] ?? 0);
                $percentage = $total > 0 ? (($monthStatus['paid_count'] ?? 0) / $total) * 100 : 0;
                ?>
                <div class="bg-green-500 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
            </div>
        </div>
        <div class="text-center">
            <span class="text-2xl font-bold text-gray-800"><?= round($percentage) ?>%</span>
        </div>
    </div>
</div>

<!-- دو ستون: بدهکاران و هزینه‌ها -->
<div class="grid md:grid-cols-2 gap-6">
    <!-- بدهکاران -->
    <div class="bg-white rounded-xl p-4 shadow">
        <h2 class="font-bold text-gray-800 mb-4">بدهکاران</h2>
        <?php if (empty($topDebtors)): ?>
            <p class="text-gray-500 text-center py-4">بدهکاری وجود ندارد</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($topDebtors as $debtor): ?>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800">واحد <?= e($debtor['unit_number']) ?></p>
                        <p class="text-sm text-gray-500"><?= e($debtor['owner_name']) ?></p>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-red-600"><?= number_format($debtor['total_debt']) ?></p>
                        <p class="text-xs text-gray-500"><?= $debtor['unpaid_months'] ?> ماه</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- آخرین هزینه‌ها -->
    <div class="bg-white rounded-xl p-4 shadow">
        <h2 class="font-bold text-gray-800 mb-4">آخرین هزینه‌ها</h2>
        <?php if (empty($latestExpenses)): ?>
            <p class="text-gray-500 text-center py-4">هزینه‌ای ثبت نشده</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($latestExpenses as $expense): ?>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-800"><?= e($expense['title']) ?></p>
                        <p class="text-sm text-gray-500"><?= $persian_months[$expense['month']] ?> <?= $expense['year'] ?></p>
                    </div>
                    <p class="font-bold text-orange-600"><?= number_format($expense['amount']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
