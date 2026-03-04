<?php

namespace App\Services\Admin;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\FlashSalePriceLog;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FlashSaleService
{
    /**
     * Tạo mới Flash Sale
     */
    public function create(array $data): FlashSale
    {
        return DB::transaction(function () use ($data) {
            // Upload banner nếu có
            if (isset($data['banner']) && $data['banner']->isValid()) {
                $data['banner'] = $this->uploadBanner($data['banner']);
            } else {
                unset($data['banner']);
            }

            // Set created_by
            $data['created_by'] = Auth::id();

            // Tạo Flash Sale
            $flashSale = FlashSale::create($data);

            // Xóa cache
            $this->clearFlashSaleCache();

            return $flashSale;
        });
    }

    /**
     * Cập nhật Flash Sale
     */
    public function update(FlashSale $flashSale, array $data): FlashSale
    {
        // Kiểm tra có thể chỉnh sửa không
        if (!$flashSale->canEdit()) {
            throw new \Exception('Flash Sale đang chạy, không thể chỉnh sửa.');
        }

        return DB::transaction(function () use ($flashSale, $data) {
            // Upload banner mới nếu có
            if (isset($data['banner']) && $data['banner']->isValid()) {
                // Xóa banner cũ
                if ($flashSale->banner) {
                    $this->deleteBanner($flashSale->banner);
                }
                $data['banner'] = $this->uploadBanner($data['banner']);
            } else {
                unset($data['banner']);
            }

            $flashSale->update($data);

            // Xóa cache
            $this->clearFlashSaleCache();

            return $flashSale->fresh();
        });
    }

    /**
     * Xóa Flash Sale (soft delete)
     */
    public function delete(FlashSale $flashSale): bool
    {
        if ($flashSale->isActive()) {
            throw new \Exception('Không thể xóa Flash Sale đang chạy.');
        }

        return DB::transaction(function () use ($flashSale) {
            // Xóa banner
            if ($flashSale->banner) {
                $this->deleteBanner($flashSale->banner);
            }

            $result = $flashSale->delete();

            // Xóa cache
            $this->clearFlashSaleCache();

            return $result;
        });
    }

    /**
     * Nhân bản Flash Sale
     */
    public function duplicate(FlashSale $flashSale): FlashSale
    {
        return DB::transaction(function () use ($flashSale) {
            // Tạo bản sao Flash Sale
            $newFlashSale = $flashSale->replicate();
            $newFlashSale->title = $flashSale->title . ' (Copy)';
            $newFlashSale->status = 'draft';
            $newFlashSale->start_time = null;
            $newFlashSale->end_time = null;
            $newFlashSale->is_locked = false;
            $newFlashSale->is_active = true;
            $newFlashSale->created_by = Auth::id();
            $newFlashSale->views = 0;
            $newFlashSale->save();

            // Copy items
            foreach ($flashSale->items as $item) {
                $newItem = $item->replicate();
                $newItem->flash_sale_id = $newFlashSale->id;
                $newItem->sold = 0; // Reset sold
                $newItem->save();
            }

            // Xóa cache
            $this->clearFlashSaleCache();

            return $newFlashSale->fresh();
        });
    }

    /**
     * Xuất bản Flash Sale
     */
    public function publish(FlashSale $flashSale): FlashSale
    {
        // Validation
        if ($flashSale->activeItems()->count() === 0) {
            throw new \Exception('Flash Sale phải có ít nhất 1 sản phẩm active.');
        }

        if (!$flashSale->start_time || !$flashSale->end_time) {
            throw new \Exception('Phải thiết lập thời gian bắt đầu và kết thúc.');
        }

        if ($flashSale->start_time >= $flashSale->end_time) {
            throw new \Exception('Thời gian kết thúc phải sau thời gian bắt đầu.');
        }

        $flashSale->update([
            'status' => 'active',
        ]);

        // Auto lock nếu đang chạy
        $flashSale->autoLock();

        // Xóa cache
        $this->clearFlashSaleCache();

        return $flashSale->fresh();
    }

    /**
     * Toggle is_active
     */
    public function toggleActive(FlashSale $flashSale): FlashSale
    {
        $flashSale->update([
            'is_active' => !$flashSale->is_active,
        ]);

        // Xóa cache
        $this->clearFlashSaleCache();

        return $flashSale->fresh();
    }

