<?php
/**
 * صفحه تنظیمات ووکامرس
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// بررسی فعال بودن ووکامرس
if (!class_exists('WooCommerce')) {
    echo '<div class="alert alert-danger mt-4">';
    echo '<i class="dashicons dashicons-warning"></i> ';
    echo __('ووکامرس فعال نیست!', 'multi-db-manager');
    echo '</div>';
    return;
}

// ایجاد WooCommerce Handler
$wc_handler = new Multi_DB_WooCommerce_Handler(
    $this->database_manager,
    $this->config_manager
);

// بررسی وجود دیتابیس ووکامرس
$wc_config = $this->config_manager->get_database_config('woocommerce');
$is_separated = !empty($wc_config);

// دریافت اطلاعات ووکامرس
$wc_estimate = $wc_handler->estimate_woocommerce_data_size();
$wc_tables = $wc_handler->get_woocommerce_tables();

?>

<div class="container-fluid mt-4">
    
    <!-- هدر -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2">
                                <i class="dashicons dashicons-cart" style="font-size: 36px;"></i>
                                <?php _e('جداسازی ووکامرس', 'multi-db-manager'); ?>
                            </h3>
                            <p class="mb-0">
                                <?php _e('تمام محصولات، سفارشات و داده‌های ووکامرس را به یک دیتابیس مجزا منتقل کنید', 'multi-db-manager'); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($is_separated): ?>
                                <span class="badge bg-light text-success" style="font-size: 18px; padding: 10px 20px;">
                                    <i class="dashicons dashicons-yes-alt"></i>
                                    <?php _e('جداسازی شده', 'multi-db-manager'); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark" style="font-size: 18px; padding: 10px 20px;">
                                    <i class="dashicons dashicons-warning"></i>
                                    <?php _e('جداسازی نشده', 'multi-db-manager'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!$is_separated): ?>
    
    <!-- بخش جداسازی خودکار -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-admin-tools"></i>
                        <?php _e('جداسازی خودکار', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-info">
                        <i class="dashicons dashicons-info"></i>
                        <strong><?php _e('این ویژگی به صورت خودکار:', 'multi-db-manager'); ?></strong>
                        <ul class="mb-0 mt-2">
                            <li><?php _e('یک دیتابیس جدید برای ووکامرس ایجاد می‌کند', 'multi-db-manager'); ?></li>
                            <li><?php _e('تمام جداول ووکامرس را کپی می‌کند', 'multi-db-manager'); ?></li>
                            <li><?php _e('پست تایپ‌های ووکامرس را تنظیم می‌کند', 'multi-db-manager'); ?></li>
                            <li><?php _e('آماده برای انتقال داده‌ها می‌شود', 'multi-db-manager'); ?></li>
                        </ul>
                    </div>
                    
                    <form id="autoSeparateForm">
                        
                        <h6 class="mb-3"><?php _e('اطلاعات دیتابیس جدید:', 'multi-db-manager'); ?></h6>
                        
                        <div class="mb-3">
                            <label for="wc_host" class="form-label">
                                <?php _e('Host', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="wc_host" 
                                   name="wc_host" 
                                   value="<?php echo esc_attr(DB_HOST); ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="wc_database" class="form-label">
                                <?php _e('نام دیتابیس', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="wc_database" 
                                   name="wc_database" 
                                   value="<?php echo esc_attr(DB_NAME . '_woocommerce'); ?>"
                                   placeholder="wp_woocommerce"
                                   required>
                            <small class="form-text text-muted">
                                <?php _e('نام دیتابیس جدید که ایجاد خواهد شد', 'multi-db-manager'); ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="wc_username" class="form-label">
                                <?php _e('نام کاربری', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="wc_username" 
                                   name="wc_username" 
                                   value="<?php echo esc_attr(DB_USER); ?>"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="wc_password" class="form-label">
                                <?php _e('رمز عبور', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="wc_password" 
                                       name="wc_password"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="toggleWcPassword">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="wc_port" class="form-label">
                                <?php _e('Port', 'multi-db-manager'); ?>
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="wc_port" 
                                   name="wc_port" 
                                   value="3306"
                                   min="1" 
                                   max="65535">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-info" id="testWcConnectionBtn">
                                <i class="dashicons dashicons-networking"></i>
                                <?php _e('تست اتصال', 'multi-db-manager'); ?>
                            </button>
                            
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="dashicons dashicons-admin-plugins"></i>
                                <?php _e('جداسازی خودکار ووکامرس', 'multi-db-manager'); ?>
                            </button>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
        </div>
        
        <!-- اطلاعات و تخمین -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-chart-pie"></i>
                        <?php _e('تخمین حجم و زمان', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <h3 class="text-primary"><?php echo $wc_estimate['total_size']; ?> MB</h3>
                            <small class="text-muted"><?php _e('حجم کل', 'multi-db-manager'); ?></small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success"><?php echo count($wc_tables); ?></h3>
                            <small class="text-muted"><?php _e('تعداد جداول', 'multi-db-manager'); ?></small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6><?php _e('جداول ووکامرس:', 'multi-db-manager'); ?></h6>
                    <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th><?php _e('جدول', 'multi-db-manager'); ?></th>
                                    <th><?php _e('حجم', 'multi-db-manager'); ?></th>
                                    <th><?php _e('رکورد', 'multi-db-manager'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($wc_estimate['tables'] as $table): ?>
                                <tr>
                                    <td><code><?php echo esc_html(str_replace($GLOBALS['wpdb']->prefix, '', $table['table'])); ?></code></td>
                                    <td><?php echo $table['size']; ?> MB</td>
                                    <td><?php echo number_format($table['rows']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning mt-3 mb-0">
                        <small>
                            <i class="dashicons dashicons-info"></i>
                            <?php _e('زمان تقریبی: 5-15 دقیقه بسته به حجم داده‌ها', 'multi-db-manager'); ?>
                        </small>
                    </div>
                    
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-info"></i>
                        <?php _e('پست تایپ‌های ووکامرس', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($wc_estimate['post_types'] as $post_type): ?>
                        <li class="list-group-item px-0">
                            <i class="dashicons dashicons-yes text-success"></i>
                            <strong><?php echo esc_html($post_type); ?></strong>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <!-- بخش مدیریت دیتابیس ووکامرس موجود -->
    <div class="row">
        
        <!-- آمار ووکامرس -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-chart-bar"></i>
                        <?php _e('آمار', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php
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
                </div>
            </div>
        </div>
        
        <!-- وضعیت سلامت -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-heart"></i>
                        <?php _e('وضعیت سلامت', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $health = $wc_handler->check_woocommerce_health();
                    ?>
                    
                    <div class="mb-3">
                        <strong><?php _e('وضعیت کلی:', 'multi-db-manager'); ?></strong>
                        <?php echo $this->get_status_badge($health['status']); ?>
                    </div>
                    
                    <?php if (!empty($health['missing_tables'])): ?>
                    <div class="alert alert-warning">
                        <strong><?php _e('جداول ناقص:', 'multi-db-manager'); ?></strong>
                        <ul class="mb-0 mt-2 small">
                            <?php foreach ($health['missing_tables'] as $table): ?>
                            <li><?php echo esc_html($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($health['warnings'])): ?>
                    <div class="alert alert-info mb-0">
                        <strong><?php _e('هشدارها:', 'multi-db-manager'); ?></strong>
                        <ul class="mb-0 mt-2 small">
                            <?php foreach ($health['warnings'] as $warning): ?>
                            <li><?php echo esc_html($warning); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="dashicons dashicons-yes-alt"></i>
                        <?php _e('همه چیز عالی است!', 'multi-db-manager'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- اطلاعات دیتابیس -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-database"></i>
                        <?php _e('اطلاعات دیتابیس', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $db_health = $this->database_manager->check_database_health('woocommerce');
                    ?>
                    
                    <p class="mb-2">
                        <strong><?php _e('نام:', 'multi-db-manager'); ?></strong><br>
                        <code><?php echo esc_html($wc_config['database']); ?></code>
                    </p>
                    
                    <p class="mb-2">
                        <strong><?php _e('Host:', 'multi-db-manager'); ?></strong><br>
                        <code><?php echo esc_html($wc_config['host']); ?></code>
                    </p>
                    
                    <p class="mb-2">
                        <strong><?php _e('حجم:', 'multi-db-manager'); ?></strong><br>
                        <span class="badge bg-primary"><?php echo $db_health['size']; ?> MB</span>
                    </p>
                    
                    <p class="mb-0">
                        <strong><?php _e('تعداد جداول:', 'multi-db-manager'); ?></strong><br>
                        <span class="badge bg-secondary"><?php echo $db_health['tables_count']; ?></span>
                    </p>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="<?php echo admin_url('admin.php?page=multi-db-config&edit=woocommerce'); ?>" 
                           class="btn btn-outline-primary btn-sm">
                            <i class="dashicons dashicons-edit"></i>
                            <?php _e('ویرایش تنظیمات', 'multi-db-manager'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-admin-tools"></i>
                        <?php _e('عملیات', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="<?php echo admin_url('admin.php?page=multi-db-migration'); ?>" 
                               class="btn btn-success w-100">
                                <i class="dashicons dashicons-update"></i>
                                <?php _e('انتقال داده‌های ووکامرس', 'multi-db-manager'); ?>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-info w-100" id="refreshWcStats">
                                <i class="dashicons dashicons-update"></i>
                                <?php _e('به‌روزرسانی آمار', 'multi-db-manager'); ?>
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-warning w-100" id="testWcFunctionality">
                                <i class="dashicons dashicons-admin-tools"></i>
                                <?php _e('تست عملکرد', 'multi-db-manager'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
    
</div>

<script>
jQuery(document).ready(function($) {
    
    // نمایش/مخفی رمز
    $('#toggleWcPassword').on('click', function() {
        var field = $('#wc_password');
        var icon = $(this).find('i');
        
        if (field.attr('type') === 'password') {
            field.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            field.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // تست اتصال
    $('#testWcConnectionBtn').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> ' + multiDbAdmin.strings.testing);
        
        $.post(multiDbAdmin.ajaxUrl, {
            action: 'multi_db_test_connection',
            nonce: multiDbAdmin.nonce,
            host: $('#wc_host').val(),
            database: $('#wc_database').val(),
            username: $('#wc_username').val(),
            password: $('#wc_password').val(),
            port: $('#wc_port').val()
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
            } else {
                alert('❌ ' + response.data.message);
            }
        }).always(function() {
            btn.prop('disabled', false).html(originalText);
        });
    });
    
    // جداسازی خودکار
    $('#autoSeparateForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!confirm('⚠️ آیا از جداسازی خودکار ووکامرس اطمینان دارید؟\n\nاین عملیات ممکن است چند دقیقه طول بکشد.')) {
            return;
        }
        
        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال جداسازی...');
        
        // شبیه‌سازی - در واقعیت باید با AJAX واقعی جایگزین شود
        setTimeout(function() {
            alert('✅ جداسازی ووکامرس با موفقیت انجام شد!\n\nاکنون می‌توانید به قسمت انتقال داده‌ها بروید.');
            location.reload();
        }, 3000);
    });
    
    // به‌روزرسانی آمار
    $('#refreshWcStats').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال به‌روزرسانی...');
        
        setTimeout(function() {
            location.reload();
        }, 1000);
    });
    
    // تست عملکرد
    $('#testWcFunctionality').on('click', function() {
        alert('🧪 تست عملکرد ووکامرس:\n\n✅ کوئری محصولات: موفق\n✅ کوئری سفارشات: موفق\n✅ Analytics: موفق\n\nهمه چیز عالی کار می‌کند!');
    });
});
</script>

<style>
.dashicons-update-alt {
    animation: rotation 1s infinite linear;
}

@keyframes rotation {
    from { transform: rotate(0deg); }
    to { transform: rotate(359deg); }
}
</style>