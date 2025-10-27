<?php
/**
 * مدیریت جداسازی ووکامرس به دیتابیس مجزا
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس هندلر ووکامرس
 */
class Multi_DB_WooCommerce_Handler {
    
    /**
     * مدیر دیتابیس
     */
    private $database_manager;
    
    /**
     * مدیر تنظیمات
     */
    private $config_manager;
    
    /**
     * نام دیتابیس ووکامرس
     */
    private $wc_database = 'woocommerce';
    
    /**
     * پست تایپ‌های ووکامرس
     */
    private $wc_post_types = [
        'product',
        'product_variation',
        'shop_order',
        'shop_order_refund',
        'shop_coupon',
        'shop_subscription'
    ];
    
    /**
     * تکسونومی‌های ووکامرس
     */
    private $wc_taxonomies = [
        'product_cat',
        'product_tag',
        'product_type',
        'product_visibility',
        'product_shipping_class'
    ];
    
    /**
     * سازنده کلاس
     */
    public function __construct($database_manager, $config_manager) {
        $this->database_manager = $database_manager;
        $this->config_manager = $config_manager;
        
        // بررسی فعال بودن ووکامرس
        if (!$this->is_woocommerce_active()) {
            return;
        }
        
        // راه‌اندازی هوک‌ها
        $this->init_hooks();
    }
    
    /**
     * بررسی فعال بودن ووکامرس
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }
    
    /**
     * راه‌اندازی هوک‌ها
     */
    private function init_hooks() {
        // هوک‌های محصولات
        add_filter('woocommerce_product_data_store', [$this, 'route_product_data_store'], 10, 1);
        
        // هوک‌های سفارشات
        add_filter('woocommerce_order_data_store', [$this, 'route_order_data_store'], 10, 1);
        
        // هوک‌های Session
        add_action('woocommerce_load_cart_from_session', [$this, 'route_session_data'], 10);
        
        // هوک‌های Analytics
        add_filter('woocommerce_analytics_clauses', [$this, 'route_analytics_queries'], 10, 2);
    }
    
