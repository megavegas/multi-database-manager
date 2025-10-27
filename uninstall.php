<?php
/**
 * فایل حذف افزونه
 * 
 * این فایل هنگام حذف افزونه اجرا می‌شود و تمام داده‌ها را پاک می‌کند
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// اگر از طریق وردپرس فراخوانی نشده، خروج
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * حذف کامل افزونه Multi-Database Manager
 */
class Multi_DB_Uninstaller {
    
    /**
     * اجرای فرآیند حذف
     */
    public static function uninstall() {
        global $wpdb;
        
        // بررسی مجوز
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // حذف جداول
        self::drop_tables();
        
        // حذف تنظیمات
        self::delete_options();
        
        // حذف transient ها
        self::delete_transients();
        
        // حذف فایل‌های پشتیبان
        self::delete_backup_files();
        
        // حذف user meta
        self::delete_user_meta();
        
        // پاک کردن کش
        self::clear_cache();
        
        // لاگ حذف
        self::log_uninstall();
    }
    
    /**
     * حذف جداول دیتابیس
     */
    private static function drop_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'multi_db_configs',
            $wpdb->prefix . 'multi_db_logs'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
        }
    }
    
    /**
     * حذف تنظیمات
     */
    private static function delete_options() {
        // تنظیمات اصلی
        delete_option('multi_db_version');
        delete_option('multi_db_installed');
        delete_option('multi_db_encryption_key');
        
        // تنظیمات پیشرفته
        delete_option('multi_db_batch_size');
        delete_option('multi_db_timeout');
        delete_option('multi_db_debug_mode');
        
        // حذف تمام تنظیماتی که با multi_db شروع می‌شوند
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE 'multi_db_%'"
        );
    }
    
    /**
     * حذف transient ها
     */
    private static function delete_transients() {
        global $wpdb;
        
        // حذف transient های افزونه
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_multi_db_%' 
             OR option_name LIKE '_transient_timeout_multi_db_%'"
        );
        
        // حذف site transient ها
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_site_transient_multi_db_%' 
             OR option_name LIKE '_site_transient_timeout_multi_db_%'"
        );
    }
    
    /**
     * حذف فایل‌های پشتیبان
     */
    private static function delete_backup_files() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/multi-db-backups';
        
        if (file_exists($backup_dir)) {
            self::delete_directory($backup_dir);
        }
    }
    
    /**
     * حذف دایرکتوری به صورت بازگشتی
     */
    private static function delete_directory($dir) {
        if (!file_exists($dir)) {
            return false;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * حذف user meta
     */
    private static function delete_user_meta() {
        global $wpdb;
        
        // حذف تمام meta های کاربران مرتبط با افزونه
        $wpdb->query(
            "DELETE FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE 'multi_db_%'"
        );
    }
    
    /**
     * پاک کردن کش
     */
    private static function clear_cache() {
        // پاک کردن کش Object Cache
        wp_cache_flush();
        
        // اگر از کش پیشرفته استفاده می‌شود
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        // پاک کردن Opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
    
    /**
     * ثبت لاگ حذف
     */
    private static function log_uninstall() {
        // ثبت در error_log
        error_log(
            sprintf(
                '[Multi-DB Manager] Plugin uninstalled at %s by user %d',
                current_time('mysql'),
                get_current_user_id()
            )
        );
    }
}

/**
 * نمایش تایید حذف (اختیاری)
 */
function multi_db_confirm_uninstall() {
    // اگر می‌خواهید قبل از حذف از کاربر تایید بگیرید
    // می‌توانید اینجا یک صفحه تایید نمایش دهید
    
    // برای مثال:
    /*
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        wp_die(
            '<h1>حذف افزونه Multi-Database Manager</h1>
            <p><strong>هشدار:</strong> با حذف این افزونه، تمام تنظیمات، لاگ‌ها و فایل‌های پشتیبان حذف خواهند شد.</p>
            <p>دیتابیس‌های جداگانه حذف نمی‌شوند و باید به صورت دستی آنها را حذف کنید.</p>
            <p><a href="' . add_query_arg('confirm', 'yes') . '" class="button button-primary">بله، افزونه را حذف کن</a>
            <a href="' . admin_url('plugins.php') . '" class="button">انصراف</a></p>',
            'تایید حذف'
        );
    }
    */
}

/**
 * اجرای حذف
 */
Multi_DB_Uninstaller::uninstall();

/**
 * پیام نهایی
 * 
 * توجه: دیتابیس‌های جداگانه که ایجاد کرده‌اید حذف نمی‌شوند.
 * برای حذف کامل، باید آنها را به صورت دستی از phpMyAdmin یا MySQL حذف کنید.
 * 
 * مراحل حذف دستی دیتابیس‌ها:
 * 
 * 1. وارد phpMyAdmin شوید
 * 2. دیتابیس‌های ایجاد شده را پیدا کنید (مثلاً wp_woocommerce)
 * 3. هر دیتابیس را انتخاب کنید
 * 4. از منوی Operations گزینه Drop Database را انتخاب کنید
 * 
 * یا از طریق MySQL command line:
 * 
 * mysql -u root -p
 * DROP DATABASE wp_woocommerce;
 * 
 * توصیه: قبل از حذف دیتابیس‌ها، حتماً از آنها پشتیبان بگیرید!
 */