<?php
/**
 * مدیریت تنظیمات و کانفیگ‌های دیتابیس
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت تنظیمات
 */
class Multi_DB_Config_Manager {
    
    /**
     * نام جدول تنظیمات
     */
    private $table_name;
    
    /**
     * کش تنظیمات
     */
    private $configs_cache = [];
    
    /**
     * کلید رمزنگاری
     */
    private $encryption_key;
    
    /**
     * سازنده کلاس
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'multi_db_configs';
        $this->encryption_key = $this->get_encryption_key();
        $this->load_configs_to_cache();
    }
    
    /**
     * دریافت کلید رمزنگاری
     */
    private function get_encryption_key() {
        $key = get_option('multi_db_encryption_key');
        
        if (!$key) {
            // ایجاد کلید جدید
            $key = wp_generate_password(64, true, true);
            update_option('multi_db_encryption_key', $key);
        }
        
        return $key;
    }
    
    /**
     * رمزنگاری داده
     */
    private function encrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * رمزگشایی داده
     */
    private function decrypt($data) {
        if (empty($data)) {
            return $data;
        }
        
        try {
            list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
            return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->encryption_key, 0, $iv);
        } catch (Exception $e) {
            Multi_Database_Manager::log('error', 'خطا در رمزگشایی: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * بارگذاری تنظیمات به کش
     */
    private function load_configs_to_cache() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT config_name, config_data, is_active FROM {$this->table_name}",
            ARRAY_A
        );
        
        if ($results) {
            foreach ($results as $row) {
                $this->configs_cache[$row['config_name']] = [
                    'data' => json_decode($row['config_data'], true),
                    'is_active' => (bool) $row['is_active']
                ];
            }
        }
    }
    
