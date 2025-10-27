<?php
/**
 * مدیریت اتصالات چندگانه به دیتابیس‌ها
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت دیتابیس
 */
class Multi_DB_Database_Manager {
    
    /**
     * مدیر تنظیمات
     */
    private $config_manager;
    
    /**
     * اتصالات فعال
     */
    private $connections = [];
    
    /**
     * دیتابیس پیش‌فرض وردپرس
     */
    private $default_wpdb;
    
    /**
     * نقشه پست تایپ به دیتابیس
     */
    private $post_type_map = [];
    
    /**
     * سازنده کلاس
     */
    public function __construct($config_manager) {
        global $wpdb;
        
        $this->config_manager = $config_manager;
        $this->default_wpdb = $wpdb;
        
        // بارگذاری نقشه پست تایپ‌ها
        $this->build_post_type_map();
    }
    
    /**
     * ساخت نقشه پست تایپ به دیتابیس
     */
    private function build_post_type_map() {
        $configs = $this->config_manager->get_all_active_configs();
        
        foreach ($configs as $db_name => $config) {
            if (!empty($config['post_types'])) {
                foreach ($config['post_types'] as $post_type) {
                    $this->post_type_map[$post_type] = $db_name;
                }
            }
        }
    }
    
    /**
     * دریافت اتصال به دیتابیس
     */
    public function get_connection($db_name) {
        // اگر اتصال موجود است
        if (isset($this->connections[$db_name])) {
            return $this->connections[$db_name];
        }
        
        // دریافت تنظیمات
        $config = $this->config_manager->get_database_config($db_name);
        
        if (!$config) {
            Multi_Database_Manager::log('error', "تنظیمات دیتابیس یافت نشد", $db_name);
            return false;
        }
        
        // ایجاد اتصال جدید
        $connection = $this->create_connection($config);
        
        if ($connection) {
            $this->connections[$db_name] = $connection;
            Multi_Database_Manager::log('info', "اتصال به دیتابیس برقرار شد", $db_name);
        }
        
        return $connection;
    }
    
    /**
     * ایجاد اتصال جدید
     */
    private function create_connection($config) {
        try {
            // استفاده از wpdb برای سازگاری با وردپرس
            $wpdb = new wpdb(
                $config['username'],
                $config['password'],
                $config['database'],
                $config['host']
            );
            
            // تنظیم charset
            if (!empty($config['charset'])) {
                $wpdb->set_charset($wpdb->dbh, $config['charset'], $config['collate'] ?? '');
            }
            
            // تست اتصال
            if (!$wpdb->check_connection(false)) {
                throw new Exception('اتصال به دیتابیس ناموفق بود');
            }
            
            return $wpdb;
            
        } catch (Exception $e) {
            Multi_Database_Manager::log('error', 'خطا در ایجاد اتصال: ' . $e->getMessage(), $config['database']);
            return false;
        }
    }
    
    /**
     * دریافت دیتابیس برای پست تایپ
     */
    public function get_database_for_post_type($post_type) {
        if (isset($this->post_type_map[$post_type])) {
            return $this->post_type_map[$post_type];
        }
        
        return null;
    }
    
    /**
     * تغییر اتصال wpdb به دیتابیس خاص
     */
    public function switch_to_database($db_name) {
        global $wpdb;
        
        $connection = $this->get_connection($db_name);
        
        if (!$connection) {
            return false;
        }
        
        // ذخیره اتصال فعلی
        if (!isset($this->default_wpdb)) {
            $this->default_wpdb = clone $wpdb;
        }
        
        // تغییر اتصال
        $wpdb = $connection;
        
        return true;
    }
    
    /**
     * بازگشت به دیتابیس پیش‌فرض
     */
    public function restore_database() {
        global $wpdb;
        
        if ($this->default_wpdb) {
            $wpdb = $this->default_wpdb;
            return true;
        }
        
        return false;
    }
    
