<?php
/**
 * صفحه تنظیمات دیتابیس
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// دریافت لیست دیتابیس‌ها
$configs = $this->config_manager->get_all_configs();

// بررسی حالت ویرایش
$edit_mode = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : false;
$edit_config = $edit_mode ? $this->config_manager->get_database_config($edit_mode) : null;

// دریافت لیست پست تایپ‌ها
$post_types = $this->get_available_post_types();

?>

<div class="container-fluid mt-4">
    
    <div class="row">
        
        <!-- ستون چپ: فرم افزودن/ویرایش -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-<?php echo $edit_mode ? 'edit' : 'plus-alt'; ?>"></i>
                        <?php echo $edit_mode ? __('ویرایش دیتابیس', 'multi-db-manager') : __('افزودن دیتابیس جدید', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" id="databaseConfigForm">
                        <?php wp_nonce_field('multi_db_config_nonce'); ?>
                        
                        <!-- نام دیتابیس -->
                        <div class="mb-3">
                            <label for="config_name" class="form-label">
                                <?php _e('نام شناسایی', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="config_name" 
                                   name="config_name" 
                                   value="<?php echo $edit_mode ? esc_attr($edit_mode) : ''; ?>"
                                   <?php echo $edit_mode ? 'readonly' : ''; ?>
                                   placeholder="مثال: woocommerce"
                                   required>
                            <small class="form-text text-muted">
                                <?php _e('نام یکتا برای شناسایی دیتابیس (فقط حروف انگلیسی و خط تیره)', 'multi-db-manager'); ?>
                            </small>
                        </div>
                        
                        <hr>
                        
                        <!-- اطلاعات اتصال -->
                        <h6 class="mb-3">
                            <i class="dashicons dashicons-admin-network"></i>
                            <?php _e('اطلاعات اتصال', 'multi-db-manager'); ?>
                        </h6>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="host" class="form-label">
                                    <?php _e('Host', 'multi-db-manager'); ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="host" 
                                       name="host" 
                                       value="<?php echo $edit_config ? esc_attr($edit_config['host']) : 'localhost'; ?>"
                                       required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="port" class="form-label">
                                    <?php _e('Port', 'multi-db-manager'); ?>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="port" 
                                       name="port" 
                                       value="<?php echo $edit_config ? esc_attr($edit_config['port']) : '3306'; ?>"
                                       min="1" 
                                       max="65535">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="database" class="form-label">
                                <?php _e('نام دیتابیس', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="database" 
                                   name="database" 
                                   value="<?php echo $edit_config ? esc_attr($edit_config['database']) : ''; ?>"
                                   placeholder="wp_database_name"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <?php _e('نام کاربری', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   value="<?php echo $edit_config ? esc_attr($edit_config['username']) : ''; ?>"
                                   autocomplete="off"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <?php _e('رمز عبور', 'multi-db-manager'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       value="<?php echo $edit_config && !empty($edit_config['password']) ? '••••••••' : ''; ?>"
                                       autocomplete="new-password"
                                       <?php echo !$edit_mode ? 'required' : ''; ?>>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword">
                                    <i class="dashicons dashicons-visibility"></i>
                                </button>
                            </div>
                            <?php if ($edit_mode): ?>
                            <small class="form-text text-muted">
                                <?php _e('برای تغییر رمز، رمز جدید را وارد کنید. در غیر این صورت خالی بگذارید.', 'multi-db-manager'); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="charset" class="form-label">
                                    <?php _e('Charset', 'multi-db-manager'); ?>
                                </label>
                                <select class="form-select" id="charset" name="charset">
                                    <option value="utf8mb4" <?php selected($edit_config['charset'] ?? 'utf8mb4', 'utf8mb4'); ?>>utf8mb4</option>
                                    <option value="utf8" <?php selected($edit_config['charset'] ?? '', 'utf8'); ?>>utf8</option>
                                    <option value="latin1" <?php selected($edit_config['charset'] ?? '', 'latin1'); ?>>latin1</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="collate" class="form-label">
                                    <?php _e('Collation', 'multi-db-manager'); ?>
                                </label>
                                <select class="form-select" id="collate" name="collate">
                                    <option value="utf8mb4_unicode_ci" <?php selected($edit_config['collate'] ?? 'utf8mb4_unicode_ci', 'utf8mb4_unicode_ci'); ?>>utf8mb4_unicode_ci</option>
                                    <option value="utf8mb4_general_ci" <?php selected($edit_config['collate'] ?? '', 'utf8mb4_general_ci'); ?>>utf8mb4_general_ci</option>
                                    <option value="utf8_general_ci" <?php selected($edit_config['collate'] ?? '', 'utf8_general_ci'); ?>>utf8_general_ci</option>
                                </select>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- پست تایپ‌ها -->
                        <h6 class="mb-3">
                            <i class="dashicons dashicons-admin-post"></i>
                            <?php _e('پست تایپ‌ها', 'multi-db-manager'); ?>
                        </h6>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <?php _e('انتخاب پست تایپ‌ها', 'multi-db-manager'); ?>
                            </label>
                            <div class="post-types-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px;">
                                <?php foreach ($post_types as $type => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="post_types[]" 
                                           value="<?php echo esc_attr($type); ?>"
                                           id="post_type_<?php echo esc_attr($type); ?>"
                                           <?php echo ($edit_config && in_array($type, $edit_config['post_types'] ?? [])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="post_type_<?php echo esc_attr($type); ?>">
                                        <strong><?php echo esc_html($label); ?></strong>
                                        <small class="text-muted">(<?php echo esc_html($type); ?>)</small>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">
                                <?php _e('پست تایپ‌هایی که در این دیتابیس ذخیره شوند', 'multi-db-manager'); ?>
                            </small>
                        </div>
                        
                        <hr>
                        
                        <!-- تنظیمات اضافی -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="active" 
                                   id="active"
                                   <?php echo ($edit_config && ($edit_config['active'] ?? false)) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="active">
                                <strong><?php _e('فعال سازی این دیتابیس', 'multi-db-manager'); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php _e('اگر فعال نباشد، از این دیتابیس استفاده نخواهد شد', 'multi-db-manager'); ?>
                                </small>
                            </label>
                        </div>
                        
                        <!-- دکمه‌های عملیات -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-info" id="testConnectionBtn">
                                <i class="dashicons dashicons-networking"></i>
                                <?php _e('تست اتصال', 'multi-db-manager'); ?>
                            </button>
                            
                            <button type="submit" name="multi_db_save_config" class="btn btn-primary">
                                <i class="dashicons dashicons-saved"></i>
                                <?php echo $edit_mode ? __('بروزرسانی', 'multi-db-manager') : __('ذخیره', 'multi-db-manager'); ?>
                            </button>
                            
                            <?php if ($edit_mode): ?>
                            <a href="<?php echo admin_url('admin.php?page=multi-db-config'); ?>" class="btn btn-secondary">
                                <i class="dashicons dashicons-no-alt"></i>
                                <?php _e('انصراف', 'multi-db-manager'); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- ستون راست: لیست دیتابیس‌ها -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-list-view"></i>
                        <?php _e('دیتابیس‌های موجود', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($configs)): ?>
                        <div class="alert alert-info">
                            <i class="dashicons dashicons-info"></i>
                            <?php _e('هیچ دیتابیسی تعریف نشده است. از فرم سمت چپ اولین دیتابیس را اضافه کنید.', 'multi-db-manager'); ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php _e('نام', 'multi-db-manager'); ?></th>
                                        <th><?php _e('دیتابیس', 'multi-db-manager'); ?></th>
                                        <th><?php _e('Host', 'multi-db-manager'); ?></th>
                                        <th><?php _e('پست تایپ', 'multi-db-manager'); ?></th>
                                        <th><?php _e('وضعیت', 'multi-db-manager'); ?></th>
                                        <th><?php _e('عملیات', 'multi-db-manager'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configs as $name => $config): ?>
                                    <tr class="<?php echo $edit_mode === $name ? 'table-primary' : ''; ?>">
                                        <td>
                                            <strong><?php echo esc_html($name); ?></strong>
                                        </td>
                                        <td>
                                            <code><?php echo esc_html($config['data']['database']); ?></code>
                                        </td>
                                        <td>
                                            <code><?php echo esc_html($config['data']['host']); ?></code>
                                        </td>
                                        <td>
                                            <?php 
                                            $types = $config['data']['post_types'] ?? [];
                                            if (!empty($types)): 
                                            ?>
                                                <span class="badge bg-info"><?php echo count($types); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($config['is_active']): ?>
                                                <span class="badge bg-success"><?php _e('فعال', 'multi-db-manager'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php _e('غیرفعال', 'multi-db-manager'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo admin_url('admin.php?page=multi-db-config&edit=' . $name); ?>" 
                                                   class="btn btn-outline-primary"
                                                   title="<?php _e('ویرایش', 'multi-db-manager'); ?>">
                                                    <i class="dashicons dashicons-edit"></i>
                                                </a>
                                                
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=multi-db-config&action=toggle_status&config_name=' . $name), 'multi_db_toggle_' . $name); ?>"
                                                   class="btn btn-outline-warning"
                                                   title="<?php echo $config['is_active'] ? __('غیرفعال کردن', 'multi-db-manager') : __('فعال کردن', 'multi-db-manager'); ?>">
                                                    <i class="dashicons dashicons-<?php echo $config['is_active'] ? 'dismiss' : 'yes-alt'; ?>"></i>
                                                </a>
                                                
                                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=multi-db-config&action=delete_config&config_name=' . $name), 'multi_db_delete_' . $name); ?>"
                                                   class="btn btn-outline-danger delete-config"
                                                   title="<?php _e('حذف', 'multi-db-manager'); ?>">
                                                    <i class="dashicons dashicons-trash"></i>
                                                </a>
                                            </div>
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
</div>

<script>
jQuery(document).ready(function($) {
    
    // نمایش/مخفی کردن رمز عبور
    $('#togglePassword').on('click', function() {
        var passwordField = $('#password');
        var icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });
    
    // تست اتصال
    $('#testConnectionBtn').on('click', function() {
        var btn = $(this);
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> ' + multiDbAdmin.strings.testing);
        
        $.post(multiDbAdmin.ajaxUrl, {
            action: 'multi_db_test_connection',
            nonce: multiDbAdmin.nonce,
            host: $('#host').val(),
            database: $('#database').val(),
            username: $('#username').val(),
            password: $('#password').val(),
            port: $('#port').val()
        }, function(response) {
            if (response.success) {
                alert('✅ ' + response.data.message);
            } else {
                alert('❌ ' + response.data.message);
            }
        }).fail(function() {
            alert('❌ خطا در برقراری ارتباط با سرور');
        }).always(function() {
            btn.prop('disabled', false).html(originalText);
        });
    });
    
    // تایید حذف
    $('.delete-config').on('click', function(e) {
        if (!confirm(multiDbAdmin.strings.confirmDelete)) {
            e.preventDefault();
            return false;
        }
    });
    
    // اعتبارسنجی فرم
    $('#databaseConfigForm').on('submit', function(e) {
        var configName = $('#config_name').val();
        
        // بررسی فرمت نام
        if (!/^[a-z0-9_-]+$/.test(configName)) {
            alert('❌ نام شناسایی فقط می‌تواند شامل حروف انگلیسی کوچک، اعداد، خط تیره و زیرخط باشد');
            e.preventDefault();
            return false;
        }
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

.table-primary {
    background-color: rgba(13, 110, 253, 0.1);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.post-types-list .form-check {
    padding: 5px 10px;
}

.post-types-list .form-check:hover {
    background-color: #f8f9fa;
}
</style>