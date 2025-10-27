<?php
/**
 * مدیریت انتقال داده‌ها بین دیتابیس‌ها
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * کلاس مدیریت انتقال داده‌ها
 */
class Multi_DB_Migration_Handler {
    
    /**
     * مدیر دیتابیس
     */
    private $database_manager;
    
    /**
     * مدیر تنظیمات
     */
    private $config_manager;
    
    /**
     * تعداد رکورد در هر Batch
     */
    private $batch_size = 100;
    
    /**
     * مسیر پشتیبان‌گیری
     */
    private $backup_dir;
    
    /**
     * سازنده کلاس
     */
    public function __construct($database_manager = null, $config_manager = null) {
        $this->database_manager = $database_manager;
        $this->config_manager = $config_manager;
        
        // تنظیم مسیر backup
        $upload_dir = wp_upload_dir();
        $this->backup_dir = $upload_dir['basedir'] . '/multi-db-backups';
    }
    
    /**
     * انتقال پست تایپ خاص
     */
    public function migrate_post_type($post_type, $target_db, $options = []) {
        global $wpdb;
        
        Multi_Database_Manager::log('info', "شروع انتقال پست تایپ: {$post_type}", $target_db);
        
        $defaults = [
            'backup' => true,
            'batch_size' => $this->batch_size,
            'delete_source' => false,
            'dry_run' => false
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $results = [
            'success' => false,
            'total_posts' => 0,
            'migrated_posts' => 0,
            'migrated_meta' => 0,
            'migrated_terms' => 0,
            'errors' => [],
            'backup_file' => null,
            'time_taken' => 0
        ];
        
        $start_time = microtime(true);
        
        try {
            // مرحله 1: Backup
            if ($options['backup'] && !$options['dry_run']) {
                $backup_result = $this->create_backup($post_type);
                
                if (is_wp_error($backup_result)) {
                    throw new Exception($backup_result->get_error_message());
                }
                
                $results['backup_file'] = $backup_result;
            }
            
            // مرحله 2: شمارش پست‌ها
            $total_posts = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
                $post_type
            ));
            
            $results['total_posts'] = intval($total_posts);
            
            if ($results['total_posts'] == 0) {
                throw new Exception('هیچ پستی برای انتقال یافت نشد');
            }
            
            // مرحله 3: دریافت اتصال به دیتابیس مقصد
            $target_connection = $this->database_manager->get_connection($target_db);
            
            if (!$target_connection) {
                throw new Exception('اتصال به دیتابیس مقصد ناموفق');
            }
            
            // مرحله 4: انتقال به صورت Batch
            $offset = 0;
            $batch_size = $options['batch_size'];
            
            while ($offset < $results['total_posts']) {
                // دریافت دسته‌ای از پست‌ها
                $posts = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->posts} 
                     WHERE post_type = %s 
                     ORDER BY ID 
                     LIMIT %d OFFSET %d",
                    $post_type,
                    $batch_size,
                    $offset
                ), ARRAY_A);
                
                foreach ($posts as $post) {
                    if (!$options['dry_run']) {
                        // انتقال پست
                        $insert_result = $this->migrate_single_post($post, $target_connection);
                        
                        if ($insert_result) {
                            $results['migrated_posts']++;
                            
                            // انتقال متادیتا
                            $meta_count = $this->migrate_post_meta($post['ID'], $target_connection);
                            $results['migrated_meta'] += $meta_count;
                            
                            // انتقال terms
                            $terms_count = $this->migrate_post_terms($post['ID'], $target_connection);
                            $results['migrated_terms'] += $terms_count;
                            
                            // حذف از دیتابیس مبدا (اگر فعال باشد)
                            if ($options['delete_source']) {
                                $this->delete_post_from_source($post['ID']);
                            }
                        } else {
                            $results['errors'][] = "خطا در انتقال پست ID: {$post['ID']}";
                        }
                    } else {
                        // حالت Dry Run - فقط شمارش
                        $results['migrated_posts']++;
                    }
                }
                
                $offset += $batch_size;
                
                // بروزرسانی Progress
                $this->update_migration_progress($post_type, $offset, $results['total_posts']);
                
                // کمی صبر برای کاهش فشار
                usleep(100000); // 0.1 ثانیه
            }
            
            $results['success'] = true;
            $results['time_taken'] = round(microtime(true) - $start_time, 2);
            
            Multi_Database_Manager::log('info', "انتقال {$post_type} با موفقیت انجام شد", $target_db, $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            Multi_Database_Manager::log('error', 'خطا در انتقال: ' . $e->getMessage(), $target_db);
            
            // Rollback در صورت خطا
            if (!empty($results['backup_file']) && !$options['dry_run']) {
                $this->rollback_from_backup($results['backup_file']);
            }
        }
        
        return $results;
    }
    
    /**
     * انتقال یک پست
     */
    private function migrate_single_post($post, $target_connection) {
        // حذف ID برای insert جدید (اگر نیاز باشد)
        $post_data = $post;
        
        // Insert در دیتابیس مقصد
        $result = $target_connection->insert(
            $target_connection->posts,
            $post_data,
            [
                '%d', '%d', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%d', '%s', '%s', '%s', '%s',
                '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d'
            ]
        );
        
        return $result !== false;
    }
    
    /**
     * انتقال متادیتای پست
     */
    private function migrate_post_meta($post_id, $target_connection) {
        global $wpdb;
        
        $meta_data = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d",
            $post_id
        ), ARRAY_A);
        
        $count = 0;
        
        foreach ($meta_data as $meta) {
            $result = $target_connection->insert(
                $target_connection->postmeta,
                [
                    'post_id' => $meta['post_id'],
                    'meta_key' => $meta['meta_key'],
                    'meta_value' => $meta['meta_value']
                ],
                ['%d', '%s', '%s']
            );
            
            if ($result !== false) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * انتقال terms پست
     */
    private function migrate_post_terms($post_id, $target_connection) {
        global $wpdb;
        
        // دریافت relationships
        $term_relationships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->term_relationships} WHERE object_id = %d",
            $post_id
        ), ARRAY_A);
        
        $count = 0;
        
        foreach ($term_relationships as $relationship) {
            // بررسی و انتقال term و taxonomy
            $this->migrate_term_and_taxonomy($relationship['term_taxonomy_id'], $target_connection);
            
            // انتقال relationship
            $result = $target_connection->insert(
                $target_connection->term_relationships,
                $relationship,
                ['%d', '%d', '%d']
            );
            
            if ($result !== false) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * انتقال term و taxonomy
     */
    private function migrate_term_and_taxonomy($term_taxonomy_id, $target_connection) {
        global $wpdb;
        
        // دریافت term_taxonomy
        $term_taxonomy = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id = %d",
            $term_taxonomy_id
        ), ARRAY_A);
        
        if (!$term_taxonomy) {
            return false;
        }
        
        // بررسی وجود در دیتابیس مقصد
        $exists = $target_connection->get_var($target_connection->prepare(
            "SELECT term_taxonomy_id FROM {$target_connection->term_taxonomy} WHERE term_taxonomy_id = %d",
            $term_taxonomy_id
        ));
        
        if ($exists) {
            return true; // قبلاً منتقل شده
        }
        
        // دریافت term
        $term = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->terms} WHERE term_id = %d",
            $term_taxonomy['term_id']
        ), ARRAY_A);
        
        if ($term) {
            // Insert term
            $term_exists = $target_connection->get_var($target_connection->prepare(
                "SELECT term_id FROM {$target_connection->terms} WHERE term_id = %d",
                $term['term_id']
            ));
            
            if (!$term_exists) {
                $target_connection->insert(
                    $target_connection->terms,
                    $term,
                    ['%d', '%s', '%s', '%d']
                );
            }
        }
        
        // Insert term_taxonomy
        $target_connection->insert(
            $target_connection->term_taxonomy,
            $term_taxonomy,
            ['%d', '%d', '%s', '%s', '%d', '%d']
        );
        
        return true;
    }
    
    /**
     * انتقال جداول خاص
     */
    public function migrate_custom_tables($tables, $target_db, $options = []) {
        global $wpdb;
        
        $defaults = [
            'backup' => true,
            'batch_size' => $this->batch_size,
            'dry_run' => false
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $results = [
            'success' => false,
            'migrated_tables' => [],
            'errors' => []
        ];
        
        $target_connection = $this->database_manager->get_connection($target_db);
        
        if (!$target_connection) {
            $results['errors'][] = 'اتصال به دیتابیس مقصد ناموفق';
            return $results;
        }
        
        foreach ($tables as $table) {
            try {
                // بررسی وجود جدول
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
                
                if (!$table_exists) {
                    $results['errors'][] = "جدول {$table} یافت نشد";
                    continue;
                }
                
                // کپی ساختار
                $create_result = $this->database_manager->copy_table_structure($table, $target_db);
                
                if (is_wp_error($create_result)) {
                    $results['errors'][] = "خطا در کپی ساختار جدول {$table}";
                    continue;
                }
                
                if (!$options['dry_run']) {
                    // انتقال داده‌ها
                    $row_count = $this->migrate_table_data($table, $target_connection, $options['batch_size']);
                    
                    $results['migrated_tables'][] = [
                        'table' => $table,
                        'rows' => $row_count
                    ];
                }
                
            } catch (Exception $e) {
                $results['errors'][] = "خطا در انتقال جدول {$table}: " . $e->getMessage();
            }
        }
        
        $results['success'] = empty($results['errors']);
        
        return $results;
    }
    
    /**
     * انتقال داده‌های جدول
     */
    private function migrate_table_data($table, $target_connection, $batch_size) {
        global $wpdb;
        
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        $migrated = 0;
        $offset = 0;
        
        while ($offset < $total_rows) {
            $rows = $wpdb->get_results(
                "SELECT * FROM `{$table}` LIMIT {$batch_size} OFFSET {$offset}",
                ARRAY_A
            );
            
            foreach ($rows as $row) {
                $target_connection->insert($table, $row);
                $migrated++;
            }
            
            $offset += $batch_size;
            usleep(100000); // 0.1 ثانیه
        }
        
        return $migrated;
    }
    
    /**
     * ایجاد پشتیبان
     */
    private function create_backup($post_type) {
        global $wpdb;
        
        // ایجاد نام فایل backup
        $filename = sprintf(
            'backup_%s_%s.sql',
            $post_type,
            date('Y-m-d_H-i-s')
        );
        
        $filepath = $this->backup_dir . '/' . $filename;
        
        // دریافت پست‌ها
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->posts} WHERE post_type = %s",
            $post_type
        ), ARRAY_A);
        
        $sql = "-- Multi-DB Manager Backup\n";
        $sql .= "-- Post Type: {$post_type}\n";
        $sql .= "-- Date: " . current_time('mysql') . "\n\n";
        
        foreach ($posts as $post) {
            $sql .= $wpdb->prepare(
                "INSERT INTO {$wpdb->posts} VALUES (%d, %d, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %d, %d, %s, %d, %s, %s, %d, %d, %d);\n",
                array_values($post)
            );
        }
        
        // ذخیره فایل
        $result = file_put_contents($filepath, $sql);
        
        if ($result === false) {
            return new WP_Error('backup_failed', 'خطا در ایجاد فایل پشتیبان');
        }
        
        Multi_Database_Manager::log('info', "پشتیبان ایجاد شد: {$filename}", null);
        
        return $filepath;
    }
    
    /**
     * بازگردانی از پشتیبان
     */
    private function rollback_from_backup($backup_file) {
        if (!file_exists($backup_file)) {
            return new WP_Error('no_backup', 'فایل پشتیبان یافت نشد');
        }
        
        global $wpdb;
        
        $sql = file_get_contents($backup_file);
        
        if ($sql === false) {
            return new WP_Error('read_failed', 'خطا در خواندن فایل پشتیبان');
        }
        
        // اجرای کوئری‌ها
        $wpdb->query($sql);
        
        Multi_Database_Manager::log('info', "بازگردانی از پشتیبان انجام شد", null);
        
        return true;
    }
    
    /**
     * حذف پست از دیتابیس مبدا
     */
    private function delete_post_from_source($post_id) {
        global $wpdb;
        
        // حذف postmeta
        $wpdb->delete($wpdb->postmeta, ['post_id' => $post_id], ['%d']);
        
        // حذف term_relationships
        $wpdb->delete($wpdb->term_relationships, ['object_id' => $post_id], ['%d']);
        
        // حذف post
        $wpdb->delete($wpdb->posts, ['ID' => $post_id], ['%d']);
    }
    
    /**
     * بروزرسانی پیشرفت انتقال
     */
    private function update_migration_progress($post_type, $current, $total) {
        $progress = round(($current / $total) * 100, 2);
        
        set_transient(
            'multi_db_migration_progress_' . $post_type,
            [
                'current' => $current,
                'total' => $total,
                'progress' => $progress,
                'updated_at' => current_time('mysql')
            ],
            3600
        );
    }
    
    /**
     * دریافت پیشرفت انتقال
     */
    public function get_migration_progress($post_type) {
        return get_transient('multi_db_migration_progress_' . $post_type);
    }
    
    /**
     * تخمین زمان انتقال
     */
    public function estimate_migration_time($post_type) {
        global $wpdb;
        
        $total_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
            $post_type
        ));
        
        // تخمین: هر 100 پست حدود 2 ثانیه
        $estimated_seconds = ($total_posts / 100) * 2;
        
        return [
            'total_posts' => intval($total_posts),
            'estimated_seconds' => round($estimated_seconds),
            'estimated_minutes' => round($estimated_seconds / 60, 2)
        ];
    }
    
    /**
     * دریافت لیست فایل‌های پشتیبان
     */
    public function get_backup_files() {
        if (!file_exists($this->backup_dir)) {
            return [];
        }
        
        $files = glob($this->backup_dir . '/backup_*.sql');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        return $backups;
    }
}