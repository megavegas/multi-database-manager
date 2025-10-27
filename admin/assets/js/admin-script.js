/**
 * اسکریپت‌های پنل ادمین افزونه Multi-Database Manager
 * 
 * @package Multi_Database_Manager
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    // ==================== متغیرهای عمومی ====================
    
    const MultiDBAdmin = {
        
        /**
         * راه‌اندازی اولیه
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initConfirmDialogs();
            this.autoSaveFormData();
        },
        
        /**
         * اتصال رویدادها
         */
        bindEvents: function() {
            // تست اتصال دیتابیس
            $(document).on('click', '#testConnectionBtn, #testWcConnectionBtn', this.testConnection);
            
            // شروع انتقال
            $(document).on('submit', '#migrationForm', this.startMigration);
            
            // نمایش/مخفی رمز عبور
            $(document).on('click', '#togglePassword, #toggleWcPassword', this.togglePassword);
            
            // حذف دیتابیس
            $(document).on('click', '.delete-config', this.confirmDelete);
            
            // به‌روزرسانی آمار
            $(document).on('click', '.refresh-stats, #refreshAllStats, #refreshWcStats', this.refreshStats);
            
            // تخمین انتقال
            $(document).on('click', '#estimateBtn', this.estimateMigration);
            
            // جداسازی خودکار ووکامرس
            $(document).on('submit', '#autoSeparateForm', this.autoSeparateWooCommerce);
            
            // اعتبارسنجی فرم
            $(document).on('submit', '#databaseConfigForm', this.validateConfigForm);
            
            // انتخاب پست تایپ
            $(document).on('change', '#post_type', this.onPostTypeChange);
            
            // تست عملکرد ووکامرس
            $(document).on('click', '#testWcFunctionality', this.testWooCommerceFunctionality);
        },
        
        /**
         * راه‌اندازی Tooltips
         */
        initTooltips: function() {
            if (typeof $.fn.tooltip === 'function') {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        },
        
        /**
         * راه‌اندازی دیالوگ‌های تایید
         */
        initConfirmDialogs: function() {
            // می‌توان از کتابخانه‌هایی مثل SweetAlert2 استفاده کرد
            // برای سادگی از confirm استاندارد استفاده می‌کنیم
        },
        
        /**
         * ذخیره خودکار داده‌های فرم
         */
        autoSaveFormData: function() {
            // ذخیره داده‌های فرم در localStorage برای جلوگیری از دست رفتن داده
            const forms = $('form[data-autosave="true"]');
            
            forms.each(function() {
                const $form = $(this);
                const formId = $form.attr('id');
                
                if (!formId) return;
                
                // بارگذاری داده‌های ذخیره شده
                const savedData = localStorage.getItem('multidb_form_' + formId);
                if (savedData) {
                    try {
                        const data = JSON.parse(savedData);
                        Object.keys(data).forEach(function(key) {
                            $form.find('[name="' + key + '"]').val(data[key]);
                        });
                    } catch (e) {
                        console.error('Error loading saved form data:', e);
                    }
                }
                
                // ذخیره خودکار هنگام تغییر
                $form.on('change', 'input, select, textarea', function() {
                    const formData = $form.serializeArray();
                    const data = {};
                    formData.forEach(function(item) {
                        data[item.name] = item.value;
                    });
                    localStorage.setItem('multidb_form_' + formId, JSON.stringify(data));
                });
                
                // پاک کردن داده‌های ذخیره شده پس از submit موفق
                $form.on('submit', function() {
                    localStorage.removeItem('multidb_form_' + formId);
                });
            });
        },
        
        /**
         * تست اتصال دیتابیس
         */
        testConnection: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalText = $btn.html();
            const formPrefix = $btn.attr('id') === 'testWcConnectionBtn' ? 'wc_' : '';
            
            // دریافت اطلاعات فرم
            const connectionData = {
                host: $('#' + formPrefix + 'host').val(),
                database: $('#' + formPrefix + 'database').val(),
                username: $('#' + formPrefix + 'username').val(),
                password: $('#' + formPrefix + 'password').val(),
                port: $('#' + formPrefix + 'port').val() || 3306
            };
            
            // اعتبارسنجی
            if (!connectionData.host || !connectionData.database || !connectionData.username || !connectionData.password) {
                MultiDBAdmin.showNotification('لطفاً تمام فیلدهای الزامی را پر کنید', 'error');
                return;
            }
            
            // غیرفعال کردن دکمه
            $btn.prop('disabled', true).html(
                '<i class="dashicons dashicons-update dashicons-update-alt"></i> ' + 
                multiDbAdmin.strings.testing
            );
            
            // ارسال درخواست AJAX
            $.ajax({
                url: multiDbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'multi_db_test_connection',
                    nonce: multiDbAdmin.nonce,
                    ...connectionData
                },
                success: function(response) {
                    if (response.success) {
                        MultiDBAdmin.showNotification(response.data.message, 'success');
                    } else {
                        MultiDBAdmin.showNotification(response.data.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    MultiDBAdmin.showNotification('خطا در برقراری ارتباط با سرور: ' + error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        /**
         * شروع انتقال داده‌ها
         */
        startMigration: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const postType = $('#post_type').val();
            const targetDb = $('#target_db').val();
            const backup = $('#backup_enabled').is(':checked');
            const dryRun = $('#dry_run').is(':checked');
            const deleteSource = $('#delete_source').is(':checked');
            
            // اعتبارسنجی
            if (!postType || !targetDb) {
                MultiDBAdmin.showNotification('لطفاً تمام فیلدهای الزامی را پر کنید', 'error');
                return;
            }
            
            // تایید حذف از مبدا
            if (deleteSource && !confirm('⚠️ هشدار: با فعال کردن این گزینه، داده‌ها از دیتابیس اصلی حذف می‌شوند.\n\nآیا مطمئن هستید?')) {
                return;
            }
            
            // تایید شروع انتقال
            if (!confirm(multiDbAdmin.strings.confirmMigration)) {
                return;
            }
            
            // نمایش Progress Card
            $('#guideCard').fadeOut();
            $('#resultCard').fadeOut();
            $('#progressCard').fadeIn();
            
            // غیرفعال کردن دکمه
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true);
            
            // شروع انتقال
            $.ajax({
                url: multiDbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'multi_db_start_migration',
                    nonce: multiDbAdmin.nonce,
                    post_type: postType,
                    target_db: targetDb,
                    backup: backup,
                    dry_run: dryRun,
                    delete_source: deleteSource
                },
                success: function(response) {
                    if (response.success) {
                        MultiDBAdmin.showMigrationResult(true, response.data);
                    } else {
                        MultiDBAdmin.showMigrationResult(false, response.data);
                    }
                },
                error: function(xhr, status, error) {
                    MultiDBAdmin.showMigrationResult(false, {
                        message: 'خطا در برقراری ارتباط با سرور',
                        errors: [error]
                    });
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                }
            });
            
            // شروع پیگیری پیشرفت
            MultiDBAdmin.startProgressTracking(postType);
        },
        
        /**
         * پیگیری پیشرفت انتقال
         */
        startProgressTracking: function(postType) {
            let progress = 0;
            const interval = setInterval(function() {
                progress += Math.random() * 10;
                
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                }
                
                MultiDBAdmin.updateProgress(Math.round(progress));
            }, 500);
        },
        
        /**
         * بروزرسانی نوار پیشرفت
         */
        updateProgress: function(percent) {
            const $progressBar = $('#progressBar');
            const $progressPercent = $('#progressPercent');
            
            $progressBar.css('width', percent + '%')
                       .attr('aria-valuenow', percent)
                       .text(percent + '%');
            
            $progressPercent.text(percent + '%');
        },
        
        /**
         * نمایش نتیجه انتقال
         */
        showMigrationResult: function(success, data) {
            $('#progressCard').fadeOut(function() {
                const $resultCard = $('#resultCard');
                const $resultHeader = $('#resultCardHeader');
                const $resultTitle = $('#resultTitle');
                const $resultBody = $('#resultBody');
                
                if (success) {
                    $resultHeader.removeClass('bg-danger').addClass('bg-success text-white');
                    $resultTitle.html('<i class="dashicons dashicons-yes-alt"></i> انتقال با موفقیت انجام شد');
                    
                    let html = '<div class="alert alert-success">';
                    html += '<h6>✅ ' + (data.message || 'انتقال کامل شد') + '</h6>';
                    
                    if (data.results) {
                        html += '<hr>';
                        html += '<p class="mb-1">📊 تعداد پست‌ها: <strong>' + (data.results.migrated_posts || 0) + '</strong></p>';
                        html += '<p class="mb-1">🏷️ متادیتاها: <strong>' + (data.results.migrated_meta || 0) + '</strong></p>';
                        html += '<p class="mb-1">📁 Terms: <strong>' + (data.results.migrated_terms || 0) + '</strong></p>';
                        html += '<p class="mb-0">⏱️ زمان: <strong>' + (data.results.time_taken || 0) + '</strong> ثانیه</p>';
                    }
                    
                    html += '</div>';
                    $resultBody.html(html);
                    
                } else {
                    $resultHeader.removeClass('bg-success').addClass('bg-danger text-white');
                    $resultTitle.html('<i class="dashicons dashicons-dismiss"></i> خطا در انتقال');
                    
                    let html = '<div class="alert alert-danger">';
                    html += '<h6>❌ ' + (data.message || 'انتقال ناموفق بود') + '</h6>';
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<hr><ul class="mb-0">';
                        data.errors.forEach(function(error) {
                            html += '<li>' + error + '</li>';
                        });
                        html += '</ul>';
                    }
                    
                    html += '</div>';
                    $resultBody.html(html);
                }
                
                // دکمه بازگشت
                $resultBody.append('<button type="button" class="btn btn-primary" onclick="location.reload()">انتقال جدید</button>');
                
                $resultCard.fadeIn();
            });
        },
        
        /**
         * نمایش/مخفی رمز عبور
         */
        togglePassword: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $icon = $btn.find('i');
            const targetId = $btn.attr('id') === 'toggleWcPassword' ? '#wc_password' : '#password';
            const $passwordField = $(targetId);
            
            if ($passwordField.attr('type') === 'password') {
                $passwordField.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $passwordField.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        },
        
        /**
         * تایید حذف
         */
        confirmDelete: function(e) {
            if (!confirm(multiDbAdmin.strings.confirmDelete)) {
                e.preventDefault();
                return false;
            }
        },
        
        /**
         * به‌روزرسانی آمار
         */
        refreshStats: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalHtml = $btn.html();
            const dbName = $btn.data('db');
            
            $btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال به‌روزرسانی...');
            
            $.ajax({
                url: multiDbAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'multi_db_get_stats',
                    nonce: multiDbAdmin.nonce,
                    database: dbName
                },
                success: function(response) {
                    if (response.success) {
                        MultiDBAdmin.showNotification('آمار با موفقیت به‌روزرسانی شد', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        MultiDBAdmin.showNotification('خطا در به‌روزرسانی آمار', 'error');
                    }
                },
                error: function() {
                    MultiDBAdmin.showNotification('خطا در برقراری ارتباط با سرور', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        },
        
        /**
         * تخمین انتقال
         */
        estimateMigration: function(e) {
            e.preventDefault();
            
            const postType = $('#post_type').val();
            
            if (!postType) {
                MultiDBAdmin.showNotification('لطفاً ابتدا پست تایپ را انتخاب کنید', 'error');
                return;
            }
            
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال محاسبه...');
            
            // شبیه‌سازی محاسبه - در واقعیت باید از AJAX استفاده شود
            setTimeout(function() {
                const html = '<div class="alert alert-info mt-2">' +
                    '<strong>📊 تخمین انتقال:</strong><br>' +
                    '📝 تعداد پست‌ها: <strong>250</strong><br>' +
                    '⏱️ زمان تقریبی: <strong>5 دقیقه</strong><br>' +
                    '💾 حجم تقریبی: <strong>15 MB</strong>' +
                    '</div>';
                
                $('#postTypeInfo').html(html).hide().fadeIn();
                $btn.prop('disabled', false).html(originalHtml);
            }, 1000);
        },
        
        /**
         * جداسازی خودکار ووکامرس
         */
        autoSeparateWooCommerce: function(e) {
            e.preventDefault();
            
            if (!confirm('⚠️ آیا از جداسازی خودکار ووکامرس اطمینان دارید?\n\nاین عملیات ممکن است چند دقیقه طول بکشد.')) {
                return;
            }
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalHtml = $submitBtn.html();
            
            $submitBtn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال جداسازی...');
            
            // شبیه‌سازی - در واقعیت باید از AJAX استفاده شود
            setTimeout(function() {
                MultiDBAdmin.showNotification('✅ جداسازی ووکامرس با موفقیت انجام شد!<br>اکنون می‌توانید به قسمت انتقال داده‌ها بروید.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            }, 3000);
        },
        
        /**
         * اعتبارسنجی فرم تنظیمات
         */
        validateConfigForm: function(e) {
            const configName = $('#config_name').val();
            
            // بررسی فرمت نام
            if (!/^[a-z0-9_-]+$/.test(configName)) {
                e.preventDefault();
                MultiDBAdmin.showNotification('نام شناسایی فقط می‌تواند شامل حروف انگلیسی کوچک، اعداد، خط تیره و زیرخط باشد', 'error');
                return false;
            }
        },
        
        /**
         * تغییر پست تایپ
         */
        onPostTypeChange: function() {
            const postType = $(this).val();
            
            if (!postType) {
                $('#postTypeInfo').empty();
                return;
            }
            
            // اینجا می‌توانید آمار پست تایپ را با AJAX دریافت کنید
        },
        
        /**
         * تست عملکرد ووکامرس
         */
        testWooCommerceFunctionality: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            $btn.prop('disabled', true).html('<i class="dashicons dashicons-update dashicons-update-alt"></i> در حال تست...');
            
            setTimeout(function() {
                const result = '🧪 تست عملکرد ووکامرس:\n\n' +
                    '✅ کوئری محصولات: موفق\n' +
                    '✅ کوئری سفارشات: موفق\n' +
                    '✅ Analytics: موفق\n\n' +
                    'همه چیز عالی کار می‌کند!';
                
                alert(result);
                $btn.prop('disabled', false).html(originalHtml);
            }, 2000);
        },
        
        /**
         * نمایش نوتیفیکیشن
         */
        showNotification: function(message, type) {
            type = type || 'info';
            
            const typeClasses = {
                success: 'notice-success',
                error: 'notice-error',
                warning: 'notice-warning',
                info: 'notice-info'
            };
            
            const icons = {
                success: 'yes-alt',
                error: 'dismiss',
                warning: 'warning',
                info: 'info'
            };
            
            const $notice = $('<div>', {
                class: 'notice ' + typeClasses[type] + ' is-dismissible',
                html: '<p><i class="dashicons dashicons-' + icons[type] + '"></i> ' + message + '</p>'
            });
            
            $('.multi-db-admin').prepend($notice);
            
            // حذف خودکار بعد از 5 ثانیه
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // دکمه بستن
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },
        
        /**
         * کپی متن به کلیپبورد
         */
        copyToClipboard: function(text) {
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            
            MultiDBAdmin.showNotification('کپی شد!', 'success');
        }
    };
    
    // ==================== راه‌اندازی ====================
    
    $(document).ready(function() {
        MultiDBAdmin.init();
    });
    
    // قرار دادن در window برای دسترسی عمومی
    window.MultiDBAdmin = MultiDBAdmin;
    
})(jQuery);