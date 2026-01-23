@extends('clients.layouts.master')

@section('title', 'Xin chào '. $account->name. ' đến với cửa hàng quần áo '. renderMeta($settings->site_name ?? $settings->subname ?? 'NOBI FASHION VIỆT NAM'))

@section('head')
    <meta name="robots" content="follow, noindex"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nobifashion_profile_card-header {
            background-repeat: no-repeat;
            background-position: bottom center;
            background-size: 100% auto;
            aspect-ratio: 14 / 2;
        }
        .nobifashion_profile_avatar-upload {
            position: relative;
            max-width: 200px;
            margin: 0 auto;
        }
        .nobifashion_profile_avatar-upload .nobifashion_profile_avatar-edit {
            position: absolute;
            right: 12px;
            z-index: 1;
            bottom: 10px;
        }
        .nobifashion_profile_avatar-upload .nobifashion_profile_avatar-preview {
            width: 192px;
            height: 192px;
            position: relative;
            border-radius: 100%;
            border: 6px solid #f8f9fa;
            box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
        }
        .nobifashion_profile_avatar-upload .nobifashion_profile_avatar-preview > div {
            width: 100%;
            height: 100%;
            border-radius: 100%;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }
        .nobifashion_profile_tab-content {
            display: none;
        }
        .nobifashion_profile_tab-content.active {
            display: block;
        }
        .nobifashion_profile_tab-link.active {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
        }
        .custom-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            z-index: 9999;
            cursor: pointer;
        }
        .custom-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            color: #fff;
            max-width: 320px;
            opacity: 0;
            transform: translateX(100%);
            transition: transform 0.4s ease, opacity 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        .custom-toast.show {
            opacity: 1;
            transform: translateX(0);
        }
        .custom-toast.success {
            background-color: #1c9a4a;
        }
        .custom-toast.error {
            background-color: #ef4444;
        }
        .custom-toast.warning {
            background-color: #f59e0b;
        }
        .custom-toast.info {
            background-color: #3b82f6;
        }
        .custom-toast-icon {
            font-size: 18px;
        }
        .nobifashion_activity_filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 14px;
            font-weight: 500;
            color: #4b5563;
            border-radius: 999px;
            border: 1px solid transparent;
            background-color: transparent;
            transition: all 0.2s ease;
        }
        .nobifashion_activity_filter-btn:hover {
            color: #2563eb;
        }
        .nobifashion_activity_filter-btn.active {
            border-color: #3b82f6;
            color: #1d4ed8;
            background-color: rgba(59, 130, 246, 0.08);
        }
    </style>
    <meta name="robots" content="follow, noindex"/>
@endsection

