<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingRequest;
use App\Models\Category;
use App\Models\Setting;
use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    protected array $protectedKeys = [
        'product_recommen',
        'contact_address',
        'contact_email',
        'contact_phone',
        'contact_zalo',
        'copyright',
        'dmca',
        'dmca_logo',
        'google_analytics',
        'google_search_console',
        'google_tag_body',
        'google_tag_header',
        'maintenance_mode',
        'site_banner',
        'site_description',
        'site_favicon',
        'site_logo',
        'site_tax_code',
        'site_name',
        'site_url',
        'site_title',
    ];

    public function index(Request $request)
    {
        $query = Setting::query();

        if ($keyword = $request->get('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('key', 'like', "%{$keyword}%")
                    ->orWhere('label', 'like', "%{$keyword}%")
                    ->orWhere('value', 'like', "%{$keyword}%");
            });
        }

        if ($group = $request->get('group')) {
            $query->where('group', $group);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if (($public = $request->get('is_public')) !== null && $public !== '') {
            $query->where('is_public', (bool) $public);
        }

        $settings_all = $query->orderBy('group')
            ->orderBy('key')
            ->paginate(20)
            ->appends($request->query());

        $groups = Setting::select('group')->distinct()->pluck('group')->filter();
        $types = $this->allowedTypes();

        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && strtolower(trim((string) $currentUser->email)) === 'admin@gmail.com';
        $protectedKeys = $this->protectedKeys;

        $stats = [
            'total'  => Setting::count(),
            'public' => Setting::where('is_public', true)->count(),
            'system' => Setting::whereIn('key', $this->protectedKeys)->count(),
        ];

        return view('admins.settings.index', compact(
            'settings_all',
            'groups',
            'types',
            'protectedKeys',
            'isSuperAdmin',
            'stats'
        ));
    }

    public function create()
    {
        $setting = new Setting();
        $groups = Setting::select('group')->distinct()->pluck('group')->filter();
        $types = $this->allowedTypes();
        $categories = Category::where('is_active', true)->select('id', 'name', 'slug', 'parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $protectedKeys = $this->protectedKeys;
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && strtolower(trim((string) $currentUser->email)) === 'admin@gmail.com';

        return view('admins.settings.create', compact('setting', 'groups', 'types', 'categories', 'protectedKeys', 'isSuperAdmin'));
    }

    public function store(SettingRequest $request)
    {
        $data = $this->normalizeValue($request->validated());

        $setting = Setting::create($data);

        if ($setting->key === 'product_recommen') {
            ProductRecommendationService::clearCache();
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Đã tạo setting thành công.');
    }

    public function edit(Setting $setting)
    {
        $groups = Setting::select('group')->distinct()->pluck('group')->filter();
        $types = $this->allowedTypes();
        $categories = Category::where('is_active', true)->select('id', 'name', 'slug', 'parent_id')->orderBy('sort_order')->orderBy('name')->get();
        $protectedKeys = $this->protectedKeys;
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && strtolower(trim((string) $currentUser->email)) === 'admin@gmail.com';

        return view('admins.settings.edit', compact('setting', 'groups', 'types', 'categories', 'protectedKeys', 'isSuperAdmin'));
    }

    public function update(SettingRequest $request, Setting $setting)
    {
        if (in_array($setting->key, $this->protectedKeys, true) && $request->key !== $setting->key) {
            throw ValidationException::withMessages([
                'key' => 'Không thể thay đổi key của setting hệ thống.',
            ]);
        }

        $data = $this->normalizeValue($request->validated());

        // giữ nguyên key khi bị khoá
        if (in_array($setting->key, $this->protectedKeys, true)) {
            $data['key'] = $setting->key;
        }

        $setting->update($data);

        if ($setting->key === 'product_recommen') {
            ProductRecommendationService::clearCache();
        }

        return redirect()->route('admin.settings.edit', $setting)
            ->with('success', 'Đã cập nhật setting.');
    }

    public function destroy(Setting $setting)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser && strtolower(trim((string) $currentUser->email)) === 'admin@gmail.com';

        if (in_array($setting->key, $this->protectedKeys, true) && !$isSuperAdmin) {
            return back()->with('error', "Cài đặt [{$setting->key}] là cấu hình hệ thống quan trọng, không được phép xoá (chỉ tài khoản admin@gmail.com mới có quyền xoá).");
        }

        $key = $setting->key;
        $setting->delete();

        if ($key === 'product_recommen') {
            ProductRecommendationService::clearCache();
        }

        return back()->with('success', "Đã xoá cài đặt '{$key}' thành công.");
    }

    private function normalizeValue(array $data): array
    {
        $value = $data['value'] ?? null;

        switch ($data['type']) {
            case 'boolean':
                $data['value'] = $value ? '1' : '0';
                break;
            case 'integer':
                $data['value'] = ($value !== null && $value !== '') ? (string) (int) $value : null;
                break;
            case 'float':
            case 'number':
                $data['value'] = (string) (float) $value;
                break;
            case 'json':
                $decoded = is_array($value) ? $value : json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw ValidationException::withMessages([
                        'value' => 'JSON không hợp lệ.',
                    ]);
                }
                $data['value'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                break;
            default:
                $data['value'] = $value ?? '';
        }

        return $data;
    }

    private function allowedTypes(): array
    {
        return [
            'string',
            'text',
            'textarea',
            'integer',
            'float',
            'number',
            'boolean',
            'json',
            'email',
            'url',
            'image',
        ];
    }
}


