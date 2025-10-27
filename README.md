# Multi-Database Manager for WordPress & WooCommerce

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)

یک افزونه قدرتمند برای جداسازی ووکامرس و پست تایپ‌ها به دیتابیس‌های مجزا جهت بهینه‌سازی عملکرد و مقیاس‌پذیری.

## 📋 فهرست مطالب

- [ویژگی‌های کلیدی](#ویژگی‌های-کلیدی)
- [نیازمندی‌ها](#نیازمندی‌ها)
- [نصب](#نصب)
- [راهنمای سریع](#راهنمای-سریع)
- [مستندات کامل](#مستندات-کامل)
- [سوالات متداول](#سوالات-متداول)
- [عیب‌یابی](#عیب‌یابی)
- [مشارکت](#مشارکت)
- [لایسنس](#لایسنس)

---

## ✨ ویژگی‌های کلیدی

### 🎯 جداسازی ووکامرس
- ✅ جداسازی خودکار تمام جداول ووکامرس با یک کلیک
- ✅ پشتیبانی کامل از محصولات، سفارشات، مشتریان و Analytics
- ✅ حفظ تمامی روابط بین جداول

### 🗄️ مدیریت چندگانه دیتابیس
- ✅ افزودن نامحدود دیتابیس
- ✅ تخصیص پست تایپ‌های دلخواه به هر دیتابیس
- ✅ فعال/غیرفعال کردن آسان دیتابیس‌ها

### 🚀 انتقال داده‌های هوشمند
- ✅ انتقال Batch Processing برای حجم بالای داده
- ✅ Progress Bar لحظه‌ای
- ✅ پشتیبان‌گیری خودکار قبل از انتقال
- ✅ Rollback در صورت بروز خطا
- ✅ حالت Dry Run برای تست

### 🛡️ امنیت و پایداری
- ✅ رمزنگاری پسوردهای دیتابیس
- ✅ اعتبارسنجی کامل ورودی‌ها
- ✅ سیستم لاگ‌گیری جامع
- ✅ بررسی سلامت دیتابیس‌ها

### 💻 رابط کاربری مدرن
- ✅ طراحی Bootstrap 5
- ✅ پشتیبانی کامل از RTL
- ✅ Responsive و موبایل‌پسند
- ✅ داشبورد زیبا با آمار لحظه‌ای

---

## 📦 نیازمندی‌ها

### حداقل نیازمندی‌ها
- **WordPress:** نسخه 6.0 یا بالاتر
- **PHP:** نسخه 8.0 یا بالاتر
- **MySQL:** نسخه 5.7 یا بالاتر / MariaDB 10.3+
- **حافظه PHP:** حداقل 128MB (256MB پیشنهادی)
- **WooCommerce:** نسخه 8.0+ (اختیاری، فقط برای جداسازی ووکامرس)

### پیشنهادی
- **PHP:** نسخه 8.1 یا 8.2
- **حافظه PHP:** 512MB یا بیشتر
- **دسترسی MySQL:** امکان ایجاد دیتابیس جدید
- **فضای دیسک:** حداقل دو برابر حجم دیتابیس فعلی

---

## 🚀 نصب

### روش 1: نصب دستی

1. **دانلود فایل‌ها**
   ```bash
   git clone https://github.com/yourusername/multi-database-manager.git
   ```

2. **آپلود به سرور**
   - فایل‌ها را به `/wp-content/plugins/multi-database-manager/` آپلود کنید
   - یا فایل ZIP را از پنل وردپرس آپلود کنید

3. **فعال‌سازی**
   - به `افزونه‌ها` بروید
   - افزونه **Multi-Database Manager** را فعال کنید

4. **بررسی**
   - منوی "چند دیتابیس" در سایدبار ظاهر می‌شود

### روش 2: نصب از طریق WP-CLI

```bash
wp plugin install multi-database-manager.zip --activate
```

---

## ⚡ راهنمای سریع

### گام 1: افزودن دیتابیس جدید

1. به `چند دیتابیس → تنظیمات دیتابیس` بروید
2. فرم را با اطلاعات زیر پر کنید:
   - **نام شناسایی:** `woocommerce` (مثال)
   - **Host:** `localhost`
   - **نام دیتابیس:** `wp_woocommerce`
   - **نام کاربری و رمز:** اطلاعات MySQL
3. روی **تست اتصال** کلیک کنید
4. پست تایپ‌های مورد نظر را انتخاب کنید
5. **ذخیره** کنید

### گام 2: جداسازی ووکامرس (اختیاری)

1. به `چند دیتابیس → تنظیمات ووکامرس` بروید
2. اطلاعات دیتابیس را وارد کنید
3. روی **جداسازی خودکار ووکامرس** کلیک کنید
4. منتظر بمانید تا فرآیند کامل شود

### گام 3: انتقال داده‌ها

1. به `چند دیتابیس → ابزار انتقال` بروید
2. پست تایپ مورد نظر را انتخاب کنید
3. دیتابیس مقصد را مشخص کنید
4. **پشتیبان‌گیری** را فعال نگه دارید
5. ابتدا با **حالت تست** امتحان کنید
6. روی **شروع انتقال** کلیک کنید

---

## 📚 مستندات کامل

### معماری افزونه

```
multi-database-manager/
├── includes/                    # کلاس‌های اصلی
│   ├── class-config-manager.php
│   ├── class-database-manager.php
│   ├── class-query-router.php
│   ├── class-migration-handler.php
│   └── class-woocommerce-handler.php
├── admin/                       # پنل مدیریت
│   ├── views/                   # صفحات HTML
│   └── assets/                  # CSS و JS
└── languages/                   # فایل‌های ترجمه
```

### کلاس‌های اصلی

#### 1. Config Manager
مدیریت تنظیمات و رمزنگاری:
```php
$config_manager = new Multi_DB_Config_Manager();
$config_manager->save_database_config('my_db', $config_data);
```

#### 2. Database Manager
مدیریت اتصالات:
```php
$db_manager = new Multi_DB_Database_Manager($config_manager);
$connection = $db_manager->get_connection('my_db');
```

#### 3. Query Router
مسیریابی خودکار کوئری‌ها:
```php
$router = new Multi_DB_Query_Router($db_manager, $config_manager);
// روتینگ خودکار انجام می‌شود
```

#### 4. Migration Handler
انتقال داده‌ها:
```php
$migration = new Multi_DB_Migration_Handler($db_manager, $config_manager);
$result = $migration->migrate_post_type('product', 'woocommerce', [
    'backup' => true,
    'batch_size' => 100
]);
```

### استفاده از Hooks و Filters

```php
// غیرفعال کردن موقت روتینگ
add_filter('multi_db_routing_enabled', '__return_false');

// تغییر اندازه Batch
add_filter('multi_db_batch_size', function($size) {
    return 200; // از 100 به 200 تغییر می‌دهیم
});

// اضافه کردن لاگ سفارشی
add_action('multi_db_after_migration', function($results) {
    // کد شما
});
```

---

## 🔧 تنظیمات پیشرفته

### تنظیم محدودیت‌های PHP

در `wp-config.php` یا `.htaccess`:

```php
// افزایش حافظه
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// افزایش زمان اجرا
set_time_limit(300);
ini_set('max_execution_time', 300);
```

### تنظیمات MySQL

```sql
-- افزایش محدودیت اتصالات
SET GLOBAL max_connections = 200;

-- افزایش زمان timeout
SET GLOBAL wait_timeout = 600;
SET GLOBAL interactive_timeout = 600;
```

---

## ❓ سوالات متداول

### 1. آیا افزونه با سایر افزونه‌ها سازگار است؟
بله، افزونه با اکثر افزونه‌های وردپرس و ووکامرس سازگار است. برای افزونه‌های خاص ممکن است نیاز به تنظیمات اضافی باشد.

### 2. آیا می‌توانم چند دیتابیس روی سرورهای مختلف داشته باشم؟
بله، می‌توانید هر دیتابیس را روی سرور جداگانه قرار دهید.

### 3. اگر در حین انتقال خطا رخ دهد چه اتفاقی می‌افتد؟
اگر پشتیبان‌گیری فعال باشد، به صورت خودکار Rollback انجام می‌شود.

### 4. آیا عملکرد سایت بهبود می‌یابد؟
بله، به خصوص برای سایت‌های با حجم بالای داده، جداسازی دیتابیس می‌تواند عملکرد را تا 40% بهبود دهد.

### 5. آیا می‌توانم دیتابیس‌ها را دوباره ادغام کنم؟
بله، با انتقال معکوس می‌توانید داده‌ها را به دیتابیس اصلی بازگردانید.

### 6. چگونه از فایل‌های backup استفاده کنم؟
فایل‌های backup در `/wp-content/uploads/multi-db-backups/` ذخیره می‌شوند و می‌توانید آنها را با phpMyAdmin یا WP-CLI import کنید.

---

## 🛠️ عیب‌یابی

### مشکل: اتصال به دیتابیس ناموفق

**راه‌حل:**
1. بررسی صحت اطلاعات دیتابیس
2. بررسی دسترسی کاربر MySQL
3. بررسی فایروال و پورت‌ها
4. تست اتصال مستقیم با phpMyAdmin

### مشکل: خطای "Maximum execution time"

**راه‌حل:**
```php
// در wp-config.php
set_time_limit(0);
ini_set('max_execution_time', 0);
```

### مشکل: خطای "Memory exhausted"

**راه‌حل:**
```php
// در wp-config.php
define('WP_MEMORY_LIMIT', '512M');
```

### مشکل: Query ها به دیتابیس اشتباه می‌روند

**راه‌حل:**
1. کش افزونه را پاک کنید
2. دیتابیس را غیرفعال و دوباره فعال کنید
3. لاگ‌ها را بررسی کنید: `wp-content/plugins/multi-database-manager/debug.log`

### فعال‌سازی Debug Mode

```php
// در wp-config.php
define('MULTI_DB_DEBUG', true);
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

---

## 🤝 مشارکت

ما از مشارکت شما استقبال می‌کنیم! برای مشارکت:

1. **Fork** کنید
2. یک **Branch** جدید بسازید: `git checkout -b feature/AmazingFeature`
3. تغییرات را **Commit** کنید: `git commit -m 'Add AmazingFeature'`
4. به Branch خود **Push** کنید: `git push origin feature/AmazingFeature`
5. یک **Pull Request** باز کنید

### راهنمای کدنویسی

- از [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/) پیروی کنید
- PHPDoc برای تمام توابع بنویسید
- تست‌های واحد اضافه کنید
- مستندات را به‌روز نگه دارید

---

## 📞 پشتیبانی

- **مستندات:** [docs.example.com](https://docs.example.com)
- **GitHub Issues:** [github.com/yourusername/multi-database-manager/issues](https://github.com/yourusername/multi-database-manager/issues)
- **ایمیل:** support@example.com
- **انجمن:** [community.example.com](https://community.example.com)

---

## 📜 تاریخچه نسخه‌ها

### نسخه 1.0.0 (2025-10-27)
- 🎉 اولین انتشار عمومی
- ✅ جداسازی خودکار ووکامرس
- ✅ مدیریت چندگانه دیتابیس
- ✅ انتقال هوشمند داده‌ها
- ✅ پنل مدیریت کامل
- ✅ پشتیبانی کامل از RTL

---

## 📄 لایسنس

این افزونه تحت لایسنس **GPL v2 or later** منتشر شده است.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

---

## 🙏 تشکر

از تمامی توسعه‌دهندگان و کاربرانی که در بهبود این افزونه مشارکت داشته‌اند، صمیمانه تشکر می‌کنیم.

### ابزارهای استفاده شده
- [WordPress](https://wordpress.org)
- [WooCommerce](https://woocommerce.com)
- [Bootstrap 5](https://getbootstrap.com)
- [jQuery](https://jquery.com)

---

## 🔗 لینک‌های مفید

- [وردپرس Codex](https://codex.wordpress.org/)
- [WooCommerce Docs](https://woocommerce.com/documentation/)
- [راهنمای MySQL](https://dev.mysql.com/doc/)
- [PHP Manual](https://www.php.net/manual/en/)

---

**ساخته شده با ❤️ برای جامعه وردپرس**

© 2025 Multi-Database Manager. All rights reserved.