<?php

declare(strict_types=1);

return [
    'navigation_group' => 'موقعیت‌ها',

    'settings' => [
        'tab' => 'موقعیت‌ها و آدرس',
        'section' => 'کشورها و فرم آدرس',
        'section_intro' => 'کشورهای فعال در سیستم و نحوهٔ نمایش استان/شهر در فرم‌های آدرس (چک‌اوت، پروفایل و …).',
        'enabled_countries' => 'کشورهای فعال',
        'enabled_countries_helper' => 'خالی = همه کشورهای موجود. فقط این کدهای ISO2 در فرم‌ها و مدیریت موقعیت نمایش داده می‌شوند.',
        'default_country' => 'کشور پیش‌فرض',
        'default_country_helper' => 'کشور پیش‌انتخاب‌شده در فرم آدرس جدید.',
        'address_levels' => 'نمایش فیلدهای آدرس',
        'address_levels_helper' => 'برای هر کشور: نمایش استان و/یا شهر. در غیر این صورت مشتری متن وارد می‌کند.',
        'address_level_country' => 'کشور',
        'address_level_show_state' => 'نمایش استان',
        'address_level_show_city' => 'نمایش شهر',
    ],

    'countries' => [
        'navigation_label' => 'کشورها',
        'model_label' => 'کشور',
        'plural_model_label' => 'کشورها',
        'iso2' => 'ISO2',
        'iso3' => 'ISO3',
        'name' => 'نام',
        'native' => 'نام بومی',
        'is_active' => 'فعال',
    ],

    'states' => [
        'navigation_label' => 'استان‌ها',
        'model_label' => 'استان',
        'plural_model_label' => 'استان‌ها',
        'country' => 'کشور',
        'code' => 'کد',
        'name' => 'نام',
        'native' => 'نام بومی',
        'is_visible' => 'نمایش در فرم‌ها',
        'source' => 'منبع',
        'cities' => 'شهرها',
    ],

    'cities' => [
        'model_label' => 'شهر',
        'plural_model_label' => 'شهرها',
        'relation_help' => 'شهرهای این استان را می‌توانید جستجو، ویرایش یا (در صورت manual) اضافه و حذف کنید.',
        'name' => 'نام',
        'native' => 'نام بومی',
        'is_visible' => 'نمایش در فرم‌ها',
        'source' => 'منبع',
        'cannot_delete_seed' => 'شهرهای seed شده قابل حذف نیستند.',
        'cannot_delete_in_use' => 'این شهر در جای دیگری استفاده شده و قابل حذف نیست.',
    ],
];
