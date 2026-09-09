<?php

return [
    'update_plugin' => 'به‌روزرسانی افزونه',
    'update_theme' => 'به‌روزرسانی قالب',

    'select_plugin' => 'افزونه‌ای که می‌خواهید به‌روز کنید',
    'select_theme' => 'قالبی که می‌خواهید به‌روز کنید',

    'zip_file' => 'فایل ZIP به‌روزرسانی',
    'plugin_zip_helper' => 'فایل ZIP از پیش‌ساخته‌شدهٔ افزونه را بارگذاری کنید. نسخهٔ جدید باید از نسخهٔ نصب‌شده بالاتر باشد مگر اینکه «اجبار» را روشن کنید.',
    'theme_zip_helper' => 'فایل ZIP از پیش‌ساخته‌شدهٔ قالب به همراه پوشهٔ dist/ را بارگذاری کنید. نسخه باید از نسخهٔ نصب‌شده بالاتر باشد مگر اینکه «اجبار» را روشن کنید.',

    'force_toggle' => 'اجبار (لغو کنترل نسخه)',
    'force_helper' => 'اجازهٔ نصب مجدد همان نسخه یا بازگشت به نسخهٔ پایین‌تر. فقط هنگام بازیابی استفاده کنید؛ پیش‌فرض هر دو را رد می‌کند.',

    'plugin_risk_label' => 'دربارهٔ به‌روزرسانی افزونه',
    'plugin_risk_body' => 'نسخهٔ فعلی افزونه پشتیبان‌گیری می‌شود، نسخهٔ جدید جایگزین می‌شود، assetها و ترجمه‌ها منتشر می‌شوند و در پایان مایگریشن‌ها اجرا می‌شود. اگر افزونه فعال باشد فقط وضعیت دیتابیس به installed می‌رود (هوک deactivate اجرا نمی‌شود)؛ باید در درخواست بعدی آن را دستی فعال کنید تا autoloader کد جدید را بارگذاری کند.',

    'theme_risk_label' => 'دربارهٔ به‌روزرسانی قالب',
    'theme_risk_body' => 'نسخهٔ فعلی قالب پشتیبان‌گیری می‌شود، نسخهٔ جدید جایگزین می‌شود و assetها و ترجمه‌ها بازنشر می‌شوند. قالب فعال، فعال می‌ماند و viewها در رندر بعدی به‌روز می‌شوند.',

    'core_title' => 'به‌روزرسانی سیستم',
    'core_navigation_label' => 'به‌روزرسانی سیستم',
    'core_subheading' => 'نسخهٔ فعلی هسته: :version',
    'core_current_version_heading' => 'نسخهٔ فعلی هسته',
    'core_current_version_label' => 'نسخه',
    'core_composer_intro' => 'هستهٔ miran/mksine را با Composer به‌روز کنید تا composer.lock و autoloader هم‌تراز بمانند. جایگزینی vendor/ یا packages/mksine از ZIP پشتیبانی نمی‌شود.',
    'core_path_composer_heading' => 'وقتی Composer روی سرور هست',
    'core_path_composer_body' => 'این دستورها را از ریشهٔ اپلیکیشن اجرا کنید (SSH یا جاب دیپلوی). اگر کش config/route/view فعال است، بعد از آن کش را پاک کنید.',
    'core_cli_equivalent' => 'یا یک دستور که Composer را اجرا می‌کند و مایگریشن‌های پکیج را منتشر می‌کند:',
    'core_composer_missing' => 'Composer روی این سرور پیدا نشد. از مسیر آفلاین زیر استفاده کنید، یا Composer را نصب کنید.',
    'core_console_warning' => 'کنسول ادمین می‌تواند دستور composer اجرا کند، اما آپدیت طولانی ممکن است به timeout HTTP بخورد. SSH مطمئن‌تر است.',
    'core_console_link' => 'باز کردن کنسول',
    'core_path_offline_heading' => 'وقتی Composer روی سرور نیست',
    'core_path_offline_body' => 'روی ماشین build که Composer دارد composer update miran/mksine را اجرا کنید، سپس حداقل این مسیرها را deploy کنید تا lock و autoloader یکی بمانند:',
    'core_path_repo_note' => 'نصب path-repository: اول packages/mksine را به‌روز کنید (مثلاً git pull). composer update آن درخت git را برای شما pull نمی‌کند.',
    'core_release_archive_note' => 'php artisan mks:release-archive کل درخت قابل‌دیپلوی (از جمله vendor/) را برای هاست بدون Composer بسته‌بندی می‌کند. ZIP فقط-هسته نیست.',

    'plugin_update_title' => 'به‌روزرسانی افزونه',
    'plugin_rollback_title' => 'بازگردانی افزونه',
    'theme_update_title' => 'به‌روزرسانی قالب',
    'theme_rollback_title' => 'بازگردانی قالب',

    'upload_failed' => 'بارگذاری ناموفق',
    'invalid_upload' => 'فایل بارگذاری‌شده نامعتبر است یا وجود ندارد.',
    'update_failed' => 'به‌روزرسانی ناموفق بود',

    'result_success_heading' => 'به‌روزرسانی موفق',
    'result_failure_heading' => 'به‌روزرسانی ناموفق',
    'result_versions_label' => 'نسخه‌ها',
    'result_steps_label' => 'مراحل اجراشده',
    'result_warnings_label' => 'هشدارها',
    'result_error_label' => 'خطا',
    'result_log_label' => 'فایل لاگ',
    'result_backup_label' => 'پشتیبان',
    'result_db_dirty_heading' => 'پایگاه داده احتمالاً به‌طور ناقص مایگریت شده است',
    'result_db_dirty_body' => 'یک مایگریشن پس از جایگزینی شکست خورد. هدف اکنون روی کد جدید در حال اجراست اما وضعیت DB مشخص نیست. لاگ را بررسی کنید و مایگریشن‌ها را دستی اجرا کنید یا کد را برگردانید و یک اسنپ‌شات DB را بازیابی کنید.',

    'rollback' => 'بازگردانی',
    'rollback_confirm_title' => 'بازگردانی انجام شود؟',
    'rollback_confirm_body' => 'جدیدترین پشتیبان را بازیابی می‌کند. مایگریشن‌ها بازگردانی نمی‌شوند.',
];
