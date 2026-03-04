<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VoucherStoreRequest;
use App\Http\Requests\Admin\VoucherTestRequest;
use App\Http\Requests\Admin\VoucherUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherService $voucherService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only([
            'status',
            'type',
            'applicable_to',
            'created_by',
            'date_from',
            'date_to',
            'search',
        ]);

        $vouchers = Voucher::query()
            ->with('account')
            ->filter($filters)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'active' => Voucher::where('status', Voucher::STATUS_ACTIVE)->count(),
            'scheduled' => Voucher::where('status', Voucher::STATUS_SCHEDULED)->count(),
            'expired' => Voucher::where('status', Voucher::STATUS_EXPIRED)->count(),
            'disabled' => Voucher::where('status', Voucher::STATUS_DISABLED)->count(),
        ];

        return view('admins.vouchers.index', compact('vouchers', 'filters', 'stats'));
    }

    public function create(): View
    {
        $voucher = new Voucher([
            'code' => strtoupper(Str::random(8)),
            'type' => Voucher::TYPE_FIXED_AMOUNT,
            'applicable_to' => Voucher::APPLICABLE_ALL,
            'status' => Voucher::STATUS_ACTIVE,
            'value' => 50000,
        ]);

        return view('admins.vouchers.create', [
            'voucher' => $voucher,
            'categories' => $this->categoryOptions(),
            'productCategories' => Category::where('is_active', true)->orderBy('name')->get(),
            'products' => [],
            'voucherImages' => $this->getVoucherImages(),
        ]);
    }

    public function store(VoucherStoreRequest $request): RedirectResponse
    {
        $payload = $this->buildPayload($request->validated());
        $payload['account_id'] = auth('web')->id();

        // Xử lý upload ảnh
        if ($request->hasFile('image_file')) {
            $imagePath = $this->uploadVoucherImage($request->file('image_file'));
            if ($imagePath) {
                $payload['image'] = $imagePath;
            }
        }

        $voucher = Voucher::create($payload);
        $voucher->refreshComputedStatus();
        $voucher->save();

        $this->voucherService->forgetCache($voucher->code);
        $this->voucherService->logHistory($voucher, 'created', null, $voucher->toArray(), 'Tạo voucher mới');

        return redirect()
            ->route('admin.vouchers.edit', $voucher)
            ->with('success', 'Tạo voucher thành công.');
    }

    public function show(Voucher $voucher): View
    {
        $voucher->load(['account', 'histories.account', 'userUsages.account']);
        
        $orders = \App\Models\Order::where('voucher_id', $voucher->id)
            ->with(['account', 'items.product'])
            ->latest()
            ->paginate(20);
        
        $stats = [
            'total_usage' => $voucher->usage_count,
            'total_orders' => \App\Models\Order::where('voucher_id', $voucher->id)->count(),
            'total_discount' => \App\Models\Order::where('voucher_id', $voucher->id)->sum('voucher_discount'),
            'unique_users' => \App\Models\Order::where('voucher_id', $voucher->id)->distinct('account_id')->count('account_id'),
        ];

        return view('admins.vouchers.show', compact('voucher', 'orders', 'stats'));
    }

    public function edit(Voucher $voucher): View
    {
        return view('admins.vouchers.edit', [
            'voucher' => $voucher,
            'categories' => $this->categoryOptions(),
            'productCategories' => Category::where('is_active', true)->orderBy('name')->get(),
            'products' => [],
            'histories' => $voucher->histories()->with('account')->latest()->limit(10)->get(),
            'voucherImages' => $this->getVoucherImages(),
        ]);
    }

    public function update(VoucherUpdateRequest $request, Voucher $voucher): RedirectResponse
    {
        $before = $voucher->toArray();

        $payload = $this->buildPayload($request->validated());

        // Xử lý upload ảnh
        if ($request->hasFile('image_file')) {
            // Xóa ảnh cũ nếu có
            if ($voucher->image && strpos($voucher->image, 'clients/assets/img/vouchers') !== false) {
                $oldPath = public_path($voucher->image);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }
            $imagePath = $this->uploadVoucherImage($request->file('image_file'));
            if ($imagePath) {
                $payload['image'] = $imagePath;
            }
        }

        $voucher->fill($payload);
        $voucher->refreshComputedStatus();
        $voucher->save();

        $this->voucherService->forgetCache($voucher->code);
        $this->voucherService->logHistory($voucher, 'updated', $before, $voucher->fresh()->toArray(), 'Cập nhật voucher');

        return redirect()
            ->route('admin.vouchers.edit', $voucher)
            ->with('success', 'Cập nhật voucher thành công.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $voucher->delete();
        $this->voucherService->logHistory($voucher, 'deleted', $voucher->toArray(), null, 'Xóa mềm voucher');

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher đã được chuyển vào thùng rác.');
    }

    public function restore(int $voucherId): RedirectResponse
    {
        $voucher = Voucher::withTrashed()->findOrFail($voucherId);
        $voucher->restore();
        $this->voucherService->logHistory($voucher, 'restored', null, $voucher->toArray(), 'Khôi phục voucher');

        return redirect()->route('admin.vouchers.edit', $voucher)
            ->with('success', 'Đã khôi phục voucher.');
    }

    public function toggle(Voucher $voucher): RedirectResponse
    {
        $before = $voucher->status;

        if ($voucher->status === Voucher::STATUS_DISABLED) {
            $voucher->status = Voucher::STATUS_ACTIVE;
        } else {
            $voucher->status = Voucher::STATUS_DISABLED;
        }

        $voucher->save();
        $this->voucherService->logHistory($voucher, 'toggle', ['status' => $before], ['status' => $voucher->status], 'Thay đổi trạng thái');

        return back()->with('success', 'Đã cập nhật trạng thái voucher.');
    }

    public function duplicate(Voucher $voucher): RedirectResponse
    {
        $clone = $voucher->replicate();
        $clone->code = strtoupper($voucher->code . '-' . Str::upper(Str::random(4)));
        $clone->usage_count = 0;
        $clone->status = Voucher::STATUS_SCHEDULED;
        $clone->start_at = now()->addDay();
        $clone->end_at = $voucher->end_at ? $voucher->end_at->addDays(7) : null;
        $clone->save();

        $this->voucherService->logHistory($clone, 'duplicated', null, $clone->toArray(), 'Nhân bản từ voucher #' . $voucher->id);

        return redirect()->route('admin.vouchers.edit', $clone)
            ->with('success', 'Đã nhân bản voucher. Vui lòng cập nhật thông tin.');
    }

    public function test(VoucherTestRequest $request): JsonResponse
    {
        $result = $this->voucherService->validateAndApplyVoucher(
            $request->input('voucher_code'),
            $request->validated(),
            auth('web')->id(),
            ['allow_flash_sale' => $request->boolean('allow_flash_sale')]
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    protected function buildPayload(array $validated): array
    {
        $payload = $validated;

        $payload['code'] = strtoupper($payload['code']);
        $payload['applicable_ids'] = $payload['applicable_to'] === Voucher::APPLICABLE_ALL
            ? null
            : array_values(array_filter($payload['applicable_ids'] ?? []));

        if ($payload['type'] === Voucher::TYPE_FREE_SHIPPING) {
            $payload['value'] = 0;
        }

        if (empty($payload['start_at']) && $payload['status'] === Voucher::STATUS_SCHEDULED) {
            $payload['start_at'] = now()->addDay();
        }

        return $payload;
    }

    protected function categoryOptions()
    {
        return Category::query()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function getVoucherImages(): array
    {
        $directory = public_path('clients/assets/img/vouchers');
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        foreach (File::allFiles($directory) as $file) {
            $relative = str_replace(public_path(), '', $file->getRealPath());
            $relative = str_replace('\\', '/', $relative);
            $relative = ltrim($relative, '/');

            $files[] = [
                'name' => $file->getFilename(),
                'url' => asset($relative),
                'path' => $relative,
            ];
        }

        return $files;
    }

    protected function uploadVoucherImage($file): ?string
    {
        if (!$file || !$file->isValid()) {
            return null;
        }

        $directory = public_path('clients/assets/img/vouchers');
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $filename);
        @chmod($directory . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $imagePath = $this->uploadVoucherImage($request->file('image'));

        if ($imagePath) {
            return response()->json([
                'success' => true,
                'url' => asset($imagePath),
                'path' => $imagePath,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Upload ảnh thất bại.',
        ], 422);
    }

    public function getProducts(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('is_active', true)
            ->with('primaryCategory')
            ->select('id', 'sku', 'name', 'price', 'sale_price', 'primary_category_id');

        // Tìm kiếm theo tên hoặc mã
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Lọc theo danh mục
        if ($request->has('category_id') && $request->category_id) {
            $query->where(function ($q) use ($request) {
                $q->where('primary_category_id', $request->category_id)
                  ->orWhereJsonContains('category_ids', (int)$request->category_id);
            });
        }

        $products = $query->orderBy('name')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}

