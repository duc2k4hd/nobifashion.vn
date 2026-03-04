<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\ClientChangePasswordRequest;
use App\Http\Requests\Client\ClientPreferencesUpdateRequest;
use App\Http\Requests\Client\ClientProfileUpdateRequest;
use App\Models\AccountLog;
use App\Models\Order;
use App\Models\Profile;
use App\Services\AccountLogService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(protected AccountLogService $accountLogService)
    {
    }

    public function index(): View
    {
        /** @var \App\Models\Account $account */
        $account = Auth::user();

        return view('clients.pages.profile.index', [
            'account' => $account,
        ]);
    }

    public function update(ClientProfileUpdateRequest $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\Account $account */
        $account = Auth::user();
        $profile = $this->ensureProfile($account);

        $data = $request->validated();
        $changes = [];

        // Xử lý xóa avatar
        if ($request->boolean('remove_avatar') && $profile->avatar) {
            $this->rememberHistory($profile, 'avatar', $profile->avatar);
            $profile->avatar = null;
            $changes['avatar'] = null;
        }

        // Xử lý xóa sub_avatar
        if ($request->boolean('remove_sub_avatar') && $profile->sub_avatar) {
            $this->rememberHistory($profile, 'sub_avatar', $profile->sub_avatar);
            $profile->sub_avatar = null;
            $changes['sub_avatar'] = null;
        }

        // Xử lý upload avatar
        if ($request->hasFile('avatar')) {
            $this->rememberHistory($profile, 'avatar', $profile->avatar);
            $profile->avatar = $this->storeAvatarFile($request->file('avatar'), $account, 'main');
            $changes['avatar'] = $profile->avatar;
        }

        // Xử lý upload sub_avatar
        if ($request->hasFile('sub_avatar')) {
            $this->rememberHistory($profile, 'sub_avatar', $profile->sub_avatar);
            $profile->sub_avatar = $this->storeAvatarFile($request->file('sub_avatar'), $account, 'sub');
            $changes['sub_avatar'] = $profile->sub_avatar;
        }

        // Cập nhật các trường khác
        $updateData = $request->only(['full_name', 'nickname', 'bio', 'gender', 'birthday', 'phone', 'location']);
        
        // 处理每个字段
        foreach ($updateData as $key => $value) {
            if ($value !== null) {
                // 对于空字符串，某些字段允许为空
                if ($value === '' && in_array($key, ['bio', 'phone', 'location'])) {
                    $profile->{$key} = null;
                } elseif ($value !== '') {
                    $profile->{$key} = $value;
                }
            }
        }
        
        $profile->save();
        $profile->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật hồ sơ thành công!',
                'profile' => [
                    'full_name' => $profile->full_name,
                    'nickname' => $profile->nickname,
                    'bio' => $profile->bio,
                    'gender' => $profile->gender,
                    'birthday' => $profile->birthday ? $profile->birthday->format('Y-m-d') : null,
                    'phone' => $profile->phone,
                    'avatar' => $profile->avatar,
                    'sub_avatar' => $profile->sub_avatar,
                    'avatar_url' => $this->getAvatarUrl($profile->avatar),
                    'sub_avatar_url' => $this->getAvatarUrl($profile->sub_avatar),
                ],
            ]);
        }

        return redirect()->route('client.profile.index')
            ->with('success', 'Cập nhật hồ sơ thành công!');
    }

    public function changePassword(ClientChangePasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\Account $account */
        $account = Auth::user();

        try {
            // 更新密码
            $account->password = Hash::make($request->input('password'));
            $account->last_password_changed_at = now();
            $account->save();

            // 记录日志
            $this->accountLogService->record(
                'auth.password_changed.client',
                $account->id,
                null,
                [],
                [
                    'ip' => $request->ip(),
                    'agent' => $request->userAgent(),
                ],
                false
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đổi mật khẩu thành công!',
                ]);
            }

            return redirect()->route('client.profile.index')
                ->with('success', 'Đổi mật khẩu thành công!');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi đổi mật khẩu. Vui lòng thử lại.',
                ], 500);
            }

            return redirect()->route('client.profile.index')
                ->with('error', 'Có lỗi xảy ra khi đổi mật khẩu. Vui lòng thử lại.');
        }
    }

    public function updatePreferences(ClientPreferencesUpdateRequest $request): JsonResponse|RedirectResponse
    {
        /** @var \App\Models\Account $account */
        $account = Auth::user();
        $profile = $this->ensureProfile($account);

        // 处理所有通知偏好字段（布尔值）
        $booleanFields = [
            'notify_order_created', 'notify_order_updated', 'notify_order_shipped', 
            'notify_order_completed', 'notify_promotions', 'notify_flash_sale',
            'notify_new_products', 'notify_stock_alert', 'notify_security',
            'notify_via_email', 'notify_via_sms', 'notify_via_in_app',
            'show_order_history', 'show_favorites'
        ];
        
        foreach ($booleanFields as $field) {
            $profile->{$field} = $request->has($field) ? $request->boolean($field) : false;
        }
        
        // 处理其他偏好设置
        if ($request->filled('preferred_language')) {
            $profile->preferred_language = $request->input('preferred_language');
        }
        if ($request->filled('preferred_timezone')) {
            $profile->preferred_timezone = $request->input('preferred_timezone');
        }
        if ($request->filled('preferred_currency')) {
            $profile->preferred_currency = $request->input('preferred_currency');
        }
        
        $profile->save();
        $profile->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật cài đặt thành công!',
                'preferences' => [
                    'notify_order_created' => $profile->notify_order_created ?? true,
                    'notify_order_updated' => $profile->notify_order_updated ?? true,
                    'notify_order_shipped' => $profile->notify_order_shipped ?? true,
                    'notify_order_completed' => $profile->notify_order_completed ?? true,
                    'notify_promotions' => $profile->notify_promotions ?? true,
                    'notify_flash_sale' => $profile->notify_flash_sale ?? true,
                    'notify_new_products' => $profile->notify_new_products ?? false,
                    'notify_stock_alert' => $profile->notify_stock_alert ?? false,
                    'notify_security' => $profile->notify_security ?? true,
                    'notify_via_email' => $profile->notify_via_email ?? true,
                    'notify_via_sms' => $profile->notify_via_sms ?? false,
                    'notify_via_in_app' => $profile->notify_via_in_app ?? true,
                    'show_order_history' => $profile->show_order_history ?? true,
                    'show_favorites' => $profile->show_favorites ?? true,
                    'preferred_language' => $profile->preferred_language ?? 'vi',
                    'preferred_timezone' => $profile->preferred_timezone ?? 'Asia/Ho_Chi_Minh',
                    'preferred_currency' => $profile->preferred_currency ?? 'VND',
                ],
            ]);
        }

        return redirect()->route('client.profile.index')
            ->with('success', 'Cập nhật cài đặt thành công!');
    }

    public function activities(Request $request): JsonResponse
    {
        /** @var \App\Models\Account $account */
        $account = Auth::user();

        $filter = Str::of($request->input('filter', 'all'))->lower()->value();
        $page = max((int) $request->input('page', 1), 1);
        $perPage = min(max((int) $request->input('per_page', 5), 5), 15);

        $logActivities = AccountLog::query()
            ->where('account_id', $account->id)
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (AccountLog $log) => $this->formatLogActivity($log))
            ->filter();

        $orderActivities = Order::query()
            ->where('account_id', $account->id)
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (Order $order) => $this->formatOrderActivity($order))
            ->filter();

        $activities = $logActivities
            ->merge($orderActivities)
            ->sortByDesc('timestamp')
            ->values();

        if ($filter !== 'all') {
            $activities = $activities
                ->filter(fn (array $activity) => $activity['group'] === $filter)
                ->values();
        }

        $total = $activities->count();
        $activities = $activities
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activities,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $total > ($page * $perPage),
            ],
        ]);
    }

    protected function ensureProfile($account): Profile
    {
        return $account->profile()->firstOrCreate([]);
    }

    protected function storeAvatarFile(UploadedFile $file, $account, string $type): string
    {
        $directory = public_path('admins/img/accounts');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = $account->id . '_' . $type . '_' . Str::random(20) . '.' . $extension;

        $file->move($directory, $filename);
        @chmod($directory . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    protected function getAvatarUrl(?string $filename): string
    {
        if (!$filename) {
            return '';
        }

        if (Str::startsWith($filename, ['http://', 'https://'])) {
            return $filename;
        }

        return asset('admins/img/accounts/' . ltrim($filename, '/'));
    }

    protected function rememberHistory(Profile $profile, string $field, ?string $oldFilename): void
    {
        if (!$oldFilename) {
            return;
        }

        $key = $field . '_history';
        $history = $profile->{$key};
        if (!is_array($history)) {
            $history = [];
        }
        array_unshift($history, $oldFilename);
        $profile->{$key} = array_slice(array_values(array_unique(array_filter($history))), 0, 5);
    }

    protected function formatLogActivity(AccountLog $log): ?array
    {
        $payload = $log->payload ?? [];
        $meta = $payload['meta'] ?? [];
        $changes = $payload['changes'] ?? [];

        $definitions = [
            'auth.register' => [
                'group' => 'security',
                'icon' => 'fas fa-user-plus',
                'icon_color' => 'text-emerald-500',
                'icon_background' => 'bg-emerald-100',
                'title' => 'Đăng ký tài khoản',
                'description' => 'Tài khoản được tạo thành công.',
                'badges' => [['text' => 'Bảo mật', 'color' => 'bg-emerald-50 text-emerald-600']],
            ],
            'auth.login.client' => [
                'group' => 'security',
                'icon' => 'fas fa-sign-in-alt',
                'icon_color' => 'text-blue-500',
                'icon_background' => 'bg-blue-100',
                'title' => 'Đăng nhập thành công',
                'description' => sprintf('Đăng nhập từ IP %s', $meta['ip'] ?? $log->ip ?? 'không xác định'),
                'badges' => [['text' => 'Bảo mật', 'color' => 'bg-blue-50 text-blue-600']],
            ],
            'auth.logout.client' => [
                'group' => 'security',
                'icon' => 'fas fa-sign-out-alt',
                'icon_color' => 'text-gray-500',
                'icon_background' => 'bg-gray-100',
                'title' => 'Đăng xuất',
                'description' => 'Bạn đã đăng xuất khỏi hệ thống.',
                'badges' => [['text' => 'Bảo mật', 'color' => 'bg-gray-50 text-gray-600']],
            ],
            'auth.failed.client' => [
                'group' => 'security',
                'icon' => 'fas fa-exclamation-triangle',
                'icon_color' => 'text-amber-500',
                'icon_background' => 'bg-amber-100',
                'title' => 'Đăng nhập thất bại',
                'description' => 'Nhập sai mật khẩu.',
                'badges' => [['text' => 'Cảnh báo', 'color' => 'bg-amber-50 text-amber-600']],
            ],
            'auth.locked.client' => [
                'group' => 'security',
                'icon' => 'fas fa-user-lock',
                'icon_color' => 'text-red-500',
                'icon_background' => 'bg-red-100',
                'title' => 'Tài khoản bị khóa',
                'description' => 'Tài khoản bị khóa tạm thời do nhập sai mật khẩu nhiều lần.',
                'badges' => [['text' => 'Khẩn cấp', 'color' => 'bg-red-50 text-red-600']],
            ],
            'auth.unverified' => [
                'group' => 'security',
                'icon' => 'fas fa-envelope-open-text',
                'icon_color' => 'text-indigo-500',
                'icon_background' => 'bg-indigo-100',
                'title' => 'Yêu cầu xác minh email',
                'description' => 'Chúng tôi đã gửi lại email xác minh cho bạn.',
                'badges' => [['text' => 'Email', 'color' => 'bg-indigo-50 text-indigo-600']],
            ],
            'auth.password_changed.client' => [
                'group' => 'security',
                'icon' => 'fas fa-key',
                'icon_color' => 'text-purple-500',
                'icon_background' => 'bg-purple-100',
                'title' => 'Đổi mật khẩu',
                'description' => 'Mật khẩu đã được đổi thành công.',
                'badges' => [['text' => 'Bảo mật', 'color' => 'bg-purple-50 text-purple-600']],
            ],
            'account.email_verified' => [
                'group' => 'account',
                'icon' => 'fas fa-check-circle',
                'icon_color' => 'text-emerald-500',
                'icon_background' => 'bg-emerald-100',
                'title' => 'Xác minh email',
                'description' => 'Email đã được xác minh thành công.',
                'badges' => [['text' => 'Xác minh', 'color' => 'bg-emerald-50 text-emerald-600']],
            ],
            'profile.avatar_updated' => [
                'group' => 'account',
                'icon' => 'fas fa-user-edit',
                'icon_color' => 'text-blue-500',
                'icon_background' => 'bg-blue-50',
                'title' => 'Cập nhật ảnh đại diện',
                'description' => 'Ảnh đại diện hoặc ảnh nền đã được thay đổi.',
                'badges' => [['text' => 'Hồ sơ', 'color' => 'bg-blue-50 text-blue-600']],
            ],
            'profile.updated' => [
                'group' => 'account',
                'icon' => 'fas fa-id-card',
                'icon_color' => 'text-sky-500',
                'icon_background' => 'bg-sky-50',
                'title' => 'Cập nhật hồ sơ',
                'description' => 'Thông tin hồ sơ đã được cập nhật.',
                'badges' => [['text' => 'Hồ sơ', 'color' => 'bg-sky-50 text-sky-600']],
            ],
        ];

        $definition = $definitions[$log->type] ?? null;

        if (!$definition) {
            $definition = [
                'group' => 'account',
                'icon' => 'fas fa-info-circle',
                'icon_color' => 'text-gray-500',
                'icon_background' => 'bg-gray-100',
                'title' => Str::of($log->type)->replace('.', ' ')->headline(),
                'description' => $meta['message'] ?? 'Hoạt động tài khoản',
                'badges' => [['text' => 'Khác', 'color' => 'bg-gray-50 text-gray-600']],
            ];
        }

        $timestamp = optional($log->created_at)->timestamp ?? 0;

        return [
            'id' => 'log_' . $log->id,
            'type' => $log->type,
            'group' => $definition['group'],
            'icon' => $definition['icon'],
            'icon_color' => $definition['icon_color'],
            'icon_background' => $definition['icon_background'],
            'title' => $definition['title'],
            'description' => $definition['description'],
            'badges' => $definition['badges'],
            'time_human' => optional($log->created_at)?->diffForHumans(),
            'time_exact' => $this->formatExactTime($log->created_at),
            'timestamp' => $timestamp,
            'meta' => [
                'ip' => $log->ip,
                'user_agent' => $this->shortAgent($log->user_agent),
                'changes' => $changes,
                'raw' => $meta,
            ],
        ];
    }

    protected function formatOrderActivity(Order $order): ?array
    {
        $status = $order->status ?? 'pending';
        $definitions = [
            'pending' => [
                'icon' => 'fas fa-receipt',
                'icon_color' => 'text-amber-500',
                'icon_background' => 'bg-amber-100',
                'description' => 'Đơn hàng đang chờ xác nhận.',
                'badge' => ['text' => 'Chờ xử lý', 'color' => 'bg-amber-50 text-amber-600'],
            ],
            'processing' => [
                'icon' => 'fas fa-sync-alt',
                'icon_color' => 'text-blue-500',
                'icon_background' => 'bg-blue-100',
                'description' => 'Đơn hàng đang được xử lý.',
                'badge' => ['text' => 'Đang xử lý', 'color' => 'bg-blue-50 text-blue-600'],
            ],
            'completed' => [
                'icon' => 'fas fa-box',
                'icon_color' => 'text-emerald-500',
                'icon_background' => 'bg-emerald-100',
                'description' => 'Đơn hàng đã giao thành công.',
                'badge' => ['text' => 'Hoàn tất', 'color' => 'bg-emerald-50 text-emerald-600'],
            ],
            'cancelled' => [
                'icon' => 'fas fa-times-circle',
                'icon_color' => 'text-red-500',
                'icon_background' => 'bg-red-100',
                'description' => 'Đơn hàng đã bị hủy.',
                'badge' => ['text' => 'Đã hủy', 'color' => 'bg-red-50 text-red-600'],
            ],
        ];

        $definition = $definitions[$status] ?? $definitions['pending'];
        $time = $order->updated_at ?? $order->created_at;
        $orderCode = $order->code ?? $order->id;

        return [
            'id' => 'order_' . $order->id,
            'type' => 'order.' . $status,
            'group' => 'order',
            'icon' => $definition['icon'],
            'icon_color' => $definition['icon_color'],
            'icon_background' => $definition['icon_background'],
            'title' => 'Đơn hàng #' . $orderCode,
            'description' => $definition['description'],
            'badges' => [
                $definition['badge'],
                [
                    'text' => strtoupper($order->payment_status ?? 'unpaid'),
                    'color' => 'bg-gray-50 text-gray-600',
                ],
            ],
            'time_human' => $time?->diffForHumans(),
            'time_exact' => $this->formatExactTime($time),
            'timestamp' => $time?->timestamp ?? 0,
            'meta' => [
                'status' => $status,
                'delivery_status' => $order->delivery_status,
                'payment_status' => $order->payment_status,
                'amount' => $this->formatCurrency($order->final_price ?? $order->total_price),
                'link' => route('client.order.show', $order->id),
            ],
            'action_text' => 'Xem chi tiết',
            'action_url' => route('client.order.show', $order->id),
        ];
    }

    protected function formatExactTime(?Carbon $time): ?string
    {
        if (!$time) {
            return null;
        }

        return $time->clone()
            ->timezone(config('app.timezone', 'Asia/Ho_Chi_Minh'))
            ->format('H:i d/m/Y');
    }

    protected function formatCurrency($value): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format((float) $value, 0, ',', '.') . ' ₫';
    }

    protected function shortAgent(?string $agent): ?string
    {
        if (!$agent) {
            return null;
        }

        return Str::limit($agent, 80);
    }
}

