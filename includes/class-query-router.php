<?php
/**
 * مسیریابی کوئری‌ها به دیتابیس‌های مناسب
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مسیریاب کوئری
 */
class Multi_DB_Query_Router {
    
    /**
     * مدیر دیتابیس
     */
    private $database_manager;
    
    /**
     * مدیر تنظیمات
     */
    private $config_manager;
    
    /**
     * دیتابیس فعلی
     */
    private $current_database = null;
    
    /**
     * کش نقشه کوئری‌ها
     */
    private $query_cache = [];
    
    /**
     * آیا روتینگ فعال است
     */
    private $routing_enabled = true;
    
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
     * راه‌اندازی هوک‌های وردپرس
     */
    private function init_hooks() {
        // هوک‌های Query
        add_filter('posts_request', [$this, 'route_posts_query'], 10, 2);
        add_filter('posts_clauses', [$this, 'modify_posts_clauses'], 10, 2);
        
        // هوک‌های Metadata
        add_filter('get_post_metadata', [$this, 'route_post_metadata'], 10, 4);
        add_filter('update_post_metadata', [$this, 'route_update_post_metadata'], 10, 5);
        add_filter('add_post_metadata', [$this, 'route_add_post_metadata'], 10, 5);
        add_filter('delete_post_metadata', [$this, 'route_delete_post_metadata'], 10, 5);
        
        // هوک‌های Terms
        add_filter('get_term', [$this, 'route_get_term'], 10, 2);
        add_filter('terms_clauses', [$this, 'modify_terms_clauses'], 10, 3);
        
        // بازگشت به دیتابیس پیش‌فرض بعد از کوئری
        add_action('posts_results', [$this, 'restore_default_database'], 999);
    }
    
    /**
     * مسیریابی کوئری posts
     */
    public function route_posts_query($request, $query) {
        if (!$this->routing_enabled) {
            return $request;
        }
        
        // شناسایی post_type از query
        $post_type = $this->detect_post_type($query);
        
        if (!$post_type) {
            return $request;
        }
        
        // دریافت دیتابیس مناسب
        $db_name = $this->database_manager->get_database_for_post_type($post_type);
        
        if (!$db_name) {
            return $request;
        }
        
        // تغییر اتصال به دیتابیس
        $switched = $this->database_manager->switch_to_database($db_name);
        
        if ($switched) {
            $this->current_database = $db_name;
            Multi_Database_Manager::log('debug', "کوئری به دیتابیس {$db_name} مسیریابی شد", $db_name);
        }
        
        return $request;
    }
    
    /**
     * شناسایی post_type از query
     */
    private function detect_post_type($query) {
        if (!is_object($query)) {
            return null;
        }
        
        // بررسی post_type در query
        $post_type = $query->get('post_type');
        
        if ($post_type) {
            // اگر آرایه است، اولین مورد را برمی‌گرداند
            if (is_array($post_type)) {
                return $post_type[0];
            }
            return $post_type;
        }
        
        // بررسی query vars دیگر
        if ($query->is_singular()) {
            global $wpdb;
            
            // دریافت post_type از query string
            if (isset($query->query['name'])) {
                $post_type = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_type FROM {$wpdb->posts} WHERE post_name = %s LIMIT 1",
                    $query->query['name']
                ));
                
                return $post_type;
            }
            