    /**
     * بررسی وجود دیتابیس
     */
    public function database_exists($db_name) {
        $config = $this->config_manager->get_database_config($db_name);
        
        if (!$config) {
            return false;
        }
        
        try {
            $mysqli = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                '',
                $config['port'] ?? 3306
            );
            
            if ($mysqli->connect_error) {
                return false;
            }
            
            $result = $mysqli->query("SHOW DATABASES LIKE '{$config['database']}'");
            $exists = $result && $result->num_rows > 0;
            
            $mysqli->close();
            
            return $exists;
            
        } catch (Exception $e) {
            Multi_Database_Manager::log('error', 'خطا در بررسی دیتابیس: ' . $e->getMessage(), $db_name);
            return false;
        }
    }
    
    /**
     * ایجاد دیتابیس جدید
     */
    public function create_database($db_name) {
        $config = $this->config_manager->get_database_config($db_name);
        
        if (!$config) {
            return new WP_Error('no_config', __('تنظیمات دیتابیس یافت نشد', 'multi-db-manager'));
        }
        
        try {
            $mysqli = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                '',
                $config['port'] ?? 3306
            );
            
            if ($mysqli->connect_error) {
                throw new Exception('اتصال ناموفق: ' . $mysqli->connect_error);
            }
            
            // ایجاد دیتابیس
            $charset = $config['charset'] ?? 'utf8mb4';
            $collate = $config['collate'] ?? 'utf8mb4_unicode_ci';
            
            $sql = "CREATE DATABASE IF NOT EXISTS `{$config['database']}` 
                    CHARACTER SET {$charset} 
                    COLLATE {$collate}";
            
            if (!$mysqli->query($sql)) {
                throw new Exception('خطا در ایجاد دیتابیس: ' . $mysqli->error);
            }
            
            $mysqli->close();
            
            Multi_Database_Manager::log('info', "دیتابیس ایجاد شد", $db_name);
            
            return true;
            
        } catch (Exception $e) {
            Multi_Database_Manager::log('error', $e->getMessage(), $db_name);
            return new WP_Error('create_failed', $e->getMessage());
        }
    }
    
    /**
     * کپی ساختار جداول از دیتابیس اصلی
     */
    public function copy_table_structure($table_name, $target_db_name) {
        global $wpdb;
        
        $target_connection = $this->get_connection($target_db_name);
        
        if (!$target_connection) {
            return new WP_Error('no_connection', __('اتصال به دیتابیس هدف ناموفق', 'multi-db-manager'));
        }
        
        // دریافت ساختار جدول
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `{$table_name}`", ARRAY_N);
        
        if (!$create_table) {
            return new WP_Error('no_table', __('جدول در دیتابیس مبدا یافت نشد', 'multi-db-manager'));
        }
        
        // ایجاد جدول در دیتابیس هدف
        $result = $target_connection->query($create_table[1]);
        
        if (!$result) {
            return new WP_Error('create_table_failed', __('خطا در ایجاد جدول', 'multi-db-manager'));
        }
        
        Multi_Database_Manager::log('info', "ساختار جدول {$table_name} کپی شد", $target_db_name);
        
        return true;
    }
    
    /**
     * کپی جداول اصلی وردپرس
     */
    public function copy_wordpress_tables($target_db_name) {
        global $wpdb;
        
        // جداول اصلی وردپرس
        $core_tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->terms,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships,
            $wpdb->comments,
            $wpdb->commentmeta,
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->links,
            $wpdb->options
        ];
        
        $success_count = 0;
        $errors = [];
        
        foreach ($core_tables as $table) {
            $result = $this->copy_table_structure($table, $target_db_name);
            
            if (is_wp_error($result)) {
                $errors[] = $table . ': ' . $result->get_error_message();
            } else {
                $success_count++;
            }
        }
        
        if (!empty($errors)) {
            Multi_Database_Manager::log('warning', 'برخی جداول کپی نشدند', $target_db_name, $errors);
        }
        
        return [
            'success' => $success_count,
            'total' => count($core_tables),
            'errors' => $errors
        ];
    }
    
    /**
     * دریافت لیست جداول دیتابیس
     */
    public function get_database_tables($db_name) {
        $connection = $this->get_connection($db_name);
        
        if (!$connection) {
            return [];
        }
        
        $tables = $connection->get_results("SHOW TABLES", ARRAY_N);
        
        if (!$tables) {
            return [];
        }
        
        return array_map(function($table) {
            return $table[0];
        }, $tables);
    }
    
    /**
     * دریافت حجم دیتابیس (به مگابایت)
     */
    public function get_database_size($db_name) {
        $config = $this->config_manager->get_database_config($db_name);
        
        if (!$config) {
            return 0;
        }
        
        $connection = $this->get_connection($db_name);
        
        if (!$connection) {
            return 0;
        }
        
        $result = $connection->get_var($connection->prepare(
            "SELECT SUM(data_length + index_length) / 1024 / 1024 AS size 
             FROM information_schema.TABLES 
             WHERE table_schema = %s",
            $config['database']
        ));
        
        return round($result ?? 0, 2);
    }
    
    /**
     * دریافت تعداد رکوردها در جدول
     */
    public function get_table_row_count($db_name, $table_name) {
        $connection = $this->get_connection($db_name);
        
        if (!$connection) {
            return 0;
        }
        
        $count = $connection->get_var("SELECT COUNT(*) FROM `{$table_name}`");
        
        return intval($count);
    }
    
    /**
     * بررسی سلامت دیتابیس
     */
    public function check_database_health($db_name) {
        $health = [
            'status' => 'healthy',
            'connection' => false,
            'size' => 0,
            'tables_count' => 0,
            'errors' => []
        ];
        
        // تست اتصال
        $connection = $this->get_connection($db_name);
        
        if (!$connection) {
            $health['status'] = 'error';
            $health['errors'][] = __('اتصال به دیتابیس ناموفق', 'multi-db-manager');
            return $health;
        }
        
        $health['connection'] = true;
        
        // دریافت حجم
        $health['size'] = $this->get_database_size($db_name);
        
        // دریافت تعداد جداول
        $tables = $this->get_database_tables($db_name);
        $health['tables_count'] = count($tables);
        
        // بررسی فضای دیسک
        if ($health['size'] > 1024) { // بیشتر از 1GB
            $health['status'] = 'warning';
            $health['errors'][] = __('حجم دیتابیس بالاست', 'multi-db-manager');
        }
        
        return $health;
    }
    
    /**
     * حذف دیتابیس
     */
    public function drop_database($db_name) {
        $config = $this->config_manager->get_database_config($db_name);
        
        if (!$config) {
            return new WP_Error('no_config', __('تنظیمات دیتابیس یافت نشد', 'multi-db-manager'));
        }
        
        try {
            $mysqli = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                '',
                $config['port'] ?? 3306
            );
            
            if ($mysqli->connect_error) {
                throw new Exception('اتصال ناموفق');
            }
            
            // حذف دیتابیس
            $sql = "DROP DATABASE IF EXISTS `{$config['database']}`";
            
            if (!$mysqli->query($sql)) {
                throw new Exception('خطا در حذف دیتابیس: ' . $mysqli->error);
            }
            
            $mysqli->close();
            
            // حذف اتصال از کش
            unset($this->connections[$db_name]);
            
            Multi_Database_Manager::log('info', "دیتابیس حذف شد", $db_name);
            
            return true;
            
        } catch (Exception $e) {
            Multi_Database_Manager::log('error', $e->getMessage(), $db_name);
            return new WP_Error('drop_failed', $e->getMessage());
        }
    }
    
    /**
     * بستن تمام اتصالات
     */
    public function close_all_connections() {
        foreach ($this->connections as $connection) {
            if ($connection && $connection->dbh) {
                mysqli_close($connection->dbh);
            }
        }
        
        $this->connections = [];
    }
    
    /**
     * پاک کردن کش اتصالات
     */
    public function clear_connections_cache() {
        $this->connections = [];
        $this->build_post_type_map();
    }
}