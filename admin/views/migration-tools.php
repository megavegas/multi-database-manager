<?php
/**
 * صفحه ابزار انتقال داده‌ها
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// دریافت دیتابیس‌های فعال
$configs = $this->config_manager->get_all_active_configs();

// دریافت لیست پست تایپ‌ها
$post_types = $this->get_available_post_types();

// ایجاد Migration Handler
$migration_handler = new Multi_DB_Migration_Handler(
    $this->database_manager,
    $this->config_manager
);

// دریافت لیست backup
$backups = $migration_handler->get_backup_files();

?>

<div class="container-fluid mt-4">
    
    <div class="row">
        
        <!-- ستون چپ: فرم انتقال -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-update"></i>
                        <?php _e('انتقال داده‌ها', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <?php if (empty($configs)): ?>
                        <div class="alert alert-warning">
                            <i class="dashicons dashicons-warning"></i>
                            <?php _e('هیچ دیتابیس فعالی برای انتقال وجود ندارد. ابتدا یک دیتابیس اضافه کنید.', 'multi-db-manager'); ?>
                            <a href="<?php echo admin_url('admin.php?page=multi-db-config'); ?>" class="alert-link">
                                <?php _e('افزودن دیتابیس', 'multi-db-manager'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                    
                    <form id="migrationForm">
                        
                        <!-- انتخاب پست تایپ -->
                        <div class="mb-4">
                            <label for="post_type" class="form-label">
                                <strong><?php _e('انتخاب پست تایپ', 'multi-db-manager'); ?></strong>
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="post_type" name="post_type" required>
                                <option value=""><?php _e('-- انتخاب کنید --', 'multi-db-manager'); ?></option>
                                <?php foreach ($post_types as $type => $label): ?>
                                    <option value="<?php echo esc_attr($type); ?>">
                                        <?php echo esc_html($label); ?> (<?php echo esc_html($type); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="postTypeInfo" class="mt-2"></div>
                        </div>
                        
                        <!-- انتخاب دیتابیس مقصد -->
                        <div class="mb-4">
                            <label for="target_db" class="form-label">
                                <strong><?php _e('دیتابیس مقصد', 'multi-db-manager'); ?></strong>
                                <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="target_db" name="target_db" required>
                                <option value=""><?php _e('-- انتخاب کنید --', 'multi-db-manager'); ?></option>
                                <?php foreach ($configs as $name => $config): ?>
                                    <option value="<?php echo esc_attr($name); ?>">
                                        <?php echo esc_html($name); ?> (<?php echo esc_html($config['database']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <hr>
                        
                        <!-- گزینه‌های انتقال -->
                        <h6 class="mb-3">
                            <i class="dashicons dashicons-admin-settings"></i>
                            <?php _e('تنظیمات انتقال', 'multi-db-manager'); ?>
                        </h6>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="backup_enabled" 
                                   name="backup_enabled"
                                   checked>
                            <label class="form-check-label" for="backup_enabled">
                                <strong><?php _e('پشتیبان‌گیری قبل از انتقال', 'multi-db-manager'); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php _e('توصیه می‌شود همیشه فعال باشد', 'multi-db-manager'); ?>
                                </small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="dry_run" 
                                   name="dry_run">
                            <label class="form-check-label" for="dry_run">
                                <strong><?php _e('حالت تست (Dry Run)', 'multi-db-manager'); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php _e('فقط شبیه‌سازی، بدون انتقال واقعی', 'multi-db-manager'); ?>
                                </small>
                            </label>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="delete_source" 
                                   name="delete_source">
                            <label class="form-check-label" for="delete_source">
                                <strong class="text-danger"><?php _e('حذف از دیتابیس مبدا', 'multi-db-manager'); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php _e('هشدار: داده‌ها از دیتابیس اصلی حذف می‌شوند', 'multi-db-manager'); ?>
                                </small>
                            </label>
                        </div>
                        
                        <!-- دکمه‌های عملیات -->
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-info" id="estimateBtn">
                                <i class="dashicons dashicons-chart-line"></i>
                                <?php _e('تخمین زمان و حجم', 'multi-db-manager'); ?>
                            </button>
                            
                            <button type="submit" class="btn btn-success btn-lg" id="startMigrationBtn">
                                <i class="dashicons dashicons-migrate"></i>
                                <?php _e('شروع انتقال', 'multi-db-manager'); ?>
                            </button>
                        </div>
                        
                    </form>
                    
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- کارت فایل‌های Backup -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-backup"></i>
                        <?php _e('فایل‌های پشتیبان', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <p class="text-muted text-center mb-0">
                            <?php _e('هیچ فایل پشتیبانی موجود نیست', 'multi-db-manager'); ?>
                        </p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($backups, 0, 5) as $backup): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="dashicons dashicons-media-code"></i>
                                        <strong><?php echo esc_html($backup['filename']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo esc_html($backup['date']); ?> 
                                            - <?php echo size_format($backup['size']); ?>
                                        </small>
                                    </div>
                                    <a href="<?php echo esc_url($backup['filepath']); ?>" 
                                       class="btn btn-sm btn-outline-primary"
                                       download>
                                        <i class="dashicons dashicons-download"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- ستون راست: نمایش پیشرفت و لاگ -->
        <div class="col-md-6">
            
            <!-- کارت پیشرفت -->
            <div class="card mb-4" id="progressCard" style="display: none;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-update dashicons-update-alt"></i>
                        <?php _e('پیشرفت انتقال', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><?php _e('در حال انتقال...', 'multi-db-manager'); ?></span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 id="progressBar"
                                 role="progressbar" 
                                 style="width: 0%"
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                0%
                            </div>
                        </div>
                    </div>
                    
                    <div class="row text-center" id="statsContainer">
                        <div class="col-4">
                            <h5 class="text-primary mb-0" id="statCurrent">0</h5>
                            <small class="text-muted"><?php _e('انجام شده', 'multi-db-manager'); ?></small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-info mb-0" id="statTotal">0</h5>
                            <small class="text-muted"><?php _e('کل', 'multi-db-manager'); ?></small>
                        </div>
                        <div class="col-4">
                            <h5 class="text-warning mb-0" id="statRemaining">0</h5>
                            <small class="text-muted"><?php _e('باقیمانده', 'multi-db-manager'); ?></small>
                        </div>
                    </div>
                    
                    <div class="mt-3" id="migrationStatus"></div>
                    
                </div>
            </div>
            
            <!-- کارت نتیجه -->
            <div class="card mb-4" id="resultCard" style="display: none;">
                <div class="card-header" id="resultCardHeader">
                    <h5 class="mb-0" id="resultTitle"></h5>
                </div>
                <div class="card-body" id="resultBody"></div>
            </div>
            
            <!-- کارت راهنما -->
            <div class="card" id="guideCard">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="dashicons dashicons-info"></i>
                        <?php _e('راهنمای انتقال', 'multi-db-manager'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    
                    <h6><?php _e('مراحل انتقال:', 'multi-db-manager'); ?></h6>
                    <ol class="small">
                        <li><?php _e('پست تایپ مورد نظر را انتخاب کنید', 'multi-db-manager'); ?></li>
                        <li><?php _e('دیتابیس مقصد را مشخص کنید', 'multi-db-manager'); ?></li>
                        <li><?php _e('گزینه پشتیبان‌گیری را فعال نگه دارید', 'multi-db-manager'); ?></li>
                        <li><?php _e('ابتدا با "حالت تست" بررسی کنید', 'multi-db-manager'); ?></li>
                        <li><?php _e('در نهایت انتقال واقعی را شروع کنید', 'multi-db-manager'); ?></li>
                    </ol>
                    
                    <hr>
                    
                    <h6><?php _e('نکات مهم:', 'multi-db-manager'); ?></h6>
                    <ul class="small">
                        <li class="text-danger">
                            <strong><?php _e('قبل از انتقال حتماً از سایت backup کامل بگیرید', 'multi-db-manager'); ?></strong>
                        </li>
                        <li><?php _e('انتقال بسته به حجم ممکن است زمان‌بر باشد', 'multi-db-manager'); ?></li>
                        <li><?php _e('در حین انتقال صفحه را نبندید', 'multi-db-manager'); ?></li>
                        <li><?php _e('گزینه "حذف از مبدا" را فقط بعد از اطمینان فعال کنید', 'multi-db-manager'); ?></li>
                    </ul>
                    
                    <div class="alert alert-warning mt-3 mb-0">
                        <small>
                            <i class="dashicons dashicons-warning"></i>
                            <?php _e('توجه: انتقال داده‌های با حجم بالا ممکن است محدودیت زمانی سرور را فعال کند.', 'multi-db-manager'); ?>
                        </small>
                    </div>
                    
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    var migrationInProgress = false;
    
    // تخمین زمان و حجم
    $('#estimateBtn').on('click', function() {
        var postType = $('#post_type').val();
        
        if (!postType) {
            alert('❌ لطفاً ابتدا پست تایپ را انتخاب کنید');
            return;
        }
        
        var btn = $(this);
        var originalText = btn.html();
        btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال محاسبه...');
        
        // فراخوانی AJAX برای تخمین
        // این قسمت را می‌توانید با endpoint مناسب تکمیل کنید
        setTimeout(function() {
            var info = '<div class="alert alert-info mt-2">';
            info += '<strong>تخمین:</strong><br>';
            info += '📊 تعداد پست‌ها: <strong>250</strong><br>';
            info += '⏱️ زمان تقریبی: <strong>5 دقیقه</strong><br>';
            info += '💾 حجم تقریبی: <strong>15 MB</strong>';
            info += '</div>';
            
            $('#postTypeInfo').html(info);
            btn.prop('disabled', false).html(originalText);
        }, 1000);
    });
    
    // شروع انتقال
    $('#migrationForm').on('submit', function(e) {
        e.preventDefault();
        
        if (migrationInProgress) {
            alert('⚠️ یک انتقال در حال انجام است');
            return;
        }
        
        var postType = $('#post_type').val();
        var targetDb = $('#target_db').val();
        var backup = $('#backup_enabled').is(':checked');
        var dryRun = $('#dry_run').is(':checked');
        var deleteSource = $('#delete_source').is(':checked');
        
        if (!postType || !targetDb) {
            alert('❌ لطفاً تمام فیلدهای الزامی را پر کنید');
            return;
        }
        
        if (deleteSource && !confirm('⚠️ هشدار: با فعال کردن این گزینه، داده‌ها از دیتابیس اصلی حذف می‌شوند.\n\nآیا مطمئن هستید؟')) {
            return;
        }
        
        if (!confirm(multiDbAdmin.strings.confirmMigration)) {
            return;
        }
        
        startMigration(postType, targetDb, backup, dryRun, deleteSource);
    });
    
    function startMigration(postType, targetDb, backup, dryRun, deleteSource) {
        migrationInProgress = true;
        
        // مخفی کردن کارت راهنما و نمایش پیشرفت
        $('#guideCard').hide();
        $('#resultCard').hide();
        $('#progressCard').show();
        $('#startMigrationBtn').prop('disabled', true);
        
        // شروع انتقال
        $.post(multiDbAdmin.ajaxUrl, {
            action: 'multi_db_start_migration',
            nonce: multiDbAdmin.nonce,
            post_type: postType,
            target_db: targetDb,
            backup: backup,
            dry_run: dryRun,
            delete_source: deleteSource
        }, function(response) {
            migrationInProgress = false;
            $('#startMigrationBtn').prop('disabled', false);
            
            if (response.success) {
                showResult(true, response.data);
            } else {
                showResult(false, response.data);
            }
        }).fail(function() {
            migrationInProgress = false;
            $('#startMigrationBtn').prop('disabled', false);
            showResult(false, {message: 'خطا در برقراری ارتباط با سرور'});
        });
        
        // شبیه‌سازی پیشرفت
        simulateProgress(postType);
    }
    
    function simulateProgress(postType) {
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 10;
            
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
            }
            
            updateProgress(progress, 100, 250);
        }, 500);
    }
    
    function updateProgress(current, total, estimated) {
        var percent = Math.round((current / total) * 100);
        
        $('#progressBar').css('width', percent + '%').text(percent + '%').attr('aria-valuenow', percent);
        $('#progressPercent').text(percent + '%');
        
        $('#statCurrent').text(Math.round(current));
        $('#statTotal').text(total);
        $('#statRemaining').text(Math.max(0, total - Math.round(current)));
    }
    
    function showResult(success, data) {
        $('#progressCard').hide();
        $('#resultCard').show();
        
        if (success) {
            $('#resultCardHeader').removeClass('bg-danger').addClass('bg-success text-white');
            $('#resultTitle').html('<i class="dashicons dashicons-yes-alt"></i> انتقال با موفقیت انجام شد');
            
            var html = '<div class="alert alert-success">';
            html += '<h6>✅ ' + (data.message || 'انتقال کامل شد') + '</h6>';
            if (data.results) {
                html += '<hr>';
                html += '<p class="mb-1">📊 تعداد پست‌ها: <strong>' + (data.results.migrated_posts || 0) + '</strong></p>';
                html += '<p class="mb-1">🏷️ متادیتاها: <strong>' + (data.results.migrated_meta || 0) + '</strong></p>';
                html += '<p class="mb-1">📁 Terms: <strong>' + (data.results.migrated_terms || 0) + '</strong></p>';
                html += '<p class="mb-0">⏱️ زمان: <strong>' + (data.results.time_taken || 0) + '</strong> ثانیه</p>';
            }
            html += '</div>';
            
            $('#resultBody').html(html);
        } else {
            $('#resultCardHeader').removeClass('bg-success').addClass('bg-danger text-white');
            $('#resultTitle').html('<i class="dashicons dashicons-dismiss"></i> خطا در انتقال');
            
            var html = '<div class="alert alert-danger">';
            html += '<h6>❌ ' + (data.message || 'انتقال ناموفق بود') + '</h6>';
            if (data.errors && data.errors.length > 0) {
                html += '<hr><ul class="mb-0">';
                data.errors.forEach(function(error) {
                    html += '<li>' + error + '</li>';
                });
                html += '</ul>';
            }
            html += '</div>';
            
            $('#resultBody').html(html);
        }
        
        // دکمه بازگشت
        var resetBtn = '<button type="button" class="btn btn-primary" onclick="location.reload()">انتقال جدید</button>';
        $('#resultBody').append(resetBtn);
    }
    
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

.progress {
    border-radius: 8px;
}

.progress-bar {
    font-size: 16px;
    font-weight: bold;
}
</style>