            if (isset($query->query['p'])) {
                $post_type = $wpdb->get_var($wpdb->prepare(
                    "SELECT post_type FROM {$wpdb->posts} WHERE ID = %d LIMIT 1",
                    $query->query['p']
                ));
                
                return $post_type;
            }
        }
        
        // پست تایپ پیش‌فرض
        return 'post';
    }
    
    /**
     * تغییر clauses کوئری posts
     */
    public function modify_posts_clauses($clauses, $query) {
        if (!$this->routing_enabled || !$this->current_database) {
            return $clauses;
        }
        
        global $wpdb;
        
        // جایگزینی نام جداول در JOIN
        if (!empty($clauses['join'])) {
            $clauses['join'] = $this->replace_table_names($clauses['join']);
        }
        
        // جایگزینی نام جداول در WHERE
        if (!empty($clauses['where'])) {
            $clauses['where'] = $this->replace_table_names($clauses['where']);
        }
        
        return $clauses;
    }
    
    /**
     * جایگزینی نام جداول در کوئری
     */
    private function replace_table_names($sql) {
        global $wpdb;
        
        // لیست جداول وردپرس
        $tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->terms,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships,
            $wpdb->comments,
            $wpdb->commentmeta
        ];
        
        // اگر دیتابیس متفاوت است، باید نام کامل جداول را استفاده کنیم
        $config = $this->config_manager->get_database_config($this->current_database);
        
        if ($config) {
            $db_name = $config['database'];
            
            foreach ($tables as $table) {
                // فقط اگر در SQL استفاده شده باشد
                if (strpos($sql, $table) !== false) {
                    $full_table_name = "`{$db_name}`.`{$table}`";
                    $sql = str_replace($table, $full_table_name, $sql);
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * مسیریابی metadata خواندن
     */
    public function route_post_metadata($check, $object_id, $meta_key, $single) {
        if (!$this->routing_enabled) {
            return $check;
        }
        
        // دریافت post_type
        $post_type = get_post_type($object_id);
        
        if (!$post_type) {
            return $check;
        }
        
        // دریافت دیتابیس
        $db_name = $this->database_manager->get_database_for_post_type($post_type);
        
        if (!$db_name) {
            return $check;
        }
        
        // تغییر اتصال
        $this->database_manager->switch_to_database($db_name);
        
        // اجرای کوئری metadata
        global $wpdb;
        
        if ($meta_key) {
            $meta_value = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
                $object_id,
                $meta_key
            ));
        } else {
            $meta_value = $wpdb->get_results($wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
                $object_id
            ));
        }
        
        // بازگشت به دیتابیس پیش‌فرض
        $this->database_manager->restore_database();
        
        return $single ? $meta_value : [$meta_value];
    }
    
    /**
     * مسیریابی metadata بروزرسانی
     */
    public function route_update_post_metadata($check, $object_id, $meta_key, $meta_value, $prev_value) {
        if (!$this->routing_enabled) {
            return $check;
        }
        
        return $this->route_metadata_operation('update', $object_id, $meta_key, $meta_value, $prev_value);
    }
    
    /**
     * مسیریابی metadata اضافه کردن
     */
    public function route_add_post_metadata($check, $object_id, $meta_key, $meta_value, $unique) {
        if (!$this->routing_enabled) {
            return $check;
        }
        
        return $this->route_metadata_operation('add', $object_id, $meta_key, $meta_value, $unique);
    }
    
    /**
     * مسیریابی metadata حذف
     */
    public function route_delete_post_metadata($check, $object_id, $meta_key, $meta_value, $delete_all) {
        if (!$this->routing_enabled) {
            return $check;
        }
        
        return $this->route_metadata_operation('delete', $object_id, $meta_key, $meta_value, $delete_all);
    }
    
    /**
     * انجام عملیات metadata
     */
    private function route_metadata_operation($operation, $object_id, $meta_key, $meta_value, $extra = null) {
        // دریافت post_type
        $post_type = get_post_type($object_id);
        
        if (!$post_type) {
            return null;
        }
        
        // دریافت دیتابیس
        $db_name = $this->database_manager->get_database_for_post_type($post_type);
        
        if (!$db_name) {
            return null;
        }
        
        // تغییر اتصال
        $this->database_manager->switch_to_database($db_name);
        
        global $wpdb;
        $result = null;
        
        // انجام عملیات
        switch ($operation) {
            case 'update':
                if ($extra !== null && $extra !== '') {
                    $result = $wpdb->update(
                        $wpdb->postmeta,
                        ['meta_value' => maybe_serialize($meta_value)],
                        [
                            'post_id' => $object_id,
                            'meta_key' => $meta_key,
                            'meta_value' => maybe_serialize($extra)
                        ],
                        ['%s'],
                        ['%d', '%s', '%s']
                    );
                } else {
                    $result = $wpdb->update(
                        $wpdb->postmeta,
                        ['meta_value' => maybe_serialize($meta_value)],
                        [
                            'post_id' => $object_id,
                            'meta_key' => $meta_key
                        ],
                        ['%s'],
                        ['%d', '%s']
                    );
                }
                break;
                
            case 'add':
                $result = $wpdb->insert(
                    $wpdb->postmeta,
                    [
                        'post_id' => $object_id,
                        'meta_key' => $meta_key,
                        'meta_value' => maybe_serialize($meta_value)
                    ],
                    ['%d', '%s', '%s']
                );
                break;
                
            case 'delete':
                $where = [
                    'post_id' => $object_id,
                    'meta_key' => $meta_key
                ];
                
                if ($meta_value !== null && $meta_value !== '') {
                    $where['meta_value'] = maybe_serialize($meta_value);
                }
                
                $result = $wpdb->delete($wpdb->postmeta, $where);
                break;
        }
        
        // بازگشت به دیتابیس پیش‌فرض
        $this->database_manager->restore_database();
        
        return $result;
    }
    
    /**
     * مسیریابی term
     */
    public function route_get_term($term, $taxonomy) {
        if (!$this->routing_enabled || !$term) {
            return $term;
        }
        
        // اگر taxonomy به پست تایپ خاصی مرتبط است
        $post_types = get_taxonomy($taxonomy)->object_type ?? [];
        
        if (empty($post_types)) {
            return $term;
        }
        
        // دریافت دیتابیس برای اولین پست تایپ
        $db_name = $this->database_manager->get_database_for_post_type($post_types[0]);
        
        if ($db_name) {
            $this->database_manager->switch_to_database($db_name);
            // کوئری دوباره term از دیتابیس جدید
            // ...
            $this->database_manager->restore_database();
        }
        
        return $term;
    }
    
    /**
     * تغییر clauses terms
     */
    public function modify_terms_clauses($clauses, $taxonomies, $args) {
        if (!$this->routing_enabled) {
            return $clauses;
        }
        
        // منطق مشابه modify_posts_clauses
        return $clauses;
    }
    
    /**
     * بازگشت به دیتابیس پیش‌فرض
     */
    public function restore_default_database($posts) {
        if ($this->current_database) {
            $this->database_manager->restore_database();
            $this->current_database = null;
        }
        
        return $posts;
    }
    
    /**
     * غیرفعال کردن موقت روتینگ
     */
    public function disable_routing() {
        $this->routing_enabled = false;
    }
    
    /**
     * فعال کردن روتینگ
     */
    public function enable_routing() {
        $this->routing_enabled = true;
    }
    
    /**
     * بررسی فعال بودن روتینگ
     */
    public function is_routing_enabled() {
        return $this->routing_enabled;
    }
    
    /**
     * پاک کردن کش
     */
    public function clear_cache() {
        $this->query_cache = [];
    }
}