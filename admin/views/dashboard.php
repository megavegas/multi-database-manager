<?php
/**
 * صفحه داشبورد اصلی
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// دریافت تنظیمات
$configs = $this->config_manager->get_all_configs();
$active_configs = array_filter($configs, function($config) {
    return $config['is_active'];
});

// دریافت آمار کلی
global $wpdb;
$total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
$total_size = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(data_length + index_length) / 1024 / 1024 AS size 
     FROM information_schema.TABLES 
     WHERE table_schema = %s",
    DB_NAME
));

?>

<div class="container-fluid mt-4">
    
    <!-- ردیف اول: آمار کلی -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="dashicons dashicons-database" style="font-size: 48px; color: #2271b1;"></i>
                    <h3 class="mt-2"><?php echo count($configs); ?></h3>
                    <p class="text-muted mb-0"><?php _e('کل دیتابیس‌ها', 'multi-db-manager'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="dashicons dashicons-yes-alt" style="font-size: 48px; color: #28a745;"></i>
                    <h3 class="mt-2"><?php echo count($active_configs); ?></h3>
                    <p class="text-muted mb-0"><?php _e('دیتابیس فعال', 'multi-db-manager'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="dashicons dashicons-admin-post" style="font-size: 48px; color: #17a2b8;"></i>
                    <h3 class="mt-2"><?php echo number_format($total_posts); ?></h3>
                    <p class="text-muted mb-0"><?php _e('کل پست‌ها', 'multi-db-manager'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <i class="dashicons dashicons-chart-area" style="font-size: 48px; color: #ffc107;"></i>
                    <h3 class="mt-2"><?php echo round($total_size, 2); ?> MB</h3>
                    <p class="text-muted mb-0"><?php _e('حجم کل', 'multi-db-manager'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ردیف دوم: دیتابیس‌های فعال -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-admin-settings"></i>
                        <?php _e('دیتابیس‌های فعال', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($active_configs)): ?>
                        <div class="alert alert-info">
                            <i class="dashicons dashicons-info"></i>
                            <?php _e('هیچ دیتابیس فعالی وجود ندارد. برای شروع یک دیتابیس جدید اضافه کنید.', 'multi-db-manager'); ?>
                            <a href="<?php echo admin_url('admin.php?page=multi-db-config'); ?>" class="btn btn-sm btn-primary ms-2">
                                <?php _e('افزودن دیتابیس', 'multi-db-manager'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php _e('نام', 'multi-db-manager'); ?></th>
                                        <th><?php _e('دیتابیس', 'multi-db-manager'); ?></th>
                                        <th><?php _e('پست تایپ‌ها', 'multi-db-manager'); ?></th>
                                        <th><?php _e('حجم', 'multi-db-manager'); ?></th>
                                        <th><?php _e('وضعیت', 'multi-db-manager'); ?></th>
                                        <th><?php _e('عملیات', 'multi-db-manager'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_configs as $name => $config): ?>
                                        <?php 
                                        $health = $this->database_manager->check_database_health($name);
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($name); ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo esc_html($config['data']['database']); ?></code>
                                            </td>
                                            <td>
                                                <?php 
                                                $post_types = $config['data']['post_types'] ?? [];
                                                if (!empty($post_types)): 
                                                ?>
                                                    <span class="badge bg-secondary"><?php echo count($post_types); ?></span>
                                                    <?php echo esc_html(implode(', ', array_slice($post_types, 0, 3))); ?>
                                                    <?php if (count($post_types) > 3): ?>
                                                        <small class="text-muted">...</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted"><?php _e('ندارد', 'multi-db-manager'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo $health['size']; ?> MB</strong>
                                            </td>
                                            <td>
                                                <?php echo $this->get_status_badge($health['status']); ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo admin_url('admin.php?page=multi-db-config&edit=' . $name); ?>" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="<?php _e('ویرایش', 'multi-db-manager'); ?>">
                                                    <i class="dashicons dashicons-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-info refresh-stats" 
                                                        data-db="<?php echo esc_attr($name); ?>"
                                                        title="<?php _e('به‌روزرسانی', 'multi-db-manager'); ?>">
                                                    <i class="dashicons dashicons-update"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ردیف سوم: ووکامرس و لاگ‌ها -->
    <div class="row">
        
        <!-- ستون چپ: آمار ووکامرس -->
        <?php if (class_exists('WooCommerce')): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-cart"></i>
                        <?php _e('آمار ووکامرس', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // بررسی وجود دیتابیس ووکامرس
                    $wc_config = $this->config_manager->get_database_config('woocommerce');
                    
                    if ($wc_config):
                        $wc_handler = new Multi_DB_WooCommerce_Handler(
                            $this->database_manager,
                            $this->config_manager
                        );
                        $wc_stats = $wc_handler->get_woocommerce_stats('woocommerce');
                    ?>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="text-primary"><?php echo number_format($wc_stats['products'] ?? 0); ?></h4>
                                <small class="text-muted"><?php _e('محصولات', 'multi-db-manager'); ?></small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-success"><?php echo number_format($wc_stats['orders'] ?? 0); ?></h4>
                                <small class="text-muted"><?php _e('سفارشات', 'multi-db-manager'); ?></small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info"><?php echo number_format($wc_stats['customers'] ?? 0); ?></h4>
                                <small class="text-muted"><?php _e('مشتریان', 'multi-db-manager'); ?></small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning"><?php echo number_format($wc_stats['coupons'] ?? 0); ?></h4>
                                <small class="text-muted"><?php _e('کوپن‌ها', 'multi-db-manager'); ?></small>
                            </div>
                        </div>
                        <hr>
                        <a href="<?php echo admin_url('admin.php?page=multi-db-woocommerce'); ?>" class="btn btn-sm btn-success w-100">
                            <?php _e('تنظیمات ووکامرس', 'multi-db-manager'); ?>
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="dashicons dashicons-warning"></i>
                            <?php _e('ووکامرس هنوز جداسازی نشده است.', 'multi-db-manager'); ?>
                            <a href="<?php echo admin_url('admin.php?page=multi-db-woocommerce'); ?>" class="alert-link">
                                <?php _e('جداسازی ووکامرس', 'multi-db-manager'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ستون راست: آخرین لاگ‌ها -->
        <div class="<?php echo class_exists('WooCommerce') ? 'col-md-6' : 'col-12'; ?> mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-list-view"></i>
                        <?php _e('آخرین رویدادها', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    global $wpdb;
                    $logs_table = $wpdb->prefix . 'multi_db_logs';
                    $logs = $wpdb->get_results(
                        "SELECT * FROM {$logs_table} ORDER BY created_at DESC LIMIT 10"
                    );
                    
                    if ($logs):
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($logs as $log): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <?php
                                            $badge_class = [
                                                'error' => 'danger',
                                                'warning' => 'warning',
                                                'info' => 'info',
                                                'debug' => 'secondary'
                                            ];
                                            $class = $badge_class[$log->log_level] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>"><?php echo esc_html($log->log_level); ?></span>
                                            <?php if ($log->database_name): ?>
                                                <code class="ms-2"><?php echo esc_html($log->database_name); ?></code>
                                            <?php endif; ?>
                                            <p class="mb-1 mt-2"><?php echo esc_html($log->message); ?></p>
                                        </div>
                                        <small class="text-muted text-nowrap"><?php echo esc_html($log->created_at); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0"><?php _e('رویدادی ثبت نشده است', 'multi-db-manager'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ردیف چهارم: دکمه‌های سریع -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-admin-tools"></i>
                        <?php _e('عملیات سریع', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="<?php echo admin_url('admin.php?page=multi-db-config'); ?>" class="btn btn-primary w-100">
                                <i class="dashicons dashicons-plus-alt"></i>
                                <?php _e('افزودن دیتابیس جدید', 'multi-db-manager'); ?>
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo admin_url('admin.php?page=multi-db-migration'); ?>" class="btn btn-success w-100">
                                <i class="dashicons dashicons-update"></i>
                                <?php _e('انتقال داده‌ها', 'multi-db-manager'); ?>
                            </a>
                        </div>
                        <?php if (class_exists('WooCommerce')): ?>
                        <div class="col-md-3">
                            <a href="<?php echo admin_url('admin.php?page=multi-db-woocommerce'); ?>" class="btn btn-info w-100">
                                <i class="dashicons dashicons-cart"></i>
                                <?php _e('جداسازی ووکامرس', 'multi-db-manager'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-warning w-100" id="refreshAllStats">
                                <i class="dashicons dashicons-update"></i>
                                <?php _e('به‌روزرسانی همه', 'multi-db-manager'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    
    // به‌روزرسانی آمار یک دیتابیس
    $('.refresh-stats').on('click', function() {
        var btn = $(this);
        var dbName = btn.data('db');
        var icon = btn.find('i');
        
        icon.addClass('dashicons-update-alt').css('animation', 'rotation 1s infinite linear');
        
        $.post(multiDbAdmin.ajaxUrl, {
            action: 'multi_db_get_stats',
            nonce: multiDbAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }).always(function() {
            icon.removeClass('dashicons-update-alt').css('animation', '');
        });
    });
    
    // به‌روزرسانی همه آمار
    $('#refreshAllStats').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال به‌روزرسانی...');
        
        $.post(multiDbAdmin.ajaxUrl, {
            action: 'multi_db_get_stats',
            nonce: multiDbAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        }).always(function() {
            btn.prop('disabled', false).html('<i class="dashicons dashicons-update"></i> به‌روزرسانی همه');
        });
    });
});
</script>

<style>
@keyframes rotation {
    from { transform: rotate(0deg); }
    to { transform: rotate(359deg); }
}

.dashicons-update-alt {
    animation: rotation 1s infinite linear;
}
</style>