    /**
     * ذخیره تنظیمات دیتابیس
     */
    public function save_database_config($config_name, $config_data) {
        global $wpdb;
        
        // اعتبارسنجی داده‌های ورودی
        $validated_data = $this->validate_config_data($config_data);
        
        if (is_wp_error($validated_data)) {
            return $validated_data;
        }
        
        // رمزنگاری پسورد
        if (!empty($validated_data['password'])) {
            $validated_data['password'] = $this->encrypt($validated_data['password']);
        }
        
        // تبدیل به JSON
        $json_data = wp_json_encode($validated_data);
        
        // بررسی وجود تنظیمات
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE config_name = %s",
            $config_name
        ));
        
        if ($exists) {
            // بروزرسانی
            $result = $wpdb->update(
                $this->table_name,
                [
                    'config_data' => $json_data,
                    'updated_at' => current_time('mysql')
                ],
                ['config_name' => $config_name],
                ['%s', '%s'],
                ['%s']
            );
        } else {
            // درج جدید
            $result = $wpdb->insert(
                $this->table_name,
                [
                    'config_name' => $config_name,
                    'config_data' => $json_data,
                    'is_active' => 1
                ],
                ['%s', '%s', '%d']
            );
        }
        
        if ($result !== false) {
            // بروزرسانی کش
            $this->configs_cache[$config_name] = [
                'data' => $validated_data,
                'is_active' => true
            ];
            
            // حذف transient
            delete_transient('multi_db_databases');
            
            Multi_Database_Manager::log('info', "تنظیمات دیتابیس ذخیره شد", $config_name);
            return true;
        }
        
        return new WP_Error('save_failed', __('خطا در ذخیره تنظیمات', 'multi-db-manager'));
    }
    
    /**
     * اعتبارسنجی داده‌های تنظیمات
     */
    private function validate_config_data($data) {
        $errors = new WP_Error();
        
        // بررسی فیلدهای اجباری
        $required_fields = ['host', 'database', 'username', 'password'];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors->add(
                    'missing_field',
                    sprintf(__('فیلد %s الزامی است', 'multi-db-manager'), $field)
                );
            }
        }
        
        // اعتبارسنجی نام host
        if (!empty($data['host']) && !filter_var($data['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            if ($data['host'] !== 'localhost' && !filter_var($data['host'], FILTER_VALIDATE_IP)) {
                $errors->add('invalid_host', __('آدرس host نامعتبر است', 'multi-db-manager'));
            }
        }
        
        // اعتبارسنجی port
        if (!empty($data['port']) && (!is_numeric($data['port']) || $data['port'] < 1 || $data['port'] > 65535)) {
            $errors->add('invalid_port', __('شماره پورت نامعتبر است', 'multi-db-manager'));
        }
        
        if ($errors->has_errors()) {
            return $errors;
        }
        
        // Sanitize داده‌ها
        $clean_data = [
            'host' => sanitize_text_field($data['host']),
            'database' => sanitize_text_field($data['database']),
            'username' => sanitize_text_field($data['username']),
            'password' => $data['password'], // رمزنگاری بعداً انجام می‌شود
            'port' => !empty($data['port']) ? intval($data['port']) : 3306,
            'charset' => !empty($data['charset']) ? sanitize_text_field($data['charset']) : 'utf8mb4',
            'collate' => !empty($data['collate']) ? sanitize_text_field($data['collate']) : 'utf8mb4_unicode_ci',
            'post_types' => !empty($data['post_types']) ? array_map('sanitize_text_field', (array) $data['post_types']) : [],
            'tables' => !empty($data['tables']) ? array_map('sanitize_text_field', (array) $data['tables']) : [],
            'active' => !empty($data['active']) ? true : false
        ];
        
        return $clean_data;
    }
    
    /**
     * دریافت تنظیمات دیتابیس
     */
    public function get_database_config($config_name) {
        // بررسی کش
        if (isset($this->configs_cache[$config_name])) {
            $config = $this->configs_cache[$config_name]['data'];
            
            // رمزگشایی پسورد
            if (!empty($config['password'])) {
                $config['password'] = $this->decrypt($config['password']);
            }
            
            return $config;
        }
        
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT config_data FROM {$this->table_name} WHERE config_name = %s",
            $config_name
        ), ARRAY_A);
        
        if ($result) {
            $config = json_decode($result['config_data'], true);
            
            // رمزگشایی پسورد
            if (!empty($config['password'])) {
                $config['password'] = $this->decrypt($config['password']);
            }
            
            return $config;
        }
        
        return null;
    }
    
    /**
     * دریافت تمام تنظیمات فعال
     */
    public function get_all_active_configs() {
        $active_configs = [];
        
        foreach ($this->configs_cache as $name => $config) {
            if ($config['is_active']) {
                $data = $config['data'];
                
                // رمزگشایی پسورد
                if (!empty($data['password'])) {
                    $data['password'] = $this->decrypt($data['password']);
                }
                
                $active_configs[$name] = $data;
            }
        }
        
        return $active_configs;
    }
    
    /**
     * دریافت تمام تنظیمات
     */
    public function get_all_configs($include_password = false) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT config_name, config_data, is_active, created_at, updated_at FROM {$this->table_name}",
            ARRAY_A
        );
        
        if (!$results) {
            return [];
        }
        
        $configs = [];
        
        foreach ($results as $row) {
            $data = json_decode($row['config_data'], true);
            
            if (!$include_password) {
                unset($data['password']);
            } elseif (!empty($data['password'])) {
                $data['password'] = $this->decrypt($data['password']);
            }
            
            $configs[$row['config_name']] = [
                'data' => $data,
                'is_active' => (bool) $row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        
        return $configs;
    }
    
    /**
     * حذف تنظیمات
     */
    public function delete_config($config_name) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_name,
            ['config_name' => $config_name],
            ['%s']
        );
        
        if ($result) {
            unset($this->configs_cache[$config_name]);
            delete_transient('multi_db_databases');
            
            Multi_Database_Manager::log('info', "تنظیمات دیتابیس حذف شد", $config_name);
            return true;
        }
        
        return false;
    }
    
    /**
     * فعال/غیرفعال کردن دیتابیس
     */
    public function toggle_config_status($config_name, $status) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            ['is_active' => $status ? 1 : 0],
            ['config_name' => $config_name],
            ['%d'],
            ['%s']
        );
        
        if ($result !== false) {
            if (isset($this->configs_cache[$config_name])) {
                $this->configs_cache[$config_name]['is_active'] = $status;
            }
            
            delete_transient('multi_db_databases');
            
            $status_text = $status ? 'فعال' : 'غیرفعال';
            Multi_Database_Manager::log('info', "دیتابیس {$status_text} شد", $config_name);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * دریافت دیتابیس برای پست تایپ
     */
    public function get_database_for_post_type($post_type) {
        foreach ($this->configs_cache as $config_name => $config) {
            if (!$config['is_active']) {
                continue;
            }
            
            if (in_array($post_type, $config['data']['post_types'] ?? [])) {
                return $config_name;
            }
        }
        
        return null;
    }
    
    /**
     * تست اتصال دیتابیس
     */
    public function test_connection($config_data) {
        $mysqli = new mysqli(
            $config_data['host'],
            $config_data['username'],
            $config_data['password'],
            $config_data['database'],
            $config_data['port'] ?? 3306
        );
        
        if ($mysqli->connect_error) {
            return new WP_Error(
                'connection_failed',
                sprintf(__('اتصال ناموفق: %s', 'multi-db-manager'), $mysqli->connect_error)
            );
        }
        
        // تست query ساده
        $result = $mysqli->query("SELECT 1");
        
        $mysqli->close();
        
        if ($result) {
            return true;
        }
        
        return new WP_Error('query_failed', __('اتصال برقرار شد اما اجرای کوئری ناموفق بود', 'multi-db-manager'));
    }
    
    /**
     * پاک کردن کش
     */
    public function clear_cache() {
        $this->configs_cache = [];
        $this->load_configs_to_cache();
        delete_transient('multi_db_databases');
    }
}