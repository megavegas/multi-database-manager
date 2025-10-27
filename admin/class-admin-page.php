<?php
/**
 * مدیریت پنل ادمین افزونه
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس پنل ادمین
 */
class Multi_DB_Admin_Page {
    
    /**
     * مدیر دیتابیس
     */
    private $database_manager;
    
    /**
     * مدیر تنظیمات
     */
    private $config_manager;
    
    /**
     * صفحه فعال
     */
    private $current_page = 'dashboard';
    
    /**
     * سازنده کلاس
     */
    public function __construct($database_manager, $config_manager) {
        $this->database_manager = $database_manager;
        $this->config_manager = $config_manager;
        
        // راه‌اندازی هوک‌ها
        $this->init_hooks();
    }
    
    /**
     * راه‌اندازی هوک‌ها
     */
    private function init_hooks() {
        // اضافه کردن منو
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // بارگذاری استایل‌ها و اسکریپت‌ها
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // پردازش فرم‌ها
        add_action('admin_init', [$this, 'process_forms']);
        
        // AJAX handlers
        add_action('wp_ajax_multi_db_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_multi_db_start_migration', [$this, 'ajax_start_migration']);
        add_action('wp_ajax_multi_db_get_progress', [$this, 'ajax_get_progress']);
        add_action('wp_ajax_multi_db_get_stats', [$this, 'ajax_get_stats']);
    }
    
    /**
     * اضافه کردن منو به ادمین
     */
    public function add_admin_menu() {
        // منوی اصلی
        add_menu_page(
            __('مدیریت چند دیتابیس', 'multi-db-manager'),
            __('چند دیتابیس', 'multi-db-manager'),
            'manage_options',
            'multi-db-manager',
            [$this, 'render_dashboard_page'],
            'dashicons-database',
            80
        );
        
        // زیرمنو داشبورد
        add_submenu_page(
            'multi-db-manager',
            __('داشبورد', 'multi-db-manager'),
            __('داشبورد', 'multi-db-manager'),
            'manage_options',
            'multi-db-manager',
            [$this, 'render_dashboard_page']
        );
        
        // زیرمنو تنظیمات دیتابیس
        add_submenu_page(
            'multi-db-manager',
            __('تنظیمات دیتابیس', 'multi-db-manager'),
            __('تنظیمات دیتابیس', 'multi-db-manager'),
            'manage_options',
            'multi-db-config',
            [$this, 'render_config_page']
        );
        
        // زیرمنو ابزار انتقال
        add_submenu_page(
            'multi-db-manager',
            __('ابزار انتقال', 'multi-db-manager'),
            __('ابزار انتقال', 'multi-db-manager'),
            'manage_options',
            'multi-db-migration',
            [$this, 'render_migration_page']
        );
        
        // زیرمنو ووکامرس
        if (class_exists('WooCommerce')) {
            add_submenu_page(
                'multi-db-manager',
                __('تنظیمات ووکامرس', 'multi-db-manager'),
                __('تنظیمات ووکامرس', 'multi-db-manager'),
                'manage_options',
                'multi-db-woocommerce',
                [$this, 'render_woocommerce_page']
            );
        }
    }
    