@section('foot')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabLinks = document.querySelectorAll('.nobifashion_profile_tab-link');
            const tabContents = document.querySelectorAll('.nobifashion_profile_tab-content');
            
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabLinks.forEach(l => l.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    const contentId = `nobifashion_profile_tab-${tabId}`;
                    const content = document.getElementById(contentId);
                    if (content) {
                        content.classList.add('active');
                    }
                });
            });

            // Password visibility toggle
            const togglePasswordButtons = document.querySelectorAll('.nobifashion_profile_toggle-password');
            togglePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });

            // Avatar upload
            const avatarUpload = document.getElementById('nobifashion_profile_avatar-upload');
            if (avatarUpload) {
                avatarUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.getElementById('nobifashion_profile_avatar-preview');
                            if (preview) {
                                preview.style.backgroundImage = `url(${e.target.result})`;
                            }
                        };
                        reader.readAsDataURL(this.files[0]);
                        
                        // Upload immediately
                        uploadAvatar('avatar', this.files[0]);
                    }
                });
            }

            // Sub Avatar upload
            const subAvatarUpload = document.getElementById('nobifashion_profile_sub_avatar-upload');
            if (subAvatarUpload) {
                subAvatarUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                            const header = document.querySelector('.nobifashion_profile_card-header');
                            if (header) {
                                header.style.backgroundImage = `url(${e.target.result})`;
                            }
                    };
                    reader.readAsDataURL(this.files[0]);
                        
                        // Upload immediately
                        uploadAvatar('sub_avatar', this.files[0]);
                    }
                });
            }

            // Upload avatar function
            function uploadAvatar(type, file) {
                const formData = new FormData();
                formData.append(type, file);

                fetch('{{ route("client.profile.update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(data.message || 'Cập nhật ảnh thành công!', 'success');
                        } else {
                            alert(data.message || 'Cập nhật ảnh thành công!');
                        }
                        
                        // Update preview URLs if provided
                        if (data.profile) {
                            if (type === 'avatar' && data.profile.avatar_url) {
                                const preview = document.getElementById('nobifashion_profile_avatar-preview');
                                if (preview) {
                                    preview.style.backgroundImage = `url(${data.profile.avatar_url})`;
                                }
                            }
                            if (type === 'sub_avatar' && data.profile.sub_avatar_url) {
                                const header = document.querySelector('.nobifashion_profile_card-header');
                                if (header) {
                                    header.style.backgroundImage = `url(${data.profile.sub_avatar_url})`;
                                }
                            }
                        }
                    } else {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(data.message || 'Có lỗi xảy ra khi cập nhật ảnh.', 'error');
                        } else {
                            alert(data.message || 'Có lỗi xảy ra khi cập nhật ảnh.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof showCustomToast === 'function') {
                        showCustomToast('Có lỗi xảy ra khi cập nhật ảnh.', 'error');
                    } else {
                        alert('Có lỗi xảy ra khi cập nhật ảnh.');
                    }
                });
            }

            // Form submissions
            const profileForm = document.getElementById('nobifashion_profile_form');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData();
                    
                    // Collect all form data
                    const fullName = document.getElementById('nobifashion_profile_first-name')?.value || '';
                    const nickname = document.getElementById('nobifashion_profile_last-name')?.value || '';
                    const bio = document.getElementById('nobifashion_profile_bio')?.value || '';
                    const gender = document.getElementById('nobifashion_profile_gender')?.value || '';
                    const birthday = document.getElementById('nobifashion_profile_dob')?.value || '';
                    const phone = document.getElementById('nobifashion_profile_phone')?.value || '';
                    
                    // Add to FormData
                    if (fullName) formData.append('full_name', fullName);
                    if (nickname) formData.append('nickname', nickname);
                    if (bio) formData.append('bio', bio);
                    if (gender) formData.append('gender', gender);
                    if (birthday) formData.append('birthday', birthday);
                    if (phone) formData.append('phone', phone);

                    fetch('{{ route("client.profile.update") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Cập nhật hồ sơ thành công!', 'success');
                            } else {
                                alert(data.message || 'Cập nhật hồ sơ thành công!');
                            }
                            
                            // Update display name if changed
                            if (data.profile && data.profile.full_name) {
                                const displayName = document.getElementById('nobifashion_profile_display-name');
                                if (displayName) {
                                    displayName.textContent = data.profile.full_name || data.profile.nickname || '{{ $account->name }}';
                                }
                            }
                        } else {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Có lỗi xảy ra khi cập nhật hồ sơ.', 'error');
                            } else {
                                alert(data.message || 'Có lỗi xảy ra khi cập nhật hồ sơ.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Có lỗi xảy ra khi cập nhật hồ sơ.', 'error');
                        } else {
                            alert('Có lỗi xảy ra khi cập nhật hồ sơ.');
                        }
                    });
                });
            }

            const passwordForm = document.getElementById('nobifashion_profile_password-form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const currentPass = document.getElementById('nobifashion_profile_current-password')?.value || '';
                    const newPass = document.getElementById('nobifashion_profile_new-password')?.value || '';
                    const confirmPass = document.getElementById('nobifashion_profile_confirm-password')?.value || '';
                    
                    // 客户端验证
                    if (!currentPass) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Vui lòng nhập mật khẩu hiện tại.', 'error');
                        } else {
                            alert('Vui lòng nhập mật khẩu hiện tại.');
                        }
                        return;
                    }
                    
                    if (!newPass) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Vui lòng nhập mật khẩu mới.', 'error');
                        } else {
                            alert('Vui lòng nhập mật khẩu mới.');
                        }
                        return;
                    }
                    
                    if (newPass.length < 8) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Mật khẩu mới phải có ít nhất 8 ký tự.', 'error');
                        } else {
                            alert('Mật khẩu mới phải có ít nhất 8 ký tự.');
                        }
                        return;
                    }
                    
                    if (newPass !== confirmPass) {
                        if (typeof showCustomToast === 'function') {
                            showCustomToast('Xác nhận mật khẩu không khớp.', 'error');
                        } else {
                            alert('Xác nhận mật khẩu không khớp.');
                        }
                        return;
                    }
                    
                    // 提交到服务器
                    const formData = new FormData();
                    formData.append('current_password', currentPass);
                    formData.append('password', newPass);
                    formData.append('password_confirmation', confirmPass);
                    
                    // 禁用提交按钮
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.textContent || 'Lưu';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Đang xử lý...';
                    }
                    
                    fetch('{{ route("client.profile.change-password") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        return response.json().then(data => {
                            if (!response.ok) {
                                // 处理验证错误
                                if (data.errors) {
                                    // 清除之前的错误
                                    document.querySelectorAll('[id$="-error"]').forEach(el => el.textContent = '');
                                    
                                    // 显示新的错误
                                    Object.keys(data.errors).forEach(field => {
                                        const errorElement = document.getElementById(`nobifashion_profile_${field.replace('_', '-')}-error`);
                                        if (errorElement && data.errors[field]) {
                                            errorElement.textContent = Array.isArray(data.errors[field]) ? data.errors[field][0] : data.errors[field];
                                        }
                                    });
                                }
                                throw new Error(data.message || 'Có lỗi xảy ra khi đổi mật khẩu.');
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Đổi mật khẩu thành công!', 'success');
                            } else {
                                alert(data.message || 'Đổi mật khẩu thành công!');
                            }
                            
                            // 清除所有错误消息
                            document.querySelectorAll('[id$="-error"]').forEach(el => el.textContent = '');
                            
                            // 重置表单
                            passwordForm.reset();
                        } else {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Có lỗi xảy ra khi đổi mật khẩu.', 'error');
                            } else {
                                alert(data.message || 'Có lỗi xảy ra khi đổi mật khẩu.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errorMessage = 'Có lỗi xảy ra khi đổi mật khẩu.';
                        
                        if (error.message) {
                            errorMessage = error.message;
                        }
                        
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(errorMessage, 'error');
                        } else {
                            alert(errorMessage);
                        }
                    })
                    .finally(() => {
                        // 恢复提交按钮
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    });
                });
            }

            const preferencesForm = document.getElementById('nobifashion_profile_preferences-form');
            if (preferencesForm) {
                preferencesForm.addEventListener('submit', function(e) {
                e.preventDefault();
                    
                    const formData = new FormData();
                    
                    // 收集所有复选框和选择框的值
                    const checkboxes = preferencesForm.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(checkbox => {
                        formData.append(checkbox.name, checkbox.checked ? '1' : '0');
                    });
                    
                    const selects = preferencesForm.querySelectorAll('select');
                    selects.forEach(select => {
                        if (select.value) {
                            formData.append(select.name, select.value);
                        }
                    });
                    
                    // 禁用提交按钮
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.textContent || 'Lưu cài đặt';
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lưu...';
                    }
                    
                    fetch('{{ route("client.profile.preferences") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'Có lỗi xảy ra khi cập nhật cài đặt.');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Cập nhật cài đặt thành công!', 'success');
                            } else {
                                alert(data.message || 'Cập nhật cài đặt thành công!');
                            }
                        } else {
                            if (typeof showCustomToast === 'function') {
                                showCustomToast(data.message || 'Có lỗi xảy ra khi cập nhật cài đặt.', 'error');
                            } else {
                                alert(data.message || 'Có lỗi xảy ra khi cập nhật cài đặt.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(error.message || 'Có lỗi xảy ra khi cập nhật cài đặt.', 'error');
                        } else {
                            alert(error.message || 'Có lỗi xảy ra khi cập nhật cài đặt.');
                        }
                    })
                    .finally(() => {
                        // 恢复提交按钮
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Lưu cài đặt';
                        }
                    });
                });
                
                // 重置为默认值
                const resetBtn = document.getElementById('nobifashion_profile_reset-prefs-btn');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function() {
                        if (confirm('Bạn có chắc chắn muốn đặt lại tất cả cài đặt về mặc định?')) {
                            // 重置所有复选框为默认值
                            const checkboxes = preferencesForm.querySelectorAll('input[type="checkbox"]');
                            checkboxes.forEach(checkbox => {
                                if (checkbox.name === 'notify_order_created' || 
                                    checkbox.name === 'notify_order_updated' || 
                                    checkbox.name === 'notify_order_shipped' || 
                                    checkbox.name === 'notify_order_completed' ||
                                    checkbox.name === 'notify_promotions' ||
                                    checkbox.name === 'notify_flash_sale' ||
                                    checkbox.name === 'notify_security' ||
                                    checkbox.name === 'notify_via_email' ||
                                    checkbox.name === 'notify_via_in_app' ||
                                    checkbox.name === 'show_order_history' ||
                                    checkbox.name === 'show_favorites') {
                                    checkbox.checked = true;
                                } else {
                                    checkbox.checked = false;
                                }
                            });
                            
                            // 重置选择框为默认值
                            const languageSelect = document.getElementById('nobifashion_preferred_language');
                            if (languageSelect) languageSelect.value = 'vi';
                            
                            const timezoneSelect = document.getElementById('nobifashion_preferred_timezone');
                            if (timezoneSelect) timezoneSelect.value = 'Asia/Ho_Chi_Minh';
                            
                            const currencySelect = document.getElementById('nobifashion_preferred_currency');
                            if (currencySelect) currencySelect.value = 'VND';
                            
                            if (typeof showCustomToast === 'function') {
                                showCustomToast('Đã đặt lại về mặc định. Vui lòng nhấn "Lưu cài đặt" để áp dụng.', 'info');
                            } else {
                                alert('Đã đặt lại về mặc định. Vui lòng nhấn "Lưu cài đặt" để áp dụng.');
                            }
                        }
                    });
                }
            }

            // Recent activities
            const activityState = {
                filter: 'all',
                page: 1,
                perPage: 5,
                hasMore: true,
                isLoading: false,
            };

            const activityListEl = document.getElementById('nobifashion_profile_activity-list');
            const activityEmptyEl = document.getElementById('nobifashion_profile_activity-empty');
            const activityLoadingEl = document.getElementById('nobifashion_profile_activity-loading');
            const activityLoadMoreBtn = document.getElementById('nobifashion_profile_activity-loadmore');
            const activityFilterButtons = document.querySelectorAll('.nobifashion_activity_filter-btn');

            function renderActivityBadges(badges = []) {
                if (!badges.length) {
                    return '';
                }

                return badges.map(badge => `
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.color}">
                        ${badge.text}
                    </span>
                `).join('');
            }

            function renderActivityItem(item) {
                const badgesHtml = renderActivityBadges(item.badges || []);
                const amountHtml = item.meta?.amount
                    ? `<span class="text-sm font-semibold text-blue-600">${item.meta.amount}</span>`
                    : '';
                const actionHtml = item.action_url
                    ? `<a href="${item.action_url}" class="inline-flex items-center text-sm text-blue-500 hover:text-blue-700 mt-2 font-medium">
                            ${item.action_text || 'Xem chi tiết'}
                            <i class="fas fa-chevron-right ml-1 text-xs"></i>
                       </a>`
                    : '';

                return `
                    <div class="flex items-start">
                        <div class="${item.icon_background} ${item.icon_color} rounded-full p-3 mr-4">
                            <i class="${item.icon}"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div>
                                    <p class="text-gray-800 font-medium">${item.title}</p>
                                    <p class="text-sm text-gray-500 mt-1">${item.description || ''}</p>
                                </div>
                                ${amountHtml}
                            </div>
                            <div class="flex items-center gap-3 flex-wrap mt-3">
                                ${badgesHtml}
                                <div class="text-sm text-gray-400 flex items-center gap-1">
                                    <i class="far fa-clock"></i>
                                    <span title="${item.time_exact || ''}">${item.time_human || ''}</span>
                                </div>
                            </div>
                            ${actionHtml}
                        </div>
                    </div>
                `;
            }

            function toggleActivityEmptyState() {
                if (!activityListEl || !activityEmptyEl) {
                    return;
                }
                
                const hasItems = activityListEl.children.length > 0;
                activityEmptyEl.classList.toggle('hidden', hasItems);
            }

            function fetchActivities(reset = false) {
                if (!activityListEl || activityState.isLoading) {
                    return;
                }
                
                if (reset) {
                    activityState.page = 1;
                    activityState.hasMore = true;
                    activityListEl.innerHTML = '';
                }

                activityState.isLoading = true;
                activityLoadingEl?.classList.remove('hidden');

                const params = new URLSearchParams({
                    filter: activityState.filter,
                    page: activityState.page.toString(),
                    per_page: activityState.perPage.toString(),
                });

                fetch(`{{ route('client.profile.activities') }}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data?.success) {
                            throw new Error(data?.message || 'Không thể tải hoạt động.');
                        }

                        const activities = data.data || [];
                        if (reset) {
                            activityListEl.innerHTML = '';
                        }

                        activities.forEach(item => {
                            activityListEl.insertAdjacentHTML('beforeend', renderActivityItem(item));
                        });

                        toggleActivityEmptyState();

                        activityState.hasMore = data?.meta?.has_more ?? false;
                        if (activityLoadMoreBtn) {
                            activityLoadMoreBtn.classList.toggle('hidden', !activityState.hasMore);
                        }

                        if (activityState.hasMore) {
                            activityState.page += 1;
                        }
                    })
                    .catch(error => {
                        console.error(error);
                        if (typeof showCustomToast === 'function') {
                            showCustomToast(error.message || 'Không thể tải hoạt động.', 'error');
                        }
                    })
                    .finally(() => {
                        activityState.isLoading = false;
                        activityLoadingEl?.classList.add('hidden');
                        toggleActivityEmptyState();
                    });
            }

            if (activityFilterButtons.length) {
                activityFilterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        activityFilterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        activityState.filter = this.getAttribute('data-activity-filter') || 'all';
                        fetchActivities(true);
                    });
                });
            }

            if (activityLoadMoreBtn) {
                activityLoadMoreBtn.addEventListener('click', function() {
                    if (activityState.hasMore) {
                        fetchActivities(false);
                    }
                });
            }

            if (activityListEl) {
                fetchActivities(true);
            }

            // Logout button
            const logoutBtn = document.querySelector('.nobifashion_profile_logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("client.auth.logout") }}';
                        
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                        form.appendChild(csrfToken);
                        
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }
            
            // Display session messages
            @if (session('success'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('success')), 'success');
                }
            @endif
            
            @if (session('error'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('error')), 'error');
                }
            @endif
            
            @if (session('warning'))
                if (typeof showCustomToast === 'function') {
                    showCustomToast(@json(session('warning')), 'warning');
                }
            @endif

            // Enable 2FA button
            document.querySelector('.nobifashion_profile_enable-2fa-btn').addEventListener('click', function() {
                alert('Two-factor authentication setup will begin. Check your email for next steps.');
            });
        });
    </script>
@endsection

@section('content')
    @php
        $avatar = $account->profile->avatar ?? '';
        $subAvatar = $account->profile->sub_avatar ?? '';
        $avatarUrl = \Illuminate\Support\Str::startsWith($avatar, ['http://', 'https://']) ? $avatar : asset('admins/img/accounts/' . ltrim($avatar, '/'));
        $subAvatarUrl = \Illuminate\Support\Str::startsWith($subAvatar, ['http://', 'https://']) ? $subAvatar : asset('admins/img/accounts/' . ltrim($subAvatar, '/'));
    @endphp
    <div class="nobifashion_profile_container mx-auto w-[95%] px-4 py-8">
        <div class="nobifashion_profile_header flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800" style="color: #FF3366; font-family: 'Dancing Script', cursive;">Xin chào, {{ $account->name }}</h1>
            <button class="nobifashion_profile_logout-btn bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
            </button>
        </div>

        <div class="nobifashion_profile_card bg-white rounded-xl shadow-md overflow-hidden">
            <div class="nobifashion_profile_card-header border-b p-6 relative" style="background-image: url({{ $subAvatarUrl }}); background-size: cover; background-position: center;">
                <!-- Sub Avatar Upload Button -->
                <div class="absolute top-4 right-4">
                    <input type="file" id="nobifashion_profile_sub_avatar-upload" class="hidden" accept="image/*">
                    <label for="nobifashion_profile_sub_avatar-upload" class="bg-white bg-opacity-80 hover:bg-opacity-100 text-gray-700 rounded-full p-2 cursor-pointer transition shadow-md">
                        <i class="fas fa-image text-sm"></i>
                        <span class="text-xs ml-1">Ảnh nền</span>
                    </label>
                </div>
                <div class="flex flex-col md:flex-row items-center">
                    <div class="nobifashion_profile_avatar-upload mb-4 md:mb-0 md:mr-6">
                        <div class="nobifashion_profile_avatar-preview">
                           
                            <div id="nobifashion_profile_avatar-preview" style="background-image: url('{{ $avatarUrl }}');"></div>
                        </div>
                        <div class="nobifashion_profile_avatar-edit">
                            <input type="file" id="nobifashion_profile_avatar-upload" class="hidden" accept="image/*">
                            <label for="nobifashion_profile_avatar-upload" class="bg-blue-500 text-white rounded-full p-2 cursor-pointer hover:bg-blue-600 transition">
                                <i class="fas fa-pencil-alt text-sm"></i>
                            </label>
                        </div>
                    </div>
                    <div class="text-center md:text-left text-white">
                        <h2 class="text-2xl font-semibold" id="nobifashion_profile_display-name">{{ $account->name }}</h2>
                        <p class="text-gray-600 mb-2 text-white" id="nobifashion_profile_email">{{ $account->email }}</p>
                        <p class="text-gray-500 text-sm text-white">Tham gia từ: <span id="nobifashion_profile_join-date">Tháng {{ \Carbon\Carbon::parse($account->created_at)->month }} năm {{ \Carbon\Carbon::parse($account->created_at)->year }}</span></p>
                    </div>
                </div>
            </div>

            <div class="nobifashion_profile_card-body">
                <div class="nobifashion_profile_tabs flex border-b">
                    <button class="nobifashion_profile_tab-link active px-6 py-4 font-medium text-gray-600 hover:text-blue-500 focus:outline-none" data-tab="profile">
                        <i class="fas fa-user mr-2"></i>Thông tin
                    </button>
                    <button class="nobifashion_profile_tab-link px-6 py-4 font-medium text-gray-600 hover:text-blue-500 focus:outline-none" data-tab="security">
                        <i class="fas fa-lock mr-2"></i>Đổi mật khẩu
                    </button>
                    <button class="nobifashion_profile_tab-link px-6 py-4 font-medium text-gray-600 hover:text-blue-500 focus:outline-none" data-tab="preferences">
                        <i class="fas fa-cog mr-2"></i>Thông báo
                    </button>
                </div>

                <!-- Profile Tab -->
                <div id="nobifashion_profile_tab-profile" class="nobifashion_profile_tab-content active p-6">
                    <form id="nobifashion_profile_form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 mb-2" for="nobifashion_profile_first-name">Tên của bạn</label>
                                <input type="text" id="nobifashion_profile_first-name" name="full_name" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $account->profile->full_name ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="nobifashion_profile_last-name">Tên phụ</label>
                                <input type="text" id="nobifashion_profile_last-name" name="nickname" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $account->profile->nickname ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="nobifashion_profile_email-input">Email</label>
                                <input type="email" id="nobifashion_profile_email-input" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100" value="{{ $account->email }}" disabled>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="nobifashion_profile_phone">Số điện thoại</label>
                                <input type="tel" id="nobifashion_profile_phone" name="phone" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $account->profile->phone ?? '' }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="nobifashion_profile_dob">Năm sinh</label>
                                <input type="date" id="nobifashion_profile_dob" name="birthday" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $account->profile->birthday ? substr($account->profile->birthday, 0, 10) : '' }}">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2" for="nobifashion_profile_gender">Giới tính</label>
                                <select id="nobifashion_profile_gender" name="gender" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="male" {{ ($account->profile->gender ?? '') === 'male' ? 'selected' : '' }}>Nam</option>
                                    <option value="female" {{ ($account->profile->gender ?? '') === 'female' ? 'selected' : '' }}>Nữ</option>
                                    <option value="other" {{ ($account->profile->gender ?? '') === 'other' ? 'selected' : '' }}>Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6">
                            @php
                                $addr = $account->defaultAddress;
                                $fullAddress = implode(' - ', array_filter([
                                    $addr->detail ?? '',
                                    $addr->ward ?? '',
                                    $addr->district ?? '',
                                    $addr->province ?? '',
                                    $addr->country ?? '',
                                ]));
                            @endphp

                            <label class="block text-gray-700 mb-2" for=".nobifashion_profile_address">Địa chỉ giao hàng mặc định</label>
                            <textarea disabled id=".nobifashion_profile_address" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3">{{ trim($fullAddress) }}</textarea>

                        </div>
                        <div class="mt-6">
                            @php
                                $addr = $account->defaultAddress;
                                $fullAddress = implode(' - ', array_filter([
                                    $addr->detail ?? '',
                                    $addr->ward ?? '',
                                    $addr->district ?? '',
                                    $addr->province ?? '',
                                    $addr->country ?? '',
                                ]));
                            @endphp

                            <label class="block text-gray-700 mb-2" for="nobifashion_profile_bio">Giới thiệu bản thân</label>
                            <textarea id="nobifashion_profile_bio" name="bio" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="Nhập giới thiệu về bản thân...">{{ $account->profile->bio ?? '' }}</textarea>
                            
                        </div>
                        <div class="mt-6 flex justify-end space-x-4">
                            {{-- <button type="button" class="nobifashion_profile_cancel-btn bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
                                Cancel
                            </button> --}}
                            <button type="submit" class="nobifashion_profile_save-btn bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
                                Lưu
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Tab -->
                <div id="nobifashion_profile_tab-security" class="nobifashion_profile_tab-content p-6">
                    <form id="nobifashion_profile_password-form">
                        <div class="mb-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4">Thay đổi mật khẩu</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 mb-2" for="nobifashion_profile_current-password">Mật khẩu hiện tại <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" id="nobifashion_profile_current-password" name="current_password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <button type="button" class="nobifashion_profile_toggle-password absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <p class="text-red-500 text-xs mt-1" id="nobifashion_profile_current-password-error"></p>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2" for="nobifashion_profile_new-password">Mật khẩu mới <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" id="nobifashion_profile_new-password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <button type="button" class="nobifashion_profile_toggle-password absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Mật khẩu phải dài ít nhất 8 ký tự!
                                    </div>
                                    <p class="text-red-500 text-xs mt-1" id="nobifashion_profile_new-password-error"></p>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2" for="nobifashion_profile_confirm-password">Nhập lại mật khẩu mới <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" id="nobifashion_profile_confirm-password" name="password_confirmation" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        <button type="button" class="nobifashion_profile_toggle-password absolute right-3 top-2 text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <p class="text-red-500 text-xs mt-1" id="nobifashion_profile_confirm-password-error"></p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="nobifashion_profile_update-password-btn bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
                                Lưu
                            </button>
                        </div>
                    </form>

                    {{-- <div class="mt-8 pt-6 border-t">
                        <h3 class="text-xl font-semibold text-gray-800 mb-4">Two-Factor Authentication</h3>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-600">Add an extra layer of security to your account</p>
                                <p class="text-sm text-gray-500 mt-1">Status: <span class="text-red-500">Disabled</span></p>
                            </div>
                            <button class="nobifashion_profile_enable-2fa-btn bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                                Enable 2FA
                            </button>
                        </div>
                    </div> --}}
                </div>

                <!-- Preferences Tab -->
                <div id="nobifashion_profile_tab-preferences" class="nobifashion_profile_tab-content p-6">
                    <form id="nobifashion_profile_preferences-form">
                        @php
                            $profile = $account->profile;
                            $notifyOrderCreated = $profile->notify_order_created ?? true;
                            $notifyOrderUpdated = $profile->notify_order_updated ?? true;
                            $notifyOrderShipped = $profile->notify_order_shipped ?? true;
                            $notifyOrderCompleted = $profile->notify_order_completed ?? true;
                            $notifyPromotions = $profile->notify_promotions ?? true;
                            $notifyFlashSale = $profile->notify_flash_sale ?? true;
                            $notifyNewProducts = $profile->notify_new_products ?? false;
                            $notifyStockAlert = $profile->notify_stock_alert ?? false;
                            $notifySecurity = $profile->notify_security ?? true;
                            $notifyViaEmail = $profile->notify_via_email ?? true;
                            $notifyViaSms = $profile->notify_via_sms ?? false;
                            $notifyViaInApp = $profile->notify_via_in_app ?? true;
                            $showOrderHistory = $profile->show_order_history ?? true;
                            $showFavorites = $profile->show_favorites ?? true;
                            $preferredLanguage = $profile->preferred_language ?? 'vi';
                            $preferredTimezone = $profile->preferred_timezone ?? 'Asia/Ho_Chi_Minh';
                            $preferredCurrency = $profile->preferred_currency ?? 'VND';
                        @endphp

                        <!-- 通知设置 -->
                        <div class="mb-8">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-bell mr-2 text-blue-500"></i>
                                Cài đặt thông báo
                            </h3>
                            
                            <!-- 订单通知 -->
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-700 mb-3">Thông báo đơn hàng</h4>
                                <div class="space-y-3 pl-4 border-l-2 border-blue-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_order_created" name="notify_order_created" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyOrderCreated ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_order_created" class="ml-2 text-gray-700">Xác nhận đơn hàng mới</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_order_updated" name="notify_order_updated" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyOrderUpdated ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_order_updated" class="ml-2 text-gray-700">Cập nhật trạng thái đơn hàng</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_order_shipped" name="notify_order_shipped" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyOrderShipped ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_order_shipped" class="ml-2 text-gray-700">Thông báo giao hàng</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_order_completed" name="notify_order_completed" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyOrderCompleted ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_order_completed" class="ml-2 text-gray-700">Đơn hàng hoàn thành</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 促销通知 -->
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-700 mb-3">Thông báo khuyến mãi</h4>
                                <div class="space-y-3 pl-4 border-l-2 border-green-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_promotions" name="notify_promotions" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyPromotions ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_promotions" class="ml-2 text-gray-700">Khuyến mãi & Ưu đãi</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_flash_sale" name="notify_flash_sale" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyFlashSale ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_flash_sale" class="ml-2 text-gray-700">Flash Sale & Deal Hot</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_new_products" name="notify_new_products" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyNewProducts ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_new_products" class="ml-2 text-gray-700">Sản phẩm mới</label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_stock_alert" name="notify_stock_alert" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyStockAlert ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_stock_alert" class="ml-2 text-gray-700">Cảnh báo hết hàng</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 安全通知 -->
                            <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-700 mb-3">Thông báo bảo mật</h4>
                                <div class="space-y-3 pl-4 border-l-2 border-red-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_security" name="notify_security" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifySecurity ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_security" class="ml-2 text-gray-700">Đăng nhập & Thay đổi mật khẩu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 通知方式 -->
                        <div class="mb-6">
                                <h4 class="text-lg font-medium text-gray-700 mb-3">Phương thức nhận thông báo</h4>
                                <div class="space-y-3 pl-4 border-l-2 border-purple-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_via_email" name="notify_via_email" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyViaEmail ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_via_email" class="ml-2 text-gray-700">
                                                <i class="fas fa-envelope mr-1"></i> Email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_via_sms" name="notify_via_sms" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyViaSms ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_via_sms" class="ml-2 text-gray-700">
                                                <i class="fas fa-sms mr-1"></i> SMS
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="nobifashion_notify_via_in_app" name="notify_via_in_app" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $notifyViaInApp ? 'checked' : '' }}>
                                            <label for="nobifashion_notify_via_in_app" class="ml-2 text-gray-700">
                                                <i class="fas fa-bell mr-1"></i> Thông báo trong ứng dụng
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 隐私设置 -->
                        <div class="mb-8 border-t pt-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                                Cài đặt quyền riêng tư
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <label for="nobifashion_show_order_history" class="text-gray-700 font-medium">Hiển thị lịch sử đơn hàng</label>
                                        <p class="text-sm text-gray-500 mt-1">Cho phép người khác xem lịch sử đơn hàng của bạn</p>
                                    </div>
                                    <input type="checkbox" id="nobifashion_show_order_history" name="show_order_history" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $showOrderHistory ? 'checked' : '' }}>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <label for="nobifashion_show_favorites" class="text-gray-700 font-medium">Hiển thị danh sách yêu thích</label>
                                        <p class="text-sm text-gray-500 mt-1">Cho phép người khác xem sản phẩm bạn đã yêu thích</p>
                                    </div>
                                    <input type="checkbox" id="nobifashion_show_favorites" name="show_favorites" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $showFavorites ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <!-- 偏好设置 -->
                        <div class="mb-8 border-t pt-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-cog mr-2 text-orange-500"></i>
                                Cài đặt tùy chọn
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 mb-2" for="nobifashion_preferred_language">
                                        <i class="fas fa-language mr-1"></i> Ngôn ngữ
                                    </label>
                                    <select id="nobifashion_preferred_language" name="preferred_language" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="vi" {{ $preferredLanguage === 'vi' ? 'selected' : '' }}>Tiếng Việt</option>
                                        <option value="en" {{ $preferredLanguage === 'en' ? 'selected' : '' }}>English</option>
                                        <option value="zh" {{ $preferredLanguage === 'zh' ? 'selected' : '' }}>中文</option>
                                        <option value="ja" {{ $preferredLanguage === 'ja' ? 'selected' : '' }}>日本語</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2" for="nobifashion_preferred_timezone">
                                        <i class="fas fa-clock mr-1"></i> Múi giờ
                                    </label>
                                    <select id="nobifashion_preferred_timezone" name="preferred_timezone" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="Asia/Ho_Chi_Minh" {{ $preferredTimezone === 'Asia/Ho_Chi_Minh' ? 'selected' : '' }}>(GMT+7) Hà Nội, TP. Hồ Chí Minh</option>
                                        <option value="Asia/Bangkok" {{ $preferredTimezone === 'Asia/Bangkok' ? 'selected' : '' }}>(GMT+7) Bangkok</option>
                                        <option value="Asia/Singapore" {{ $preferredTimezone === 'Asia/Singapore' ? 'selected' : '' }}>(GMT+8) Singapore</option>
                                        <option value="Asia/Hong_Kong" {{ $preferredTimezone === 'Asia/Hong_Kong' ? 'selected' : '' }}>(GMT+8) Hong Kong</option>
                                        <option value="Asia/Tokyo" {{ $preferredTimezone === 'Asia/Tokyo' ? 'selected' : '' }}>(GMT+9) Tokyo</option>
                                        <option value="Asia/Seoul" {{ $preferredTimezone === 'Asia/Seoul' ? 'selected' : '' }}>(GMT+9) Seoul</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2" for="nobifashion_preferred_currency">
                                        <i class="fas fa-dollar-sign mr-1"></i> Tiền tệ
                                    </label>
                                    <select id="nobifashion_preferred_currency" name="preferred_currency" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="VND" {{ $preferredCurrency === 'VND' ? 'selected' : '' }}>VND (₫)</option>
                                        <option value="USD" {{ $preferredCurrency === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                        <option value="EUR" {{ $preferredCurrency === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                        <option value="CNY" {{ $preferredCurrency === 'CNY' ? 'selected' : '' }}>CNY (¥)</option>
                                        <option value="JPY" {{ $preferredCurrency === 'JPY' ? 'selected' : '' }}>JPY (¥)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4 border-t pt-6">
                            <button type="button" id="nobifashion_profile_reset-prefs-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg transition">
                                <i class="fas fa-undo mr-2"></i>Cài lại mặc định
                            </button>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition">
                                <i class="fas fa-save mr-2"></i>Lưu cài đặt
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="nobifashion_profile_activity mt-8 bg-white rounded-xl shadow-md overflow-hidden">
            <div class="nobifashion_profile_activity-header border-b p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-history text-blue-500"></i>
                        Hoạt động gần đây
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Theo dõi tất cả thay đổi liên quan đến tài khoản và đơn hàng của bạn.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="nobifashion_activity_filter-btn active" data-activity-filter="all">
                        <i class="fas fa-layer-group"></i> Tất cả
                    </button>
                    <button class="nobifashion_activity_filter-btn" data-activity-filter="account">
                        <i class="fas fa-user-circle"></i> Hồ sơ
                    </button>
                    <button class="nobifashion_activity_filter-btn" data-activity-filter="security">
                        <i class="fas fa-shield-alt"></i> Bảo mật
                    </button>
                    <button class="nobifashion_activity_filter-btn" data-activity-filter="order">
                        <i class="fas fa-truck"></i> Đơn hàng
                    </button>
                </div>
            </div>
            <div class="nobifashion_profile_activity-body p-6">
                <div id="nobifashion_profile_activity-loading" class="py-6 text-center text-gray-500 hidden">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Đang tải hoạt động...
                        </div>
                <div id="nobifashion_profile_activity-list" class="space-y-4"></div>
                <div id="nobifashion_profile_activity-empty" class="text-center text-gray-500 py-6 hidden">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-2">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <p>Chưa có hoạt động nào.</p>
                </div>
                <button id="nobifashion_profile_activity-loadmore" class="mt-4 text-blue-500 hover:text-blue-700 font-medium hidden">
                    Xem thêm hoạt động <i class="fas fa-chevron-right ml-1"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="custom-toast-container" class="custom-toast-container"></div>
@endsection