<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'مدیریت ساختمان') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        * { font-family: 'Vazirmatn', Tahoma, sans-serif; }
        .nav-active { background-color: #3b82f6; color: white; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php if (isLoggedIn()): ?>
    <!-- نوار ناوبری موبایل -->
    <nav class="bg-white shadow-lg fixed bottom-0 left-0 right-0 z-50 md:top-0 md:bottom-auto">
        <div class="max-w-7xl mx-auto px-2">
            <div class="flex justify-around md:justify-start md:gap-1 py-2">
                <a href="index.php" class="flex flex-col md:flex-row items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'nav-active' : '' ?>">
                    <svg class="w-6 h-6 md:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-xs md:text-sm">خانه</span>
                </a>
                <a href="units.php" class="flex flex-col md:flex-row items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'units.php' ? 'nav-active' : '' ?>">
                    <svg class="w-6 h-6 md:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="text-xs md:text-sm">واحدها</span>
                </a>
                <a href="charges.php" class="flex flex-col md:flex-row items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'charges.php' ? 'nav-active' : '' ?>">
                    <svg class="w-6 h-6 md:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-xs md:text-sm">شارژ</span>
                </a>
                <a href="expenses.php" class="flex flex-col md:flex-row items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'expenses.php' ? 'nav-active' : '' ?>">
                    <svg class="w-6 h-6 md:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                    </svg>
                    <span class="text-xs md:text-sm">هزینه‌ها</span>
                </a>
                <a href="reports.php" class="flex flex-col md:flex-row items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'nav-active' : '' ?>">
                    <svg class="w-6 h-6 md:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-xs md:text-sm">گزارش</span>
                </a>
                <a href="settings.php" class="flex flex-col md:flex-row items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100 <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'nav-active' : '' ?>">
                    <svg class="w-6 h-6 md:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="text-xs md:text-sm">تنظیمات</span>
                </a>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- محتوای اصلی -->
    <main class="<?= isLoggedIn() ? 'pb-20 md:pt-20 md:pb-4' : '' ?> px-4 py-6 max-w-7xl mx-auto">
