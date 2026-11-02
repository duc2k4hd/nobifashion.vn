<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ReelSettingController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Màn hình Cài đặt hệ thống Reels
     */
    public function index(): View
    {
        // Danh sách setting mặc định
        $defaultSettings = [
            'reels.enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Bật trang Reels công khai'],
            'reels.comments_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Cho phép bình luận'],
            'reels.likes_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Cho phép thả tim'],
            'reels.share_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Cho phép chia sẻ'],
            'reels.save_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Cho phép lưu video'],
            'reels.batch_limit' => ['value' => '10', 'type' => 'integer', 'label' => 'Số video mỗi lần tải'],
            'reels.max_duration' => ['value' => '180', 'type' => 'integer', 'label' => 'Thời lượng video tối đa (giây)'],
            'reels.moderation_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Bật tự động kiểm duyệt bình luận'],
            'reels.block_links' => ['value' => '1', 'type' => 'boolean', 'label' => 'Tự động chặn đường link trong bình luận'],
            'reels.stickers_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Bật tính năng gửi sticker'],
            'reels.max_comment_length' => ['value' => '500', 'type' => 'integer', 'label' => 'Độ dài tối đa của bình luận'],
            'reels.trending_weight' => ['value' => '60', 'type' => 'integer', 'label' => 'Tỷ lệ Engine Trending (%)'],
            'reels.newest_weight' => ['value' => '40', 'type' => 'integer', 'label' => 'Tỷ lệ Engine Newest (%)'],
            'reels.snapshot_ttl_minutes' => ['value' => '60', 'type' => 'integer', 'label' => 'Thời gian sống của Snapshot (phút)'],
            'reels.analytics_enabled' => ['value' => '1', 'type' => 'boolean', 'label' => 'Bật thu thập dữ liệu Analytics'],
            'reels.mask_ips' => ['value' => '1', 'type' => 'boolean', 'label' => 'Ẩn một phần IP khách truy cập để bảo vệ quyền riêng tư'],
            'reels.retention_days' => ['value' => '90', 'type' => 'integer', 'label' => 'Số ngày lưu trữ nhật ký xem (ngày)'],
            'reels.default_seo_title_suffix' => ['value' => ' | Nobi Fashion Reels', 'type' => 'string', 'label' => 'Hậu tố tiêu đề SEO'],
            'reels.default_seo_description' => ['value' => 'Khám phá video thời trang xu hướng, phong cách trẻ trung và BST mới nhất tại Nobi Fashion Reels.', 'type' => 'string', 'label' => 'Mô tả SEO mặc định'],
        ];

        // Lấy các giá trị hiện có từ bảng settings
        $settings = [];
        $existing = Setting::where('group', 'reels')->get()->keyBy('key');

        foreach ($defaultSettings as $key => $config) {
            if ($existing->has($key)) {
                $item = $existing->get($key);
                $settings[$key] = $item->type === 'boolean' ? (bool) $item->value : $item->value;
            } else {
                $settings[$key] = $config['type'] === 'boolean' ? (bool) $config['value'] : $config['value'];
            }
        }

        return view('admins.reels.settings.index', compact('settings'));
    }

    /**
     * Cập nhật cài đặt
     */
    public function update(Request $request): RedirectResponse
    {
        $keys = [
            'reels.enabled' => 'boolean',
            'reels.comments_enabled' => 'boolean',
            'reels.likes_enabled' => 'boolean',
            'reels.share_enabled' => 'boolean',
            'reels.save_enabled' => 'boolean',
            'reels.batch_limit' => 'integer',
            'reels.max_duration' => 'integer',
            'reels.moderation_enabled' => 'boolean',
            'reels.block_links' => 'boolean',
            'reels.stickers_enabled' => 'boolean',
            'reels.max_comment_length' => 'integer',
            'reels.trending_weight' => 'integer',
            'reels.newest_weight' => 'integer',
            'reels.snapshot_ttl_minutes' => 'integer',
            'reels.analytics_enabled' => 'boolean',
            'reels.mask_ips' => 'boolean',
            'reels.retention_days' => 'integer',
            'reels.default_seo_title_suffix' => 'string',
            'reels.default_seo_description' => 'string',
        ];

        foreach ($keys as $key => $type) {
            $value = $request->input(str_replace('.', '_', $key));

            if ($type === 'boolean') {
                $value = $request->has(str_replace('.', '_', $key)) ? '1' : '0';
            }

            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'type' => $type,
                    'group' => 'reels',
                    'is_public' => false,
                ]
            );

            Cache::forget("setting_{$key}");
        }

        Cache::forget('settings_all');

        $this->adminService->logAudit('update_reels_settings', ['keys' => array_keys($keys)]);

        return back()->with('success', 'Đã lưu cấu hình cài đặt Reels thành công.');
    }
}

