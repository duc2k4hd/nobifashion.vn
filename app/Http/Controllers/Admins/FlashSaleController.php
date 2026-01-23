<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FlashSale\StoreFlashSaleRequest;
use App\Http\Requests\Admin\FlashSale\UpdateFlashSaleRequest;
use App\Http\Requests\Admin\FlashSale\StoreFlashSaleItemRequest;
use App\Http\Requests\Admin\FlashSale\UpdateFlashSaleItemRequest;
use App\Http\Requests\Admin\FlashSale\ImportFlashSaleItemsRequest;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Services\Admin\FlashSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlashSaleController extends Controller
{
    public function __construct(protected FlashSaleService $flashSaleService)
    {
    }

    /**
     * Danh sách Flash Sale
     */
    public function index(Request $request)
    {
        $query = FlashSale::query()
            ->withCount('items')
            ->with('creator');

        // Filter theo status
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'draft') {
                $query->where('status', 'draft');
            } elseif ($status === 'scheduled') {
                $query->where('status', 'active')
                    ->where('start_time', '>', now());
            } elseif ($status === 'running') {
                $query->where('status', 'active')
                    ->where('start_time', '<=', now())
                    ->where('end_time', '>=', now());
            } elseif ($status === 'ended') {
                $query->where('status', 'expired')
                    ->orWhere(function ($q) {
                        $q->where('status', 'active')
                            ->where('end_time', '<', now());
                    });
            }
        }

        // Filter theo ngày
        if ($request->filled('from_date')) {
            $query->whereDate('start_time', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('end_time', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('tag', 'like', "%{$search}%");
            });
        }

        $flashSales = $query->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admins.flash-sales.index', compact('flashSales'));
    }

    /**
     * Form tạo mới
     */
    public function create()
    {
        return view('admins.flash-sales.form', [
            'flashSale' => new FlashSale(),
        ]);
    }

    /**
     * Lưu Flash Sale mới
     */
    public function store(StoreFlashSaleRequest $request)
    {
        try {
            $flashSale = $this->flashSaleService->create($request->validated());

            return redirect()
                ->route('admin.flash-sales.edit', $flashSale)
                ->with('success', 'Tạo Flash Sale thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Không thể tạo Flash Sale: ' . $e->getMessage());
        }
    }

    /**
     * Chi tiết Flash Sale
     */
    public function show(FlashSale $flashSale)
    {
        $flashSale->load(['items.product', 'creator']);
        $this->hydrateFlashSaleSoldStats($flashSale->items);
        $flashSaleStats = $this->summarizeFlashSaleItems($flashSale->items);

        return view('admins.flash-sales.show', [
            'flashSale' => $flashSale,
            'flashSaleStats' => $flashSaleStats,
        ]);
    }

    /**
     * Form chỉnh sửa
     */
    public function edit(FlashSale $flashSale)
    {
        $flashSale->load(['items.product', 'creator']);

        return view('admins.flash-sales.form', [
            'flashSale' => $flashSale,
        ]);
    }

    /**
     * Cập nhật Flash Sale
     */
    public function update(UpdateFlashSaleRequest $request, FlashSale $flashSale)
    {
        try {
            $this->flashSaleService->update($flashSale, $request->validated());

            return redirect()
                ->route('admin.flash-sales.edit', $flashSale)
                ->with('success', 'Cập nhật Flash Sale thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật Flash Sale: ' . $e->getMessage());
        }
    }

    /**
     * Xóa Flash Sale
     */
    public function destroy(FlashSale $flashSale)
    {
        try {
            $this->flashSaleService->delete($flashSale);

            return redirect()
                ->route('admin.flash-sales.index')
                ->with('success', 'Xóa Flash Sale thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể xóa Flash Sale: ' . $e->getMessage());
        }
    }

    /**
     * Nhân bản Flash Sale
     */
    public function duplicate(FlashSale $flashSale)
    {
        try {
            $newFlashSale = $this->flashSaleService->duplicate($flashSale);

            return redirect()
                ->route('admin.flash-sales.edit', $newFlashSale)
                ->with('success', 'Nhân bản Flash Sale thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể nhân bản Flash Sale: ' . $e->getMessage());
        }
    }

    /**
     * Trang thống kê Flash Sale
     */
    public function stats(FlashSale $flashSale)
    {
        $flashSale->load(['items.product']);
        $this->hydrateFlashSaleSoldStats($flashSale->items);

        $totalItems = $flashSale->items->count();
        $activeItems = $flashSale->items->where('is_active', true)->count();
        $soldItems = $flashSale->items->sum(function ($item) {
            return $item->display_sold ?? (int) $item->sold ?? 0;
        });
        $totalStock = $flashSale->items->sum('stock');
        $totalRemaining = $flashSale->items->sum(function ($item) {
            return $item->display_remaining ?? max(0, ($item->stock ?? 0) - ($item->display_sold ?? (int) $item->sold ?? 0));
        });

        $totalRevenue = $flashSale->items->reduce(function ($carry, $item) {
            $sold = $item->display_sold ?? (int) $item->sold ?? 0;
            return $carry + ($sold * ($item->sale_price ?? 0));
        }, 0);

        $topSelling = $flashSale->items
            ->sortByDesc(function ($item) {
                return $item->display_sold ?? (int) $item->sold ?? 0;
            })
            ->slice(0, 10)
            ->values();

        $topRevenue = $flashSale->items
            ->filter(fn ($item) => $item->sale_price)
            ->sortByDesc(function ($item) {
                $sold = $item->display_sold ?? (int) $item->sold ?? 0;
                return $item->sale_price * $sold;
            })
            ->take(5)
            ->values();

        return view('admins.flash-sales.stats', [
            'flashSale' => $flashSale,
            'stats' => [
                'total_items' => $totalItems,
                'active_items' => $activeItems,
                'sold_items' => $soldItems,
                'total_stock' => $totalStock,
                'total_remaining' => $totalRemaining,
                'total_revenue' => $totalRevenue,
            ],
            'topSelling' => $topSelling,
            'topRevenue' => $topRevenue,
        ]);
    }

    /**
     * Xuất bản Flash Sale
     */
    public function publish(FlashSale $flashSale)
    {
        try {
            $this->flashSaleService->publish($flashSale);

            return back()
                ->with('success', 'Xuất bản Flash Sale thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể xuất bản Flash Sale: ' . $e->getMessage());
        }
    }

    /**
     * Toggle is_active
     */
    public function toggleActive(FlashSale $flashSale)
    {
        try {
            $this->flashSaleService->toggleActive($flashSale);

            return back()
                ->with('success', 'Cập nhật trạng thái thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể cập nhật trạng thái: ' . $e->getMessage());
        }
    }

    /**
     * Danh sách items của Flash Sale
     */
    public function items(FlashSale $flashSale, Request $request)
    {
        $query = $flashSale->items()
            ->with(['product.primaryImage'])
            ->orderBy('sort_order')
            ->orderBy('id');

        // Filter
        if ($request->filled('filter')) {
            $filter = $request->filter;
            if ($filter === 'available') {
                $query->where('is_active', true)
                    ->whereRaw('stock > sold');
            } elseif ($filter === 'sold_out') {
                $query->whereRaw('stock <= sold');
            } elseif ($filter === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(20)
            ->appends($request->query());
        $this->hydrateFlashSaleSoldStats($items->getCollection());
        $pageStats = $this->summarizeFlashSaleItems($items->getCollection());
        $pageStats['total_items'] = $items->total();

        // Lấy danh sách categories nếu mode là auto_by_category
        $categories = null;
        if ($flashSale->product_add_mode === 'auto_by_category') {
            $categories = Category::where('is_active', true)->orderBy('name')->get();
        }

        return view('admins.flash-sales.items', [
            'flashSale' => $flashSale,
            'items' => $items,
            'categories' => $categories,
            'pageStats' => $pageStats,
        ]);
    }

    /**
     * Thêm sản phẩm vào Flash Sale
     */
    public function addItem(Request $request, FlashSale $flashSale)
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'default_sale_price_percent' => 'nullable|numeric|min:0|max:90',
            'default_stock' => 'nullable|integer|min:1',
            'default_max_per_user' => 'nullable|integer|min:1',
            'set_mode' => 'nullable|in:manual,auto_by_category',
        ]);

        try {
            // Nếu chưa có sản phẩm và có set_mode, set mode cho flash sale
            if ($flashSale->items()->count() === 0 && $request->has('set_mode')) {
                $mode = $request->input('set_mode');
                if (in_array($mode, ['manual', 'auto_by_category'])) {
                    $flashSale->update(['product_add_mode' => $mode]);
                }
            }

            // Kiểm tra mode
            if ($flashSale->product_add_mode === 'auto_by_category') {
                return back()
                    ->with('error', 'Flash Sale này chỉ hỗ trợ thêm sản phẩm tự động theo danh mục.');
            }

            // Chuẩn bị default data
            $defaultData = [];
            if ($request->filled('default_sale_price_percent')) {
                $defaultData['sale_price_percent'] = $request->default_sale_price_percent;
            }
            if ($request->filled('default_stock')) {
                $defaultData['stock'] = $request->default_stock;
            }
            if ($request->filled('default_max_per_user')) {
                $defaultData['max_per_user'] = $request->default_max_per_user;
            }

            // Thêm nhiều sản phẩm
            $result = $this->flashSaleService->addProducts(
                $flashSale,
                $request->product_ids,
                $defaultData
            );

            $message = "Đã thêm " . count($result['added']) . " sản phẩm";
            if (!empty($result['errors'])) {
                $message .= ". Có " . count($result['errors']) . " lỗi";
            }

            return back()
                ->with('success', $message)
                ->with('bulk_errors', $result['errors'] ?? []);
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->withInput()
                ->with('error', 'Không thể thêm sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Thêm sản phẩm tự động từ danh mục
     */
    public function addItemsByCategories(Request $request, FlashSale $flashSale)
    {
        $request->validate([
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'default_sale_price_percent' => 'nullable|numeric|min:0|max:90',
        ]);

        try {
            // Nếu chưa có sản phẩm, set mode cho flash sale
            if ($flashSale->items()->count() === 0) {
                $flashSale->update(['product_add_mode' => 'auto_by_category']);
            }

            // Kiểm tra mode
            if ($flashSale->product_add_mode !== 'auto_by_category') {
                return back()
                    ->with('error', 'Flash Sale này chỉ hỗ trợ thêm sản phẩm thủ công.');
            }

            $defaultData = [];
            if ($request->filled('default_sale_price_percent')) {
                $defaultData['sale_price_percent'] = $request->default_sale_price_percent;
            }

            $result = $this->flashSaleService->addProductsByCategories(
                $flashSale,
                $request->category_ids,
                $defaultData
            );

            $message = "Đã thêm " . count($result['added']) . " sản phẩm từ " . count($request->category_ids) . " danh mục";
            if (!empty($result['errors'])) {
                $message .= ". Có " . count($result['errors']) . " lỗi";
            }

            return back()
                ->with('success', $message)
                ->with('bulk_errors', $result['errors'] ?? []);
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể thêm sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Import sản phẩm từ file Excel
     */
    public function importItemsFromExcel(ImportFlashSaleItemsRequest $request, FlashSale $flashSale)
    {
        try {
            $result = $this->flashSaleService->importItemsFromExcel($flashSale, $request->file('file'));

            $message = "Đã import {$result['success']} sản phẩm vào Flash Sale.";

            return back()
                ->with('success', $message)
                ->with('import_errors', $result['errors']);
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể import sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Tải file mẫu import
     */
    public function downloadImportTemplate(FlashSale $flashSale)
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $fileName = 'flash-sale-import-template.xlsx';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');

        $columns = [
            'A1' => 'SKU',
            'B1' => 'Original Price',
            'C1' => 'Sale Price',
            'D1' => 'Stock',
            'E1' => 'Max Per User',
            'F1' => 'Is Active (1/0)',
        ];

        foreach ($columns as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->fromArray(
            [
                ['SKU001', 500000, 400000, 50, 1, 1],
                ['SKU002', 300000, 240000, 30, 2, 1],
            ],
            null,
            'A2',
            true
        );

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, $headers);
    }

    /**
     * Bulk action cho danh sách sản phẩm trong Flash Sale
     */
    public function bulkActionItems(Request $request, FlashSale $flashSale)
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'exists:flash_sale_items,id',
        ]);

        try {
            $result = $this->flashSaleService->bulkActionItems(
                $flashSale,
                $validated['item_ids'],
                $validated['action']
            );

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Không thể thực hiện hành động: ' . $e->getMessage());
        }
    }

    /**
     * Cập nhật Flash Sale Item
     */
    public function updateItem(UpdateFlashSaleItemRequest $request, FlashSale $flashSale, FlashSaleItem $item)
    {
        try {
            $this->flashSaleService->updateItem($item, $request->validated());

            // Nếu là AJAX request (inline edit)
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật thành công'
                ]);
            }

            return back()
                ->with('success', 'Cập nhật sản phẩm thành công');
        } catch (\Throwable $e) {
            report($e);
            
            // Nếu là AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }

            return back()
                ->withInput()
                ->with('error', 'Không thể cập nhật sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Xóa Flash Sale Item
     */
    public function deleteItem(FlashSale $flashSale, FlashSaleItem $item)
    {
        try {
            $this->flashSaleService->deleteItem($item);

            return back()
                ->with('success', 'Xóa sản phẩm thành công');
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể xóa sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Xóa toàn bộ sản phẩm trong Flash Sale
     */
    public function deleteAllItems(Request $request, FlashSale $flashSale)
    {
        if (!$request->has('confirm') || $request->confirm != '1') {
            return back()
                ->with('error', 'Vui lòng xác nhận xóa toàn bộ sản phẩm.');
        }

        try {
            $count = $this->flashSaleService->deleteAllItems($flashSale);

            return back()
                ->with('success', "Đã xóa {$count} sản phẩm. Bạn có thể chọn lại chế độ thêm sản phẩm.");
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể xóa sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Tìm kiếm sản phẩm để thêm vào Flash Sale (cho TomSelect)
     */
    public function searchProducts(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with('primaryCategory');

        // Tìm kiếm theo tên hoặc SKU - BẮT BUỘC phải có query
        if (!$request->filled('q') || strlen(trim($request->q)) < 1) {
            return response()->json([]);
        }

        $search = trim($request->q);
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        });

        // Lọc theo danh mục nếu có
        if ($request->filled('category_id')) {
            $query->where('primary_category_id', $request->category_id);
        }

        // Loại bỏ sản phẩm đã có trong flash sale
        if ($request->filled('flash_sale_id')) {
            $flashSale = FlashSale::find($request->flash_sale_id);
            if ($flashSale) {
                $existingProductIds = $flashSale->items()->pluck('product_id')->toArray();
                if (!empty($existingProductIds)) {
                    $query->whereNotIn('id', $existingProductIds);
                }
            }
        }

        $products = $query->limit(50)->get();

        // Format cho TomSelect
        $results = $products->map(function ($product) {
            return [
                'value' => (string)$product->id,
                'text' => $product->name . ' (SKU: ' . $product->sku . ') - ' . number_format((float)($product->price ?? 0), 0, ',', '.') . '₫',
                'price' => $product->price,
                'stock' => $product->stock_quantity,
                'sku' => $product->sku,
            ];
        })->values()->toArray();

        // Log để debug
        Log::info('Search products', [
            'query' => $request->q,
            'count' => count($results),
            'flash_sale_id' => $request->flash_sale_id,
        ]);

        return response()->json($results);
    }

    /**
     * Lấy sản phẩm theo danh mục cho popup chọn nhanh
     */
    public function productsByCategory(Request $request)
    {
        $query = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->with(['primaryCategory', 'primaryImage']);

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('primary_category_id', $request->category_id);
        }

        if ($request->filled('q') && strlen(trim($request->q)) >= 1) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('flash_sale_id')) {
            $flashSale = FlashSale::find($request->flash_sale_id);
            if ($flashSale) {
                $existingProductIds = $flashSale->items()->pluck('product_id')->toArray();
                if (!empty($existingProductIds)) {
                    $query->whereNotIn('id', $existingProductIds);
                }
            }
        }

        $query->orderByDesc('is_featured')
            ->orderByDesc('updated_at');

        $perPage = (int) $request->get('per_page', 20);
        $perPage = max(10, min(60, $perPage));

        $products = $query->paginate($perPage);

        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock_quantity' => $product->stock_quantity,
                'category' => $product->primaryCategory->name ?? null,
                'image' => $product->primaryImage
                    ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
                    : asset('clients/assets/img/clothes/no-image.webp'),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Gợi ý sản phẩm bán chạy dựa trên đơn hàng hoàn tất
     */
    public function suggestBestSellingProducts(Request $request, FlashSale $flashSale)
    {
        $limit = (int) $request->get('limit', 20);
        $limit = max(5, min(40, $limit));

        $existingProductIds = $flashSale->items()->pluck('product_id')->toArray();

        $bestSellingTotals = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereNotNull('product_id')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['completed', 'processing', 'delivered']);
            })
            ->whereNotIn('product_id', $existingProductIds)
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit * 2)
            ->get()
            ->keyBy('product_id');

        $bestSellingIds = $bestSellingTotals
            ->keys()
            ->take($limit);

        $products = Product::with(['primaryCategory', 'primaryImage'])
            ->whereIn('id', $bestSellingIds)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->get()
            ->keyBy('id');

        $data = $bestSellingIds->map(function ($productId) use ($products, $bestSellingTotals) {
            $product = $products->get($productId);
            if (!$product) {
                return null;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'stock_quantity' => $product->stock_quantity,
                'category' => $product->primaryCategory->name ?? null,
                'image' => $product->primaryImage
                    ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
                    : asset('clients/assets/img/clothes/no-image.webp'),
                'total_sold' => (int) ($bestSellingTotals[$productId]->total_sold ?? 0),
            ];
        })->filter()->values();

        // Nếu chưa có dữ liệu đơn hàng, fallback sang sản phẩm nổi bật
        if ($data->isEmpty()) {
            $fallbackProducts = Product::query()
                ->where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->whereNotIn('id', $existingProductIds)
                ->orderByDesc('is_featured')
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->with(['primaryCategory', 'primaryImage'])
                ->get();

            $data = $fallbackProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'stock_quantity' => $product->stock_quantity,
                    'category' => $product->primaryCategory->name ?? null,
                    'image' => $product->primaryImage
                        ? asset('clients/assets/img/clothes/' . $product->primaryImage->url)
                        : asset('clients/assets/img/clothes/no-image.webp'),
                    'total_sold' => 0,
                ];
            });
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Lấy lịch sử thay đổi giá của một item
     */
    public function priceLogs(FlashSale $flashSale, FlashSaleItem $item)
    {
        if ($item->flash_sale_id !== $flashSale->id) {
            abort(404);
        }

        $logs = $item->priceLogs()
            ->with(['changer:id,name,email'])
            ->orderByDesc('changed_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($log) {
                return [
                    'old_price' => (float) $log->old_price,
                    'new_price' => (float) $log->new_price,
                    'changed_by' => $log->changer?->name ?? 'Hệ thống',
                    'changed_at' => optional($log->changed_at)->format('d/m/Y H:i') ?? optional($log->created_at)->format('d/m/Y H:i'),
                    'reason' => $log->reason,
                ];
            });

        return response()->json([
            'data' => $logs,
        ]);
    }

    /**
     * Revenue by time interval for charts
     */
    public function revenueByTime(Request $request, FlashSale $flashSale)
    {
        $interval = $request->get('interval', 'hour');
        $allowedIntervals = ['hour', 'day', 'week'];
        if (!in_array($interval, $allowedIntervals, true)) {
            $interval = 'hour';
        }

        $itemIds = $flashSale->items()->pluck('id');
        if ($itemIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $format = match ($interval) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            default => '%Y-%m-%d %H:00:00',
        };

        $labelFormatter = match ($interval) {
            'day' => 'Y-m-d',
            'week' => 'o-\WW',
            default => 'Y-m-d H:00',
        };

        $query = OrderItem::selectRaw("DATE_FORMAT(order_items.created_at, '{$format}') as period")
            ->selectRaw('SUM(order_items.total_price) as revenue')
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->whereIn('flash_sale_item_id', $itemIds)
            ->where('is_flash_sale', true)
            ->whereBetween('order_items.created_at', [$flashSale->start_time, $flashSale->end_time])
            ->whereHas('order', function ($orderQuery) {
                $orderQuery->whereNotIn('status', ['cancelled'])
                    ->whereNotIn('payment_status', ['failed']);
            })
            ->groupBy('period')
            ->orderBy('period');

        $data = $query->get()->map(function ($row) use ($labelFormatter) {
            $timestamp = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $row->period . (strlen($row->period) === 10 ? ' 00:00:00' : ''), config('app.timezone', 'UTC'));
            return [
                'period' => $timestamp ? $timestamp->format($labelFormatter) : $row->period,
                'revenue' => (float) $row->revenue,
                'quantity' => (int) $row->quantity,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Conversion metrics (views, cart adds, purchases)
     */
    public function conversionMetrics(FlashSale $flashSale)
    {
        $itemIds = $flashSale->items()->pluck('id');
        if ($itemIds->isEmpty()) {
            return response()->json([
                'views' => (int) ($flashSale->views ?? 0),
                'cart_adds' => 0,
                'purchases' => 0,
                'orders' => 0,
                'conversion_rate' => 0,
                'cart_to_order_rate' => 0,
            ]);
        }

        $views = (int) ($flashSale->views ?? 0);

        $cartAdds = CartItem::whereIn('flash_sale_item_id', $itemIds)
            ->whereBetween('created_at', [$flashSale->start_time, $flashSale->end_time])
            ->count();

        $purchasesQuery = OrderItem::whereIn('flash_sale_item_id', $itemIds)
            ->where('is_flash_sale', true)
            ->whereBetween('created_at', [$flashSale->start_time, $flashSale->end_time])
            ->whereHas('order', function ($orderQuery) {
                $orderQuery->whereNotIn('status', ['cancelled'])
                    ->whereNotIn('payment_status', ['failed']);
            });

        $purchases = (int) $purchasesQuery->sum('quantity');
        $orders = (int) $purchasesQuery->distinct('order_id')->count('order_id');

        $conversionRate = $views > 0 ? round(($purchases / $views) * 100, 2) : 0;
        $cartToOrderRate = $cartAdds > 0 ? round(($orders / $cartAdds) * 100, 2) : 0;

        return response()->json([
            'views' => $views,
            'cart_adds' => $cartAdds,
            'purchases' => $purchases,
            'orders' => $orders,
            'conversion_rate' => $conversionRate,
            'cart_to_order_rate' => $cartToOrderRate,
        ]);
    }

    /**
     * Heatmap data (day-hour)
     */
    public function salesHeatmap(FlashSale $flashSale)
    {
        $itemIds = $flashSale->items()->pluck('id');
        if ($itemIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $rows = OrderItem::whereIn('flash_sale_item_id', $itemIds)
            ->where('is_flash_sale', true)
            ->whereBetween('created_at', [$flashSale->start_time, $flashSale->end_time])
            ->whereHas('order', function ($orderQuery) {
                $orderQuery->whereNotIn('status', ['cancelled'])
                    ->whereNotIn('payment_status', ['failed']);
            })
            ->get(['created_at', 'quantity', 'total_price']);

        $heatmap = [];

        foreach ($rows as $row) {
            $day = $row->created_at->format('Y-m-d');
            $hour = $row->created_at->format('H');
            $key = "{$day}_{$hour}";

            if (!isset($heatmap[$key])) {
                $heatmap[$key] = [
                    'day' => $day,
                    'hour' => (int) $hour,
                    'quantity' => 0,
                    'revenue' => 0.0,
                ];
            }

            $heatmap[$key]['quantity'] += (int) $row->quantity;
            $heatmap[$key]['revenue'] += (float) $row->total_price;
        }

        return response()->json([
            'data' => array_values($heatmap),
        ]);
    }

    /**
     * Thêm nhiều sản phẩm cùng lúc
     */
    public function bulkAddItems(Request $request, FlashSale $flashSale)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'default_sale_price_percent' => 'nullable|numeric|min:0|max:90',
        ]);

        try {
            $productIds = $request->product_ids;
            $defaultData = [];

            if ($request->filled('default_sale_price_percent')) {
                $defaultData['sale_price'] = null; // Sẽ tính trong service
            }

            $result = $this->flashSaleService->addProducts($flashSale, $productIds, $defaultData);

            $message = "Đã thêm " . count($result['added']) . " sản phẩm";
            if (!empty($result['errors'])) {
                $message .= ". Có " . count($result['errors']) . " lỗi";
            }

            return back()
                ->with('success', $message)
                ->with('bulk_errors', $result['errors'] ?? []);
        } catch (\Throwable $e) {
            report($e);
            return back()
                ->with('error', 'Không thể thêm sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Preview Flash Sale trên frontend
     */
    public function preview(FlashSale $flashSale)
    {
        $flashSale->load([
            'items.product.primaryImage',
            'items.product.primaryCategory'
        ]);

        // Lọc items: chỉ hiển thị items active, còn hàng, và có product
        $flashSale->setRelation('items', $flashSale->items->filter(function ($item) {
            return $item->is_active 
                && ($item->stock > $item->sold) 
                && $item->product 
                && $item->product->is_active;
        }));

        return view('admins.flash-sales.preview', compact('flashSale'));
    }

    /**
     * So sánh nhiều flash sale
     */
    public function compare(Request $request)
    {
        $ids = $request->get('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $flashSales = FlashSale::whereIn('id', $ids)
            ->with(['items'])
            ->get();

        $data = $flashSales->map(function (FlashSale $flashSale) {
            $items = $flashSale->items;
            $totalItems = $items->count();
            $totalSold = $items->sum('sold');
            $totalRevenue = $items->sum(function ($item) {
                return ($item->sale_price ?? 0) * ($item->sold ?? 0);
            });

            $originalSum = $items->sum('original_price');
            $saleSum = $items->sum('sale_price');
            $averageDiscount = 0;
            if ($originalSum > 0 && $saleSum > 0) {
                $averageDiscount = round((1 - ($saleSum / $originalSum)) * 100, 2);
            }

            return [
                'id' => $flashSale->id,
                'title' => $flashSale->title,
                'period' => optional($flashSale->start_time)->format('d/m/Y') . ' - ' . optional($flashSale->end_time)->format('d/m/Y'),
                'total_items' => $totalItems,
                'total_sold' => $totalSold,
                'total_revenue' => $totalRevenue,
                'average_discount' => $averageDiscount,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    protected function hydrateFlashSaleSoldStats($items): void
    {
        if (!$items || $items->isEmpty()) {
            return;
        }

        $ids = $items->pluck('id')->filter()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $soldFromOrders = OrderItem::select('flash_sale_item_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereIn('flash_sale_item_id', $ids)
            ->whereHas('order', function ($query) {
                $query->where('status', '!=', 'cancelled');
            })
            ->groupBy('flash_sale_item_id')
            ->pluck('total_quantity', 'flash_sale_item_id');

        foreach ($items as $item) {
            $calculatedSold = (int) ($soldFromOrders[$item->id] ?? 0);
            $item->calculated_sold = $calculatedSold;
            $item->display_sold = max($calculatedSold, (int) $item->sold);
            $item->display_remaining = max(0, (int) $item->stock - $item->display_sold);
            $item->display_sold_percentage = ($item->stock ?? 0) > 0
                ? round(($item->display_sold / $item->stock) * 100, 2)
                : 0;
        }
    }

    protected function summarizeFlashSaleItems($items): array
    {
        if (!$items || $items->isEmpty()) {
            return [
                'total_items' => 0,
                'total_sold' => 0,
                'total_remaining' => 0,
            ];
        }

        return [
            'total_items' => $items->count(),
            'total_sold' => $items->sum(function ($item) {
                return $item->display_sold ?? (int) $item->sold ?? 0;
            }),
            'total_remaining' => $items->sum(function ($item) {
                if (isset($item->display_remaining)) {
                    return $item->display_remaining;
                }
                $sold = $item->display_sold ?? (int) $item->sold ?? 0;
                return max(0, (int) $item->stock - $sold);
            }),
        ];
    }
}