    /**
     * Thêm sản phẩm vào Flash Sale
     */
    public function addProduct(FlashSale $flashSale, int $productId, array $data = []): FlashSaleItem
    {
        // Kiểm tra đã tồn tại chưa
        if ($flashSale->items()->where('product_id', $productId)->exists()) {
            throw new \Exception('Sản phẩm này đã có trong Flash Sale.');
        }

        $product = Product::findOrFail($productId);

        // Set giá mặc định
        $data['flash_sale_id'] = $flashSale->id;
        $data['product_id'] = $productId;
        $data['original_price'] = $data['original_price'] ?? $product->price;
        
        // Tính sale_price
        if (isset($data['sale_price']) && !empty($data['sale_price'])) {
            // Đã có sale_price từ input
        } elseif (isset($data['sale_price_percent']) && $data['sale_price_percent'] > 0) {
            // Tính từ % giảm giá
            $data['sale_price'] = $data['original_price'] * (1 - $data['sale_price_percent'] / 100);
        } else {
            // Mặc định giảm 20%
            $data['sale_price'] = $data['original_price'] * 0.8;
        }
        
        $data['stock'] = $data['stock'] ?? min($product->stock_quantity, 100);
        $data['sold'] = 0;
        $data['max_per_user'] = $data['max_per_user'] ?? 1;
        $data['is_active'] = $data['is_active'] ?? true;
        $maxSortOrder = $flashSale->items()->max('sort_order') ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? ($maxSortOrder + 1);

        // Validation
        $original = (float) ($data['original_price'] ?? 0);
        $sale = (float) ($data['sale_price'] ?? 0);

        if ($sale >= $original) {
            throw new \Exception('Giá Flash Sale phải nhỏ hơn giá gốc.');
        }

        if ($data['stock'] > $product->stock_quantity) {
            throw new \Exception('Số lượng Flash Sale không được vượt quá tồn kho thực.');
        }

        $item = FlashSaleItem::create($data);

        // Xóa cache
        $this->clearFlashSaleCache();

        return $item;
    }

    /**
     * Cập nhật Flash Sale Item
     */
    public function updateItem(FlashSaleItem $item, array $data): FlashSaleItem
    {
        // Nếu Flash Sale đang chạy, chỉ cho phép toggle is_active
        if ($item->flashSale->isActive()) {
            $allowedFields = ['is_active'];
            $filteredData = array_intersect_key($data, array_flip($allowedFields));
            
            if (empty($filteredData)) {
                throw new \Exception('Flash Sale đang chạy, chỉ có thể bật/tắt sản phẩm.');
            }
            
            $item->update($filteredData);
            return $item->fresh();
        }

        // Log thay đổi giá nếu có
        if (isset($data['sale_price']) && $data['sale_price'] != $item->sale_price) {
            $this->logPriceChange($item, $item->sale_price, $data['sale_price'], $data['reason'] ?? null);
        }

        $item->update($data);

        // Xóa cache
        $this->clearFlashSaleCache();

        return $item->fresh();
    }

    /**
     * Xóa Flash Sale Item
     */
    public function deleteItem(FlashSaleItem $item): bool
    {
        if ($item->flashSale->isActive()) {
            throw new \Exception('Không thể xóa item khi Flash Sale đang chạy.');
        }

        $result = $item->delete();

        // Xóa cache
        $this->clearFlashSaleCache();

        return $result;
    }

    /**
     * Xóa toàn bộ sản phẩm trong Flash Sale
     */
    public function deleteAllItems(FlashSale $flashSale): int
    {
        if ($flashSale->isActive()) {
            throw new \Exception('Không thể xóa toàn bộ sản phẩm khi Flash Sale đang chạy.');
        }

        $count = $flashSale->items()->count();
        $flashSale->items()->delete();

        // Xóa cache
        $this->clearFlashSaleCache();

        return $count;
    }