    /**
     * بارگذاری فایل‌های CSS و JS
     */
    public function enqueue_assets($hook) {
        // فقط در صفحات افزونه
        if (strpos($hook, 'multi-db') === false) {
            return;
        }
        
        // Bootstrap CSS
        wp_enqueue_style(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            '5.3.0'
        );
        
        // استایل سفارشی
        wp_enqueue_style(
            'multi-db-admin-style',
            MULTI_DB_PLUGIN_URL . 'admin/assets/css/admin-style.css',
            ['bootstrap'],
            MULTI_DB_VERSION
        );
        
        // jQuery (از وردپرس)
        wp_enqueue_script('jquery');
        
        // Bootstrap JS
        wp_enqueue_script(
            'bootstrap',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            ['jquery'],
            '5.3.0',
            true
        );
        
        // اسکریپت سفارشی
        wp_enqueue_script(
            'multi-db-admin-script',
            MULTI_DB_PLUGIN_URL . 'admin/assets/js/admin-script.js',
            ['jquery', 'bootstrap'],
            MULTI_DB_VERSION,
            true
        );
        
        // متغیرهای جاوااسکریپت
        wp_localize_script('multi-db-admin-script', 'multiDbAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('multi_db_nonce'),
            'strings' => [
                'confirmDelete' => __('آیا از حذف این دیتابیس اطمینان دارید؟', 'multi-db-manager'),
                'confirmMigration' => __('آیا از انتقال داده‌ها اطمینان دارید؟', 'multi-db-manager'),
                'testing' => __('در حال تست اتصال...', 'multi-db-manager'),
                'success' => __('موفق', 'multi-db-manager'),
                'error' => __('خطا', 'multi-db-manager')
            ]
        ]);
    }
    
    /**
     * پردازش فرم‌ها
     */
    public function process_forms() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // پردازش فرم ذخیره تنظیمات
        if (isset($_POST['multi_db_save_config']) && check_admin_referer('multi_db_config_nonce')) {
            $this->process_save_config();
        }
        
        // پردازش فرم حذف تنظیمات
        if (isset($_GET['action']) && $_GET['action'] === 'delete_config' && isset($_GET['config_name'])) {
            if (check_admin_referer('multi_db_delete_' . $_GET['config_name'])) {
                $this->process_delete_config($_GET['config_name']);
            }
        }
        
        // پردازش فرم toggle status
        if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['config_name'])) {
            if (check_admin_referer('multi_db_toggle_' . $_GET['config_name'])) {
                $this->process_toggle_status($_GET['config_name']);
            }
        }
    }
    
    /**
     * پردازش ذخیره تنظیمات
     */
    private function process_save_config() {
        $config_name = sanitize_text_field($_POST['config_name']);
        
        $config_data = [
            'host' => sanitize_text_field($_POST['host']),
            'database' => sanitize_text_field($_POST['database']),
            'username' => sanitize_text_field($_POST['username']),
            'password' => $_POST['password'],
            'port' => intval($_POST['port']),
            'charset' => sanitize_text_field($_POST['charset']),
            'collate' => sanitize_text_field($_POST['collate']),
            'post_types' => isset($_POST['post_types']) ? array_map('sanitize_text_field', $_POST['post_types']) : [],
            'tables' => isset($_POST['tables']) ? array_map('sanitize_text_field', $_POST['tables']) : [],
            'active' => isset($_POST['active'])
        ];
        
        $result = $this->config_manager->save_database_config($config_name, $config_data);
        
        if (is_wp_error($result)) {
            add_settings_error(
                'multi_db_messages',
                'multi_db_error',
                $result->get_error_message(),
                'error'
            );
        } else {
            add_settings_error(
                'multi_db_messages',
                'multi_db_success',
                __('تنظیمات با موفقیت ذخیره شد', 'multi-db-manager'),
                'success'
            );
        }
    }
    
    /**
     * پردازش حذف تنظیمات
     */
    private function process_delete_config($config_name) {
        $result = $this->config_manager->delete_config($config_name);
        
        if ($result) {
            add_settings_error(
                'multi_db_messages',
                'multi_db_success',
                __('تنظیمات حذف شد', 'multi-db-manager'),
                'success'
            );
        } else {
            add_settings_error(
                'multi_db_messages',
                'multi_db_error',
                __('خطا در حذف تنظیمات', 'multi-db-manager'),
                'error'
            );
        }
        
        wp_redirect(admin_url('admin.php?page=multi-db-config'));
        exit;
    }
    
    /**
     * پردازش تغییر وضعیت
     */
    private function process_toggle_status($config_name) {
        $configs = $this->config_manager->get_all_configs();
        $current_status = $configs[$config_name]['is_active'] ?? false;
        
        $result = $this->config_manager->toggle_config_status($config_name, !$current_status);
        
        if ($result) {
            $message = $current_status ? 
                __('دیتابیس غیرفعال شد', 'multi-db-manager') : 
                __('دیتابیس فعال شد', 'multi-db-manager');
            
            add_settings_error('multi_db_messages', 'multi_db_success', $message, 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=multi-db-config'));
        exit;
    }
    
    /**
     * رندر صفحه داشبورد
     */
    public function render_dashboard_page() {
        $this->current_page = 'dashboard';
        $this->render_page_header();
        include MULTI_DB_PLUGIN_DIR . 'admin/views/dashboard.php';
        $this->render_page_footer();
    }
    
    /**
     * رندر صفحه تنظیمات
     */
    public function render_config_page() {
        $this->current_page = 'config';
        $this->render_page_header();
        include MULTI_DB_PLUGIN_DIR . 'admin/views/database-config.php';
        $this->render_page_footer();
    }
    
    /**
     * رندر صفحه انتقال
     */
    public function render_migration_page() {
        $this->current_page = 'migration';
        $this->render_page_header();
        include MULTI_DB_PLUGIN_DIR . 'admin/views/migration-tools.php';
        $this->render_page_footer();
    }
    
    /**
     * رندر صفحه ووکامرس
     */
    public function render_woocommerce_page() {
        $this->current_page = 'woocommerce';
        $this->render_page_header();
        include MULTI_DB_PLUGIN_DIR . 'admin/views/woocommerce-settings.php';
        $this->render_page_footer();
    }
    
    /**
     * رندر هدر صفحه
     */
    private function render_page_header() {
        ?>
        <div class="wrap multi-db-admin">
            <h1 class="wp-heading-inline">
                <i class="dashicons dashicons-database"></i>
                <?php echo esc_html__('مدیریت چند دیتابیس', 'multi-db-manager'); ?>
            </h1>
            <hr class="wp-header-end">
            <?php settings_errors('multi_db_messages'); ?>
        <?php
    }
    
    /**
     * رندر فوتر صفحه
     */
    private function render_page_footer() {
        ?>
        </div>
        <?php
    }
    
    /**
     * AJAX: تست اتصال دیتابیس
     */
    public function ajax_test_connection() {
        check_ajax_referer('multi_db_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('دسترسی غیرمجاز', 'multi-db-manager')]);
        }
        
        $config_data = [
            'host' => sanitize_text_field($_POST['host']),
            'database' => sanitize_text_field($_POST['database']),
            'username' => sanitize_text_field($_POST['username']),
            'password' => $_POST['password'],
            'port' => intval($_POST['port'])
        ];
        
        $result = $this->config_manager->test_connection($config_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => __('اتصال با موفقیت برقرار شد', 'multi-db-manager')]);
        }
    }
    
    /**
     * AJAX: شروع انتقال
     */
    public function ajax_start_migration() {
        check_ajax_referer('multi_db_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('دسترسی غیرمجاز', 'multi-db-manager')]);
        }
        
        $post_type = sanitize_text_field($_POST['post_type']);
        $target_db = sanitize_text_field($_POST['target_db']);
        $backup = isset($_POST['backup']) && $_POST['backup'] === 'true';
        
        // ایجاد Migration Handler
        $migration_handler = new Multi_DB_Migration_Handler(
            $this->database_manager,
            $this->config_manager
        );
        
        // شروع انتقال در background
        $result = $migration_handler->migrate_post_type($post_type, $target_db, [
            'backup' => $backup,
            'batch_size' => 50,
            'delete_source' => false
        ]);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => __('انتقال با موفقیت انجام شد', 'multi-db-manager'),
                'results' => $result
            ]);
        } else {
            wp_send_json_error([
                'message' => __('خطا در انتقال', 'multi-db-manager'),
                'errors' => $result['errors']
            ]);
        }
    }
    
    /**
     * AJAX: دریافت پیشرفت
     */
    public function ajax_get_progress() {
        check_ajax_referer('multi_db_nonce', 'nonce');
        
        $post_type = sanitize_text_field($_POST['post_type']);
        
        $migration_handler = new Multi_DB_Migration_Handler();
        $progress = $migration_handler->get_migration_progress($post_type);
        
        if ($progress) {
            wp_send_json_success($progress);
        } else {
            wp_send_json_error(['message' => __('اطلاعات پیشرفت یافت نشد', 'multi-db-manager')]);
        }
    }
    
    /**
     * AJAX: دریافت آمار
     */
    public function ajax_get_stats() {
        check_ajax_referer('multi_db_nonce', 'nonce');
        
        $configs = $this->config_manager->get_all_configs();
        $stats = [];
        
        foreach ($configs as $name => $config) {
            if ($config['is_active']) {
                $health = $this->database_manager->check_database_health($name);
                $stats[$name] = [
                    'size' => $health['size'],
                    'tables' => $health['tables_count'],
                    'status' => $health['status']
                ];
            }
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * دریافت لیست پست تایپ‌ها
     */
    public function get_available_post_types() {
        $post_types = get_post_types(['public' => true], 'objects');
        
        $list = [];
        foreach ($post_types as $post_type) {
            $list[$post_type->name] = $post_type->label;
        }
        
        return $list;
    }
    
    /**
     * دریافت رنگ بر اساس وضعیت
     */
    public function get_status_badge($status) {
        $badges = [
            'healthy' => '<span class="badge bg-success">سالم</span>',
            'warning' => '<span class="badge bg-warning">هشدار</span>',
            'error' => '<span class="badge bg-danger">خطا</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">نامشخص</span>';
    }
}