    /**
     * دریافت لیست جداول ووکامرس
     */
    public function get_woocommerce_tables() {
        global $wpdb;
        
        $tables = [];
        
        // جداول با پیشوند wc_
        $wc_tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}wc_%'",
            ARRAY_N
        );
        
        foreach ($wc_tables as $table) {
            $tables[] = $table[0];
        }
        
        // جداول با پیشوند woocommerce_
        $woocommerce_tables = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_%'",
            ARRAY_N
        );
        
        foreach ($woocommerce_tables as $table) {
            $tables[] = $table[0];
        }
        
        // جداول خاص ووکامرس
        $specific_tables = [
            $wpdb->prefix . 'actionscheduler_actions',
            $wpdb->prefix . 'actionscheduler_claims',
            $wpdb->prefix . 'actionscheduler_groups',
            $wpdb->prefix . 'actionscheduler_logs'
        ];
        
        foreach ($specific_tables as $table) {
            // بررسی وجود جدول
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($exists) {
                $tables[] = $table;
            }
        }
        
        return array_unique($tables);
    }
    
    /**
     * دریافت تخمین حجم داده‌های ووکامرس
     */
    public function estimate_woocommerce_data_size() {
        global $wpdb;
        
        $tables = $this->get_woocommerce_tables();
        $total_size = 0;
        $table_info = [];
        
        foreach ($tables as $table) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    (data_length + index_length) / 1024 / 1024 AS size_mb,
                    table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table
            ));
            
            if ($result) {
                $size = round($result->size_mb, 2);
                $total_size += $size;
                
                $table_info[] = [
                    'table' => $table,
                    'size' => $size,
                    'rows' => $result->table_rows
                ];
            }
        }
        
        // محاسبه حجم پست‌ها و متادیتا
        $posts_size = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM((data_length + index_length) / 1024 / 1024) 
             FROM information_schema.TABLES 
             WHERE table_schema = %s 
             AND table_name IN (%s, %s)",
            DB_NAME,
            $wpdb->posts,
            $wpdb->postmeta
        ));
        
        // تخمین بخش ووکامرس از posts و postmeta (حدود 30%)
        $estimated_posts_size = round($posts_size * 0.3, 2);
        
        return [
            'total_size' => round($total_size + $estimated_posts_size, 2),
            'tables_size' => round($total_size, 2),
            'posts_size' => $estimated_posts_size,
            'tables' => $table_info,
            'post_types' => $this->wc_post_types
        ];
    }
    
    /**
     * جداسازی خودکار ووکامرس
     */
    public function auto_separate_woocommerce($config_data) {
        global $wpdb;
        
        Multi_Database_Manager::log('info', 'شروع جداسازی خودکار ووکامرس', 'woocommerce');
        
        $results = [
            'success' => false,
            'steps' => [],
            'errors' => []
        ];
        
        try {
            // مرحله 1: ذخیره تنظیمات
            $wc_config = [
                'host' => $config_data['host'],
                'database' => $config_data['database'],
                'username' => $config_data['username'],
                'password' => $config_data['password'],
                'port' => $config_data['port'] ?? 3306,
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_unicode_ci',
                'post_types' => $this->wc_post_types,
                'tables' => $this->get_woocommerce_tables(),
                'active' => false // فعال نمی‌شود تا انتقال کامل شود
            ];
            
            $save_result = $this->config_manager->save_database_config('woocommerce', $wc_config);
            
            if (is_wp_error($save_result)) {
                throw new Exception($save_result->get_error_message());
            }
            
            $results['steps'][] = 'تنظیمات ذخیره شد';
            
            // مرحله 2: ایجاد دیتابیس
            $create_result = $this->database_manager->create_database('woocommerce');
            
            if (is_wp_error($create_result)) {
                throw new Exception($create_result->get_error_message());
            }
            
            $results['steps'][] = 'دیتابیس ایجاد شد';
            
            // مرحله 3: کپی ساختار جداول
            $copy_result = $this->database_manager->copy_wordpress_tables('woocommerce');
            
            if (!empty($copy_result['errors'])) {
                $results['errors'] = array_merge($results['errors'], $copy_result['errors']);
            }
            
            $results['steps'][] = "ساختار {$copy_result['success']} جدول کپی شد";
            
            // مرحله 4: کپی جداول خاص ووکامرس
            $wc_tables = $this->get_woocommerce_tables();
            $wc_tables_copied = 0;
            
            foreach ($wc_tables as $table) {
                $result = $this->database_manager->copy_table_structure($table, 'woocommerce');
                if (!is_wp_error($result)) {
                    $wc_tables_copied++;
                }
            }
            
            $results['steps'][] = "ساختار {$wc_tables_copied} جدول ووکامرس کپی شد";
            
            // مرحله 5: آماده برای انتقال داده‌ها
            $results['steps'][] = 'آماده برای انتقال داده‌ها';
            $results['success'] = true;
            
            Multi_Database_Manager::log('info', 'جداسازی خودکار ووکامرس با موفقیت انجام شد', 'woocommerce');
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            Multi_Database_Manager::log('error', 'خطا در جداسازی ووکامرس: ' . $e->getMessage(), 'woocommerce');
        }
        
        return $results;
    }
    
    /**
     * مسیریابی Product Data Store
     */
    public function route_product_data_store($data_store) {
        // اگر دیتابیس ووکامرس فعال است
        $db_name = $this->database_manager->get_database_for_post_type('product');
        
        if ($db_name) {
            $this->database_manager->switch_to_database($db_name);
        }
        
        return $data_store;
    }
    
    /**
     * مسیریابی Order Data Store
     */
    public function route_order_data_store($data_store) {
        // اگر دیتابیس ووکامرس فعال است
        $db_name = $this->database_manager->get_database_for_post_type('shop_order');
        
        if ($db_name) {
            $this->database_manager->switch_to_database($db_name);
        }
        
        return $data_store;
    }
    
    /**
     * مسیریابی Session Data
     */
    public function route_session_data() {
        $db_name = $this->database_manager->get_database_for_post_type('product');
        
        if ($db_name) {
            $this->database_manager->switch_to_database($db_name);
        }
    }
    
    /**
     * مسیریابی Analytics Queries
     */
    public function route_analytics_queries($clauses, $context) {
        $db_name = $this->database_manager->get_database_for_post_type('shop_order');
        
        if ($db_name) {
            // تغییر نام جداول در clauses
            $config = $this->config_manager->get_database_config($db_name);
            
            if ($config) {
                global $wpdb;
                $db_name_full = $config['database'];
                
                // جایگزینی نام جداول
                foreach ($clauses as $key => $clause) {
                    if (is_string($clause)) {
                        $clauses[$key] = str_replace(
                            $wpdb->prefix . 'wc_',
                            "`{$db_name_full}`.`{$wpdb->prefix}wc_",
                            $clause
                        );
                    }
                }
            }
        }
        
        return $clauses;
    }
    
    /**
     * دریافت آمار ووکامرس
     */
    public function get_woocommerce_stats($db_name = 'woocommerce') {
        $connection = $this->database_manager->get_connection($db_name);
        
        if (!$connection) {
            return null;
        }
        
        $stats = [];
        
        // تعداد محصولات
        $stats['products'] = $connection->get_var(
            "SELECT COUNT(*) FROM {$connection->posts} WHERE post_type = 'product' AND post_status = 'publish'"
        );
        
        // تعداد سفارشات
        $stats['orders'] = $connection->get_var(
            "SELECT COUNT(*) FROM {$connection->posts} WHERE post_type = 'shop_order'"
        );
        
        // تعداد مشتریان
        $stats['customers'] = $connection->get_var(
            "SELECT COUNT(DISTINCT meta_value) 
             FROM {$connection->postmeta} 
             WHERE meta_key = '_customer_user' AND meta_value > 0"
        );
        
        // تعداد کوپن‌ها
        $stats['coupons'] = $connection->get_var(
            "SELECT COUNT(*) FROM {$connection->posts} WHERE post_type = 'shop_coupon'"
        );
        
        return $stats;
    }
    
    /**
     * بررسی سلامت دیتابیس ووکامرس
     */
    public function check_woocommerce_health() {
        $health = [
            'status' => 'healthy',
            'missing_tables' => [],
            'warnings' => []
        ];
        
        $db_name = 'woocommerce';
        $connection = $this->database_manager->get_connection($db_name);
        
        if (!$connection) {
            $health['status'] = 'error';
            $health['warnings'][] = 'اتصال به دیتابیس ووکامرس ناموفق';
            return $health;
        }
        
        // بررسی جداول ضروری
        $required_tables = [
            'wc_product_meta_lookup',
            'wc_order_stats',
            'wc_customer_lookup',
            'woocommerce_sessions'
        ];
        
        $existing_tables = $this->database_manager->get_database_tables($db_name);
        
        foreach ($required_tables as $table) {
            $full_table_name = $connection->prefix . $table;
            if (!in_array($full_table_name, $existing_tables)) {
                $health['missing_tables'][] = $table;
            }
        }
        
        if (!empty($health['missing_tables'])) {
            $health['status'] = 'warning';
            $health['warnings'][] = 'برخی جداول ووکامرس یافت نشدند';
        }
        
        return $health;
    }
    
    /**
     * تست عملکرد ووکامرس با دیتابیس جدید
     */
    public function test_woocommerce_functionality() {
        $tests = [
            'product_query' => false,
            'order_query' => false,
            'customer_query' => false,
            'analytics' => false
        ];
        
        try {
            // تست کوئری محصولات
            $products = wc_get_products(['limit' => 1]);
            $tests['product_query'] = !empty($products);
            
            // تست کوئری سفارشات
            $orders = wc_get_orders(['limit' => 1]);
            $tests['order_query'] = !empty($orders);
            
            // تست آنالیتیکس
            if (class_exists('Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore')) {
                $tests['analytics'] = true;
            }
            
        } catch (Exception $e) {
            Multi_Database_Manager::log('error', 'خطا در تست ووکامرس: ' . $e->getMessage(), 'woocommerce');
        }
        
        return $tests;
    }
    
    /**
     * دریافت تنظیمات پیش‌فرض ووکامرس
     */
    public function get_default_woocommerce_config() {
        return [
            'host' => DB_HOST,
            'database' => DB_NAME . '_woocommerce',
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'port' => 3306,
            'charset' => 'utf8mb4',
            'collate' => 'utf8mb4_unicode_ci',
            'post_types' => $this->wc_post_types,
            'tables' => [],
            'active' => false
        ];
    }
}