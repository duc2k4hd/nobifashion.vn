<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductSlugRedirect;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->extractProductPayload($data);
            $payload['category_ids'] = $this->resolveCategoryIds($data);

            $product = Product::create($payload);
            
            // Sync tags sau khi tạo product (cần product->id)
            $this->syncTags($product, Arr::get($data, 'tag_ids', []), Arr::get($data, 'tag_names'));

            $this->syncImages($product, Arr::get($data, 'images', []));
            $this->syncVariants($product, Arr::get($data, 'variants', []));
            $this->refreshHasVariants($product);
            $this->syncFaqs($product, Arr::get($data, 'faqs', []));
            $this->syncHowTos($product, Arr::get($data, 'how_tos', []));

            return $product;
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $oldSlug = $product->slug;
            $payload = $this->extractProductPayload($data);
            $payload['category_ids'] = $this->resolveCategoryIds($data);
            $newSlug = $payload['slug'];

            // Nếu slug thay đổi, lưu redirect từ slug cũ sang slug mới
            if ($oldSlug !== $newSlug && !empty($oldSlug)) {
                // Kiểm tra xem redirect đã tồn tại chưa
                $existingRedirect = ProductSlugRedirect::where('old_slug', $oldSlug)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$existingRedirect) {
                    ProductSlugRedirect::create([
                        'product_id' => $product->id,
                        'old_slug' => $oldSlug,
                        'new_slug' => $newSlug,
                    ]);
                } else {
                    // Cập nhật redirect nếu đã tồn tại
                    $existingRedirect->update(['new_slug' => $newSlug]);
                }
            }

            $product->update($payload);
            
            // Sync tags
            $this->syncTags($product, Arr::get($data, 'tag_ids', []), Arr::get($data, 'tag_names'));

            $this->syncImages($product, Arr::get($data, 'images', []));
            $this->syncVariants($product, Arr::get($data, 'variants', []));
            $this->refreshHasVariants($product);
            $this->syncFaqs($product, Arr::get($data, 'faqs', []));
            $this->syncHowTos($product, Arr::get($data, 'how_tos', []));
            return $product;
        });
    }

    public function delete(Product $product): void
    {
        // Xóa mềm: chỉ chuyển sản phẩm sang trạng thái tạm ẩn
        $product->update(['is_active' => false]);
    }

    private function extractProductPayload(array $data): array
    {
        $slug = Arr::get($data, 'slug');
        if (empty($slug)) {
            $slug = Str::slug($data['name'] ?? Str::random(6));
        }

        $domain_name = Setting::where('key', 'site_url')->first();
        $domain_name = $domain_name->value;

        return [
            'sku' => Arr::get($data, 'sku'),
            'name' => Arr::get($data, 'name'),
            'slug' => $slug,
            'description' => Arr::get($data, 'description'),
            'short_description' => Arr::get($data, 'short_description'),
            'price' => Arr::get($data, 'price', 0),
            'sale_price' => Arr::get($data, 'sale_price'),
            'cost_price' => Arr::get($data, 'cost_price'),
            'stock_quantity' => Arr::get($data, 'stock_quantity', 0),
            'meta_title' => Arr::get($data, 'meta_title'),
            'meta_description' => Arr::get($data, 'meta_description'),
            'meta_keywords' => Arr::get($data, 'meta_keywords'),
            'meta_canonical' => Arr::get($data, 'meta_canonical') ?? $domain_name . '/san-pham/' . $slug,
            'primary_category_id' => Arr::get($data, 'primary_category_id'),
            'is_featured' => Arr::get($data, 'is_featured', false),
            'has_variants' => false,
            'created_by' => Arr::get($data, 'created_by', Auth::id()),
            'is_active' => Arr::get($data, 'is_active', true),
        ];
    }

    private function resolveCategoryIds(array $data): ?array
    {
        $primary = Arr::get($data, 'primary_category_id');
        $extra = Arr::get($data, 'category_ids', []);

        $ids = array_filter(array_unique(array_merge(
            $extra,
            $primary ? [$primary] : []
        )));

        return !empty($ids) ? $ids : null;
    }

    /**
     * Sync tags cho product vào tags table với entity_type = 'App\Models\Product'
     * Mỗi tag sẽ được tạo với entity_id = product->id và entity_type = Product::class
     * 
     * @param Product $product
     * @param array $tagIds Tag IDs từ dropdown
     * @param string|null $tagNames Tag names từ input (phân cách bằng dấu phẩy)
     */
    private function syncTags(Product $product, array $tagIds, ?string $tagNames = null): void
    {
        // Xóa tất cả tags cũ của product này
        Tag::where('entity_type', Product::class)
            ->where('entity_id', $product->id)
            ->delete();

        // Xử lý tag names từ input (tags mới)
        $allTagNames = [];
        if (!empty($tagNames)) {
            $newTagNames = $this->parseTagNames($tagNames);
            $allTagNames = array_merge($allTagNames, $newTagNames);
        }

        // Nếu không có tagIds và không có tagNames, xóa hết tags
        if (empty($tagIds) && empty($allTagNames)) {
            $product->tag_ids = [];
            $product->saveQuietly();
            return;
        }

        // Lấy thông tin tags từ products (entity_type = Product::class)
        // Chỉ lấy tags của products, không lấy tags của posts
        $existingTags = [];
        if (!empty($tagIds)) {
            $existingTags = Tag::whereIn('id', $tagIds)
                ->where('entity_type', Product::class) // Chỉ lấy tags của products
                ->select('id', 'name', 'slug', 'description', 'is_active')
                ->get()
                ->unique('name') // Lấy unique theo name để tránh duplicate
                ->keyBy('id');
            
            // Lấy thêm tag names từ existing tags
            foreach ($existingTags as $tag) {
                $allTagNames[] = $tag->name;
            }
        }

        // Loại bỏ duplicate và tạo tags
        $allTagNames = array_unique(array_map('trim', $allTagNames));
        $createdTagIds = [];
        
        foreach ($allTagNames as $tagName) {
            if (empty($tagName)) {
                continue;
            }
            
            // Kiểm tra xem tag đã có với entity_id = product->id chưa
            $existingProductTag = Tag::where('entity_type', Product::class)
                ->where('entity_id', $product->id)
                ->where('name', $tagName)
                ->first();
            
            if ($existingProductTag) {
                // Nếu đã tồn tại, dùng tag đó
                $createdTagIds[] = $existingProductTag->id;
                continue;
            }
            
            // Tìm tag template (có thể từ products khác hoặc mới tạo)
            $templateTag = Tag::where('entity_type', Product::class)
                ->where('name', $tagName)
                ->first();
            
            // Tạo tag mới với entity_type và entity_id cho product này
            $baseSlug = Str::slug($tagName);
            $uniqueSlug = $baseSlug . '-product-' . $product->id;
            
            // Đảm bảo slug unique
            $counter = 1;
            while (Tag::where('slug', $uniqueSlug)->exists()) {
                $uniqueSlug = $baseSlug . '-product-' . $product->id . '-' . $counter;
                $counter++;
            }

            $newTag = Tag::create([
                'name' => $tagName,
                'slug' => $uniqueSlug,
                'description' => $templateTag->description ?? null,
                'is_active' => $templateTag->is_active ?? true,
                'usage_count' => 0, // Reset usage count cho tag mới
                'entity_id' => $product->id,
                'entity_type' => Product::class,
            ]);
            $createdTagIds[] = $newTag->id;
        }

        // Cập nhật lại tag_ids trong products table để backward compatibility
        // Lưu IDs của tags vừa tạo
        $product->tag_ids = $createdTagIds;
        $product->saveQuietly(); // Save without triggering events
    }

    /**
     * Parse tag names từ string (phân cách bằng dấu phẩy)
     */
    private function parseTagNames(string $tagNames): array
    {
        return array_filter(
            array_map('trim', explode(',', $tagNames)),
            fn($name) => !empty($name)
        );
    }

    private function syncImages(Product $product, array $images): void
    {
        $keepIds = [];
        $hasPrimary = false;
        foreach ($images as $order => $imageData) {
            $imageId = Arr::get($imageData, 'id');
            $file = Arr::get($imageData, 'file');
            $path = Arr::get($imageData, 'existing_path', Arr::get($imageData, 'path'));
            $filename = $path ? basename($path) : null;
            if ($file instanceof UploadedFile) {
                $filename = $this->storeImageFile($file);
            }

            $payload = [
                'product_id' => $product->id,
                'title' => Arr::get($imageData, 'title'),
                'notes' => Arr::get($imageData, 'notes'),
                'alt' => Arr::get($imageData, 'alt'),
                'is_primary' => Arr::get($imageData, 'is_primary', false),
                'order' => Arr::get($imageData, 'order', $order),
            ];

            if ($filename) {
                $payload['url'] = $filename;
                $payload['thumbnail_url'] = $filename;
                $payload['medium_url'] = $filename;
            }

            if ($imageId) {
                $image = Image::where('product_id', $product->id)->where('id', $imageId)->first();
                if ($image) {
                    if ($filename && $image->url !== $filename) {
                        $this->deleteImageFile($image->url);
                    }
                    $image->update($payload);
                    $keepIds[] = $image->id;
                    if ($payload['is_primary']) {
                        $hasPrimary = true;
                    }
                    continue;
                }
            }

            if (!empty($payload['url'])) {
                $image = Image::create($payload);
                $keepIds[] = $image->id;
                if ($payload['is_primary']) {
                    $hasPrimary = true;
                }
            }
        }

        if (!$hasPrimary && !empty($keepIds)) {
            Image::whereIn('id', $keepIds)
                ->orderBy('order')
                ->limit(1)
                ->update(['is_primary' => true]);
        }

        if (!empty($keepIds)) {
            $obsolete = Image::where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->get();
            foreach ($obsolete as $img) {
                $this->deleteImageFile($img->url);
                $img->delete();
            }
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $existing = ProductVariant::where('product_id', $product->id)
            ->orderBy('id')
            ->get();

        $keptIds = [];

        foreach ($variants as $index => $variantData) {
            $attributes = $this->normalizeVariantAttributes(Arr::get($variantData, 'attributes', []));
            $payload = [
                'product_id' => $product->id,
                'price' => Arr::get($variantData, 'price'),
                'stock_quantity' => Arr::get($variantData, 'stock_quantity', 0),
                'attributes' => $attributes,
                'image_id' => Arr::get($variantData, 'image_id'),
                'status' => Arr::get($variantData, 'status', 'active'),
            ];

            if (isset($existing[$index])) {
                $variant = $existing[$index];
                $variant->update($payload);
                $keptIds[] = $variant->id;
            } else {
                $insertPayload = $payload;
                $insertPayload['attributes'] = !empty($attributes) ? json_encode($attributes) : null;
                $newId = DB::table('product_variants')->insertGetId(array_merge($insertPayload, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $keptIds[] = $newId;
            }
        }

        if ($existing->isNotEmpty()) {
            $toDelete = $existing->pluck('id')->diff($keptIds)->all();
            if (!empty($toDelete)) {
                ProductVariant::whereIn('id', $toDelete)->delete();
            }
        }
    }

    private function refreshHasVariants(Product $product): void
    {
        $hasVariants = ProductVariant::where('product_id', $product->id)->exists();

        if ((bool) $product->has_variants !== $hasVariants) {
            $product->forceFill([
                'has_variants' => $hasVariants,
            ])->save();
        } else {
            $product->setAttribute('has_variants', $hasVariants);
        }
    }

    private function syncFaqs(Product $product, array $faqs): void
    {
        $keepIds = [];
        foreach ($faqs as $faq) {
            $faqId = Arr::get($faq, 'id');
            $payload = [
                'product_id' => $product->id,
                'question' => Arr::get($faq, 'question'),
                'answer' => Arr::get($faq, 'answer'),
                'order' => Arr::get($faq, 'order', 0),
                'updated_at' => now(),
            ];

            if ($faqId && ProductFaq::where('product_id', $product->id)->where('id', $faqId)->exists()) {
                DB::table('product_faqs')->where('id', $faqId)->update($payload);
                $keepIds[] = $faqId;
            } else {
                $newId = DB::table('product_faqs')->insertGetId(array_merge($payload, [
                    'created_at' => now(),
                ]));
                $keepIds[] = $newId;
            }
        }

        if (!empty($keepIds)) {
            DB::table('product_faqs')
                ->where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    private function syncHowTos(Product $product, array $howTos): void
    {
        $keepIds = [];
        foreach ($howTos as $howTo) {
            $howToId = Arr::get($howTo, 'id');
            $payload = [
                'product_id' => $product->id,
                'title' => Arr::get($howTo, 'title'),
                'description' => Arr::get($howTo, 'description'),
                'steps' => $this->normalizeArrayField(Arr::get($howTo, 'steps')),
                'supplies' => $this->normalizeArrayField(Arr::get($howTo, 'supplies')),
                'is_active' => Arr::get($howTo, 'is_active', true),
                'updated_at' => now(),
            ];

            if ($howToId && ProductHowTo::where('product_id', $product->id)->where('id', $howToId)->exists()) {
                DB::table('product_how_tos')->where('id', $howToId)->update($payload);
                $keepIds[] = $howToId;
            } else {
                $newId = DB::table('product_how_tos')->insertGetId(array_merge($payload, [
                    'created_at' => now(),
                ]));
                $keepIds[] = $newId;
            }
        }

        if (!empty($keepIds)) {
            DB::table('product_how_tos')
                ->where('product_id', $product->id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }
    }

    private function normalizeArrayField($value): ?array
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            return array_filter(array_map('trim', explode("\n", $value)));
        }

        if (is_array($value)) {
            return array_values(array_filter($value, function ($item) {
                return !empty($item);
            }));
        }

        return null;
    }

    private function normalizeVariantAttributes($attributes): ?array
    {
        if (is_string($attributes)) {
            $decoded = json_decode($attributes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attributes = $decoded;
            }
        }

        if (!is_array($attributes)) {
            return null;
        }

        if (array_is_list($attributes) && isset($attributes[0]) && is_array($attributes[0]) && array_key_exists('key', $attributes[0])) {
            $converted = [];
            foreach ($attributes as $pair) {
                $key = trim((string)($pair['key'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $converted[$key] = trim((string)($pair['value'] ?? ''));
            }
            $attributes = $converted;
        }

        $clean = [];
        foreach ($attributes as $key => $value) {
            $attrKey = is_string($key) ? trim($key) : $key;
            if ($attrKey === '' || $value === null || $value === '') {
                continue;
            }
            $clean[$attrKey] = is_string($value) ? trim($value) : $value;
        }

        return !empty($clean) ? $clean : null;
    }

    private function storeImageFile(UploadedFile $file): string
    {
        $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
        $destination = public_path('clients/assets/img/clothes');

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        return $filename;
    }

    private function deleteImageFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = public_path('clients/assets/img/clothes/' . $filename);
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