    /**
     * Thêm nhiều sản phẩm cùng lúc
     */
    public function addProducts(FlashSale $flashSale, array $productIds, array $defaultData = []): array
    {
        $added = [];
        $errors = [];

        foreach ($productIds as $productId) {
            try {
                $item = $this->addProduct($flashSale, $productId, $defaultData);
                $added[] = $item;
            } catch (\Exception $e) {
                $errors[] = [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Xóa cache sau khi thêm xong (mỗi addProduct đã xóa cache, nhưng để chắc chắn xóa lại 1 lần nữa)
        if (!empty($added)) {
            $this->clearFlashSaleCache();
        }

        return [
            'added' => $added,
            'errors' => $errors,
        ];
    }

    /**
     * Thêm sản phẩm tự động từ danh mục
     * Lấy 20 sản phẩm nổi bật từ mỗi danh mục
     */
    public function addProductsByCategories(FlashSale $flashSale, array $categoryIds, array $defaultData = []): array
    {
        // Kiểm tra mode
        if ($flashSale->product_add_mode !== 'auto_by_category') {
            throw new \Exception('Flash Sale này không hỗ trợ thêm sản phẩm tự động theo danh mục.');
        }

        $added = [];
        $errors = [];

        foreach ($categoryIds as $categoryId) {
            try {
                // Lấy 20 sản phẩm nổi bật từ danh mục
                $products = Product::where('is_active', true)
                    ->where('stock_quantity', '>', 0)
                    ->where(function ($query) use ($categoryId) {
                        $query->where('primary_category_id', $categoryId)
                            ->orWhereJsonContains('category_ids', $categoryId);
                    })
                    ->where('is_featured', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get();

                if ($products->isEmpty()) {
                    $errors[$categoryId] = 'Không tìm thấy sản phẩm nổi bật trong danh mục này.';
                    continue;
                }

                // Thêm từng sản phẩm
                foreach ($products as $product) {
                    try {
                        // Kiểm tra đã tồn tại chưa
                        if ($flashSale->items()->where('product_id', $product->id)->exists()) {
                            continue; // Bỏ qua nếu đã có
                        }

                        // Set original_price từ product price
                        $productData = array_merge($defaultData, [
                            'original_price' => $product->price,
                        ]);

                        $item = $this->addProduct($flashSale, $product->id, $productData);
                        $added[] = $item;
                    } catch (\Exception $e) {
                        $errors[$product->id] = $e->getMessage();
                    }
                }
            } catch (\Exception $e) {
                $errors[$categoryId] = $e->getMessage();
            }
        }

        // Xóa cache sau khi thêm xong
        if (!empty($added)) {
            $this->clearFlashSaleCache();
        }

        return [
            'added' => $added,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk action items (activate/deactivate/delete)
     */
    public function bulkActionItems(FlashSale $flashSale, array $itemIds, string $action): array
    {
        $items = FlashSaleItem::where('flash_sale_id', $flashSale->id)
            ->whereIn('id', $itemIds)
            ->get();

        if ($items->isEmpty()) {
            throw new \Exception('Không tìm thấy sản phẩm hợp lệ.');
        }

        if ($action === 'delete' && $flashSale->isActive()) {
            throw new \Exception('Flash Sale đang chạy, không thể xóa sản phẩm.');
        }

        $affected = 0;

        DB::transaction(function () use (&$affected, $items, $action) {
            if ($action === 'activate' || $action === 'deactivate') {
                $status = $action === 'activate';
                $affected = FlashSaleItem::whereIn('id', $items->pluck('id'))
                    ->update(['is_active' => $status]);
            } elseif ($action === 'delete') {
                $affected = FlashSaleItem::whereIn('id', $items->pluck('id'))->delete();
            }
        });

        if ($affected > 0) {
            $this->clearFlashSaleCache();
        }

        $messages = [
            'activate' => "Đã bật {$affected} sản phẩm.",
            'deactivate' => "Đã tắt {$affected} sản phẩm.",
            'delete' => "Đã xóa {$affected} sản phẩm.",
        ];

        return [
            'affected' => $affected,
            'message' => $messages[$action] ?? 'Đã thực hiện xong.',
        ];
    }

    /**
     * Import sản phẩm từ file Excel/CSV
     */
    public function importItemsFromExcel(FlashSale $flashSale, UploadedFile $file): array
    {
        if ($flashSale->isActive()) {
            throw new \Exception('Flash Sale đang chạy, không thể import sản phẩm.');
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) <= 1) {
            throw new \Exception('File không chứa dữ liệu.');
        }

        $headerRow = array_shift($rows);
        $columnMap = $this->buildImportColumnMap($headerRow);

        if (!isset($columnMap['sku'])) {
            throw new \Exception('File phải chứa cột "SKU".');
        }

        $success = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 vì mảng bắt đầu từ 0 và đã bỏ header

            // Bỏ qua nếu toàn dòng trống
            $hasValue = collect($row)->contains(function ($value) {
                return trim((string) $value) !== '';
            });
            if (!$hasValue) {
                continue;
            }

            $sku = trim((string) ($this->getImportCellValue($row, $columnMap, 'sku') ?? ''));
            if ($sku === '') {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => 'Thiếu SKU.',
                ];
                continue;
            }

            $product = Product::where('sku', $sku)->first();
            if (!$product) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Không tìm thấy sản phẩm với SKU {$sku}.",
                ];
                continue;
            }

            if ($flashSale->items()->where('product_id', $product->id)->exists()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Sản phẩm SKU {$sku} đã có trong Flash Sale.",
                ];
                continue;
            }

            $originalPrice = $this->toFloat($this->getImportCellValue($row, $columnMap, 'original_price')) ?? (float) ($product->price ?? 0);
            if ($originalPrice <= 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Giá gốc không hợp lệ cho SKU {$sku}.",
                ];
                continue;
            }

            $salePrice = $this->toFloat($this->getImportCellValue($row, $columnMap, 'sale_price')) ?? ($originalPrice * 0.8);
            if ($salePrice <= 0 || $salePrice >= $originalPrice) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Giá Flash Sale phải nhỏ hơn giá gốc cho SKU {$sku}.",
                ];
                continue;
            }

            $stock = (int) ($this->toFloat($this->getImportCellValue($row, $columnMap, 'stock')) ?? min($product->stock_quantity, 100));
            if ($stock <= 0) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Số lượng phải lớn hơn 0 cho SKU {$sku}.",
                ];
                continue;
            }
            if ($stock > $product->stock_quantity) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => "Số lượng Flash Sale ({$stock}) vượt tồn kho thực ({$product->stock_quantity}) cho SKU {$sku}.",
                ];
                continue;
            }

            $maxPerUser = (int) ($this->toFloat($this->getImportCellValue($row, $columnMap, 'max_per_user')) ?? 1);
            $isActive = $this->parseBooleanValue($this->getImportCellValue($row, $columnMap, 'is_active'));

            try {
                $this->addProduct($flashSale, $product->id, [
                    'original_price' => $originalPrice,
                    'sale_price' => $salePrice,
                    'stock' => $stock,
                    'max_per_user' => $maxPerUser > 0 ? $maxPerUser : 1,
                    'is_active' => $isActive,
                ]);
                $success++;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($success > 0) {
            $this->clearFlashSaleCache();
        }

        return [
            'success' => $success,
            'errors' => $errors,
        ];
    }

    /**
     * Chuẩn hóa header và map về key chuẩn
     */
    protected function buildImportColumnMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $columnKey => $headerTitle) {
            $normalized = $this->normalizeImportHeader($headerTitle);
            if ($normalized && !isset($map[$normalized])) {
                $map[$normalized] = $columnKey;
            }
        }

        return $map;
    }

    protected function normalizeImportHeader(?string $header): ?string
    {
        if ($header === null) {
            return null;
        }

        $header = trim($header);
        if ($header === '') {
            return null;
        }

        $normalized = Str::slug($header, '_');

        $aliases = [
            'sku' => 'sku',
            'ma_sku' => 'sku',
            'ma_san_pham' => 'sku',
            'original_price' => 'original_price',
            'gia_goc' => 'original_price',
            'sale_price' => 'sale_price',
            'gia_flash_sale' => 'sale_price',
            'stock' => 'stock',
            'so_luong' => 'stock',
            'max_per_user' => 'max_per_user',
            'gioi_han_mua' => 'max_per_user',
            'is_active' => 'is_active',
            'trang_thai' => 'is_active',
        ];

        return $aliases[$normalized] ?? $normalized;
    }

    protected function getImportCellValue(array $row, array $map, string $key)
    {
        if (!isset($map[$key])) {
            return null;
        }

        $columnKey = $map[$key];
        return $row[$columnKey] ?? null;
    }

    protected function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = str_replace([',', ' '], ['', ''], (string) $value);
        if (!is_numeric($numeric)) {
            return null;
        }

        return (float) $numeric;
    }

    protected function parseBooleanValue($value): bool
    {
        if ($value === null) {
            return true;
        }

        $value = Str::lower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'x', 'active', 'bật', 'co', 'có'], true);
    }

    /**
     * Upload banner
     */
    private function uploadBanner($file): string
    {
        // Tạo folder nếu chưa tồn tại
        $folderPath = public_path('admins/img/banners/flash-sale');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
        
        // Tạo tên file unique
        $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // Di chuyển file vào folder
        $file->move($folderPath, $fileName);
        @chmod($folderPath . DIRECTORY_SEPARATOR . $fileName, 0644);
        
        // Trả về đường dẫn relative để lưu vào DB
        return 'admins/img/banners/flash-sale/' . $fileName;
    }

    /**
     * Xóa banner
     */
    private function deleteBanner(string $path): bool
    {
        // Xóa file từ public folder
        $filePath = public_path($path);
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Log thay đổi giá
     */
    private function logPriceChange(FlashSaleItem $item, float $oldPrice, float $newPrice, ?string $reason = null): void
    {
        FlashSalePriceLog::create([
            'flash_sale_item_id' => $item->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'changed_by' => Auth::id(),
            'changed_at' => now(),
            'reason' => $reason,
        ]);
    }

    /**
     * Xóa cache flash sale data
     */
    private function clearFlashSaleCache(): void
    {
        Cache::forget('flash_sale_data');
    }
}

