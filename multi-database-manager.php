<?php
/**
 * Plugin Name: Multi-Database Manager for WordPress & WooCommerce
 * Plugin URI: https://decoline.ir/plugins/multi-database-manager
 * Description: جداسازی ووکامرس و پست تایپ‌ها به دیتابیس‌های مجزا برای بهینه‌سازی عملکرد
 * Version: 1.0.0
 * Author: مقداد عباسی حصوری
 * Author URI: https://decoline.ir
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: multi-db-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های اصلی افزونه
define('MULTI_DB_VERSION', '1.0.0');
define('MULTI_DB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MULTI_DB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MULTI_DB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * کلاس اصلی افزونه Multi Database Manager
 */
class Multi_Database_Manager {
    
    /**
     * نمونه واحد کلاس (Singleton)
     */
    private static $instance = null;
    
    /**
     * مدیر دیتابیس
     */
    public $database_manager = null;
    
    /**
     * مسیریاب کوئری
     */
    public $query_router = null;
    
    /**
     * هندلر ووکامرس
     */
    public $woocommerce_handler = null;
    
    /**
     * مدیر تنظیمات
     */
    public $config_manager = null;
    
    /**
     * دریافت نمونه واحد کلاس
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * بارگذاری فایل‌های وابسته
     */
    private function load_dependencies() {
        // کلاس‌های اصلی
        require_once MULTI_DB_PLUGIN_DIR . 'includes/class-config-manager.php';
        require_once MULTI_DB_PLUGIN_DIR . 'includes/class-database-manager.php';
        require_once MULTI_DB_PLUGIN_DIR . 'includes/class-query-router.php';
        require_once MULTI_DB_PLUGIN_DIR . 'includes/class-migration-handler.php';
        require_once MULTI_DB_PLUGIN_DIR . 'includes/class-woocommerce-handler.php';
        
        // پنل مدیریت
        if (is_admin()) {
            require_once MULTI_DB_PLUGIN_DIR . 'admin/class-admin-page.php';
        }
    }
    
    /**
     * راه‌اندازی هوک‌ها
     */
    private function init_hooks() {
        // فعال‌سازی افزونه
        register_activation_hook(__FILE__, [$this, 'activate']);
        
        // غیرفعال‌سازی افزونه
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // بارگذاری فایل‌های ترجمه
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // اضافه کردن لینک تنظیمات در صفحه افزونه‌ها
        add_filter('plugin_action_links_' . MULTI_DB_PLUGIN_BASENAME, [$this, 'add_settings_link']);
    }
    
    /**
     * راه‌اندازی اجزای افزونه
     */
    private function init_components() {
        // مدیر تنظیمات
        $this->config_manager = new Multi_DB_Config_Manager();
        
        // مدیر دیتابیس
        $this->database_manager = new Multi_DB_Database_Manager($this->config_manager);
        
        // مسیریاب کوئری
        $this->query_router = new Multi_DB_Query_Router($this->database_manager, $this->config_manager);
        
        // هندلر ووکامرس (فقط اگر ووکامرس فعال باشد)
        if (class_exists('WooCommerce')) {
            $this->woocommerce_handler = new Multi_DB_WooCommerce_Handler(
                $this->database_manager,
                $this->config_manager
            );
        }
        
        // پنل مدیریت
        if (is_admin()) {
            new Multi_DB_Admin_Page(
                $this->database_manager,
                $this->config_manager
            );
        }
    }
    
    /**
     * فعال‌سازی افزونه
     */
    public function activate() {
        // ایجاد جدول تنظیمات
        $this->create_config_table();
        
        // ایجاد جدول لاگ
        $this->create_log_table();
        
        // ایجاد دایرکتوری backup
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/multi-db-backups';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            // ایجاد فایل .htaccess برای امنیت
            file_put_contents($backup_dir . '/.htaccess', 'deny from all');
        }
        
        // ذخیره نسخه افزونه
        update_option('multi_db_version', MULTI_DB_VERSION);
        
        // تنظیم پیش‌فرض
        if (!get_option('multi_db_installed')) {
            add_option('multi_db_installed', current_time('mysql'));
        }
    }
    
    /**
     * غیرفعال‌سازی افزونه
     */
    public function deactivate() {
        // پاک کردن کش‌ها
        wp_cache_flush();
        
        // حذف transient‌ها
        delete_transient('multi_db_databases');
        delete_transient('multi_db_health_status');
    }
    
    /**
     * ایجاد جدول تنظیمات
     */
    private function create_config_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'multi_db_configs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            config_name varchar(255) NOT NULL,
            config_data longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY config_name (config_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * ایجاد جدول لاگ
     */
    private function create_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'multi_db_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            log_level varchar(50) NOT NULL,
            database_name varchar(255),
            message text NOT NULL,
            context longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY log_level (log_level),
            KEY database_name (database_name),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * بارگذاری فایل‌های ترجمه
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'multi-db-manager',
            false,
            dirname(MULTI_DB_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * اضافه کردن لینک تنظیمات
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=multi-db-manager'),
            __('تنظیمات', 'multi-db-manager')
        );
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * ثبت لاگ
     */
    public static function log($level, $message, $database = null, $context = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'multi_db_logs';
        
        $wpdb->insert(
            $table_name,
            [
                'log_level' => sanitize_text_field($level),
                'database_name' => sanitize_text_field($database),
                'message' => sanitize_text_field($message),
                'context' => wp_json_encode($context)
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        // حذف لاگ‌های قدیمی‌تر از 30 روز
        $wpdb->query(
            "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
    }
}

/**
 * راه‌اندازی افزونه
 */
function multi_db_manager_init() {
    return Multi_Database_Manager::get_instance();
}

// اجرای افزونه
multi_db_manager_init();