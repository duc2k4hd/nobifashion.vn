<?php

namespace App\Services\Admin;

use App\Models\Affiliate;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\FlashSaleItem;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductHowTo;
use App\Models\ProductSlugRedirect;
use App\Models\ProductVariant;
use App\Models\Tag;
use App\Services\Media\FileHelperService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProductService
{
    protected ?array $imageTableColumns = null;

    public function __construct(
        protected FileHelperService $files
    ) {
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $payload = $this->extractProductPayload($data);
            $payload['category_ids'] = $this->resolveCategoryIds($data);

            $product = Product::create($payload);

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

            if ($oldSlug !== $newSlug && !empty($oldSlug)) {
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
                    $existingRedirect->update(['new_slug' => $newSlug]);
                }
            }

            $product->update($payload);

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
        $product->forceFill([
            'locked_by' => null,
            'locked_at' => null,
        ])->saveQuietly();

        $product->delete();
    }

    public function restore(int $id): void
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();
        $product->forceFill([
            'locked_by' => null,
            'locked_at' => null,
        ])->saveQuietly();
    }

    public function forceDelete(int $id): void
    {
        $pathsToDelete = DB::transaction(function () use ($id) {
            $product = Product::withTrashed()->findOrFail($id);
            $imageRecords = $this->getProductImageRecords($product);
            $pathsToDelete = $this->collectManagedProductImagePaths($imageRecords);

            $this->deleteProductRelations($product, $imageRecords);
            $product->forceDelete();

            return $pathsToDelete;
        });

        foreach ($pathsToDelete as $path) {
            $this->deleteManagedProductFileIfUnused($path);
        }
    }

    private function extractProductPayload(array $data): array
    {
        $slug = Arr::get($data, 'slug');
        if (empty($slug)) {
            $slug = Str::slug($data['name'] ?? Str::random(6));
        }

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
            'brand_id' => Arr::get($data, 'brand_id'),
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

    private function syncTags(Product $product, array $tagIds, ?string $tagNames = null): void
    {
        Tag::where('entity_type', Product::class)
            ->where('entity_id', $product->id)
            ->delete();

        $allTagNames = [];
        if (!empty($tagNames)) {
            $newTagNames = $this->parseTagNames($tagNames);
            $allTagNames = array_merge($allTagNames, $newTagNames);
        }

        if (empty($tagIds) && empty($allTagNames)) {
            $product->tag_ids = [];
            $product->saveQuietly();

            return;
        }

        $existingTags = [];
        if (!empty($tagIds)) {
            $existingTags = Tag::whereIn('id', $tagIds)
                ->where('entity_type', Product::class)
                ->select('id', 'name', 'slug', 'description', 'is_active')
                ->get()
                ->unique('name')
                ->keyBy('id');

            foreach ($existingTags as $tag) {
                $allTagNames[] = $tag->name;
            }
        }

        $allTagNames = array_unique(array_map('trim', $allTagNames));
        $createdTagIds = [];

        foreach ($allTagNames as $tagName) {
            if (empty($tagName)) {
                continue;
            }

            $existingProductTag = Tag::where('entity_type', Product::class)
                ->where('entity_id', $product->id)
                ->where('name', $tagName)
                ->first();

            if ($existingProductTag) {
                $createdTagIds[] = $existingProductTag->id;
                continue;
            }

            $templateTag = Tag::where('entity_type', Product::class)
                ->where('name', $tagName)
                ->first();

            $baseSlug = Str::slug($tagName);
            $uniqueSlug = $baseSlug . '-product-' . $product->id;

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
                'usage_count' => 0,
                'entity_id' => $product->id,
                'entity_type' => Product::class,
            ]);
            $createdTagIds[] = $newTag->id;
        }

        $product->tag_ids = $createdTagIds;
        $product->saveQuietly();
    }

    private function parseTagNames(string $tagNames): array
    {
        return array_filter(
            array_map('trim', explode(',', $tagNames)),
            fn ($name) => !empty($name)
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
            $existingImage = null;

            if ($imageId) {
                $existingImage = Image::where('product_id', $product->id)
                    ->where('id', $imageId)
                    ->first();
            }

            $relativeUrl = $this->resolveSelectedImageForProduct($path);

            if ($file instanceof UploadedFile) {
                $relativeUrl = $this->storeImageFile($file);
            }

            if (!$relativeUrl && $existingImage) {
                $relativeUrl = $this->getPersistedProductImageValue($existingImage);
            }

            if (!$relativeUrl) {
                continue;
            }

            $payload = [
                'product_id' => $product->id,
                'title' => Arr::get($imageData, 'title'),
                'notes' => Arr::get($imageData, 'notes'),
                'alt' => Arr::get($imageData, 'alt'),
                'is_primary' => Arr::get($imageData, 'is_primary', false),
                'order' => Arr::get($imageData, 'order', $order),
                'path' => $relativeUrl,
                'url' => $relativeUrl,
            ];

            if ($relativeUrl) {
                $payload['thumbnail_url'] = $relativeUrl;
                $payload['medium_url'] = $relativeUrl;
            }

            $payload = $this->appendOptionalImageColumns($payload, [
                'name' => basename($relativeUrl),
                'entity_type' => 'product',
                'entity_id' => $product->id,
                'role' => $payload['is_primary'] ? 'primary' : 'gallery',
                'context' => 'product',
            ]);

            if ($existingImage) {
                if (!$this->isSameManagedProductImage($existingImage, $relativeUrl)) {
                    $this->deleteImageAssetsIfUnused($existingImage, [$existingImage->id]);
                }

                $existingImage->update($payload);
                $keepIds[] = $existingImage->id;

                if ($payload['is_primary']) {
                    $hasPrimary = true;
                }

                continue;
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
            $firstImage = Image::whereIn('id', $keepIds)
                ->orderBy('order')
                ->orderBy('id')
                ->first();

            if ($firstImage) {
                $firstImage->update($this->appendOptionalImageColumns([
                    'is_primary' => true,
                ], [
                    'role' => 'primary',
                ]));

                if ($this->hasImageColumn('role')) {
                    Image::whereIn('id', $keepIds)
                        ->where('id', '!=', $firstImage->id)
                        ->update(['role' => 'gallery']);
                }
            }
        }

        $obsoleteQuery = Image::where('product_id', $product->id);
        if (!empty($keepIds)) {
            $obsoleteQuery->whereNotIn('id', $keepIds);
        }

        $obsolete = $obsoleteQuery->get();
        foreach ($obsolete as $img) {
            $this->deleteImageAssetsIfUnused($img, [$img->id]);
            $img->delete();
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
                $key = trim((string) ($pair['key'] ?? ''));
                if ($key === '') {
                    continue;
                }

                $converted[$key] = trim((string) ($pair['value'] ?? ''));
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
        $destination = public_path($this->getProductImageDirectory());

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);
        @chmod($destination . DIRECTORY_SEPARATOR . $filename, 0644);

        return $filename;
    }

    private function resolveSelectedImageForProduct(?string $path): ?string
    {
        $normalizedPath = $this->files->normalizeRelativePath($path);
        if (!$normalizedPath || Str::startsWith($normalizedPath, ['http://', 'https://'])) {
            return null;
        }

        $productImageDirectory = $this->getProductImageDirectory();

        if ($normalizedPath === basename($normalizedPath)) {
            return basename($normalizedPath);
        }

        if ($normalizedPath === $productImageDirectory || Str::startsWith($normalizedPath, $productImageDirectory . '/')) {
            return basename($normalizedPath);
        }

        $absolutePath = $this->files->toAbsolutePath($normalizedPath);
        if (!$absolutePath || !is_file($absolutePath)) {
            return null;
        }

        return $this->copyExistingImageIntoProductDirectory($absolutePath);
    }

    private function getPersistedProductImageValue(Image $image): ?string
    {
        $normalizedPath = $this->normalizeManagedProductImagePath($image->path ?: $image->url);
        if (!$normalizedPath) {
            return null;
        }

        return basename($normalizedPath);
    }

    private function copyExistingImageIntoProductDirectory(string $sourceAbsolutePath): ?string
    {
        $extension = strtolower(pathinfo($sourceAbsolutePath, PATHINFO_EXTENSION) ?: 'webp');
        $baseName = Str::slug(pathinfo($sourceAbsolutePath, PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? $baseName : 'product-image';

        $destinationDirectory = public_path($this->getProductImageDirectory());
        $this->files->ensureDirectory($destinationDirectory);

        $filename = $this->buildUniqueProductImageFilename($destinationDirectory, $baseName, $extension);
        $destinationPath = $destinationDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!@copy($sourceAbsolutePath, $destinationPath)) {
            return null;
        }

        @chmod($destinationPath, 0644);

        return $filename;
    }

    private function buildUniqueProductImageFilename(string $destinationDirectory, string $baseName, string $extension): string
    {
        $counter = 0;

        do {
            $suffix = $counter === 0
                ? '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(4))
                : '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(4)) . '-' . $counter;

            $filename = $baseName . $suffix . '.' . $extension;
            $counter++;
        } while (is_file($destinationDirectory . DIRECTORY_SEPARATOR . $filename));

        return $filename;
    }

    private function appendOptionalImageColumns(array $payload, array $optionalValues): array
    {
        foreach ($optionalValues as $column => $value) {
            if ($this->hasImageColumn($column)) {
                $payload[$column] = $value;
            }
        }

        return $payload;
    }

    private function deleteImageFile(?string $filename): void
    {
        $normalizedPath = $this->normalizeManagedProductImagePath($filename);
        if (!$normalizedPath) {
            return;
        }

        $this->files->deleteManagedFile($normalizedPath, [$this->getProductImageDirectory()]);
    }

    private function deleteProductRelations(Product $product, EloquentCollection $imageRecords): void
    {
        Favorite::where('product_id', $product->id)->delete();
        Affiliate::where('product_id', $product->id)->delete();
        ProductSlugRedirect::where('product_id', $product->id)->delete();

        Comment::withTrashed()
            ->where('commentable_id', $product->id)
            ->whereIn('commentable_type', [Product::class, 'product'])
            ->forceDelete();

        Tag::withTrashed()
            ->where('entity_id', $product->id)
            ->whereIn('entity_type', [Product::class, 'product'])
            ->forceDelete();

        ProductFaq::where('product_id', $product->id)->delete();
        ProductHowTo::where('product_id', $product->id)->delete();
        FlashSaleItem::where('product_id', $product->id)->delete();
        ProductVariant::where('product_id', $product->id)->delete();

        if ($imageRecords->isNotEmpty()) {
            Image::whereIn('id', $imageRecords->modelKeys())->delete();
        }
    }

    private function getProductImageRecords(Product $product): EloquentCollection
    {
        return $this->newProductImageScope($product)
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values();
    }

    private function deleteImageAssetsIfUnused(Image $image, array $ignoreImageIds = []): void
    {
        foreach ($this->collectManagedProductImagePaths([$image]) as $path) {
            $this->deleteManagedProductFileIfUnused($path, $ignoreImageIds);
        }
    }

    private function collectManagedProductImagePaths(iterable $images): array
    {
        $paths = [];

        foreach ($images as $image) {
            foreach ([$image->path, $image->url, $image->thumbnail_url, $image->medium_url] as $candidate) {
                $normalizedPath = $this->normalizeManagedProductImagePath($candidate);
                if ($normalizedPath) {
                    $paths[$normalizedPath] = true;
                }
            }
        }

        return array_keys($paths);
    }

    private function deleteManagedProductFileIfUnused(string $path, array $ignoreImageIds = []): void
    {
        if ($this->isManagedProductFileReferenced($path, $ignoreImageIds)) {
            return;
        }

        $this->deleteImageFile($path);
    }

    private function isManagedProductFileReferenced(string $path, array $ignoreImageIds = []): bool
    {
        $pathVariants = array_values(array_unique(array_filter([
            $this->files->normalizeRelativePath($path),
            basename($path),
        ])));

        if (empty($pathVariants)) {
            return false;
        }

        $query = Image::query()
            ->when(!empty($ignoreImageIds), fn ($builder) => $builder->whereNotIn('id', $ignoreImageIds));

        $pathColumns = $this->getExistingImagePathColumns();
        if (empty($pathColumns)) {
            return false;
        }

        return $query->where(function ($builder) use ($pathVariants, $pathColumns) {
            $firstColumn = array_shift($pathColumns);
            $builder->whereIn($firstColumn, $pathVariants);

            foreach ($pathColumns as $column) {
                $builder->orWhereIn($column, $pathVariants);
            }
        })->exists();
    }

    private function isSameManagedProductImage(Image $image, string $relativeUrl): bool
    {
        $currentPath = $this->normalizeManagedProductImagePath($image->path ?: $image->url);
        $nextPath = $this->normalizeManagedProductImagePath($relativeUrl);

        if ($currentPath && $nextPath) {
            return $currentPath === $nextPath;
        }

        return (string) ($image->url ?? $image->path ?? '') === (string) $relativeUrl;
    }

    private function normalizeManagedProductImagePath(?string $path): ?string
    {
        $normalizedPath = $this->files->normalizeRelativePath($path);
        if (!$normalizedPath || Str::startsWith($normalizedPath, ['http://', 'https://'])) {
            return null;
        }

        $productImageDirectory = $this->getProductImageDirectory();
        if ($normalizedPath === basename($normalizedPath)) {
            return $productImageDirectory . '/' . $normalizedPath;
        }

        if ($normalizedPath === $productImageDirectory || Str::startsWith($normalizedPath, $productImageDirectory . '/')) {
            return $normalizedPath;
        }

        return null;
    }

    private function getProductImageDirectory(): string
    {
        return trim((string) config('media.directories.clothes', 'clients/assets/img/clothes'), '/');
    }

    private function newProductImageScope(Product $product)
    {
        return Image::query()->where(function ($query) use ($product) {
            $query->where('product_id', $product->id);

            if ($this->hasImageRegistryColumns()) {
                $query->orWhere(function ($nested) use ($product) {
                    $nested->where('entity_id', $product->id)
                        ->whereIn('entity_type', ['product', Product::class]);
                });
            }
        });
    }

    private function hasImageRegistryColumns(): bool
    {
        return $this->hasImageColumn('entity_id') && $this->hasImageColumn('entity_type');
    }

    private function hasImageColumn(string $column): bool
    {
        $columns = $this->getImageTableColumns();
        return in_array($column, $columns, true);
    }

    private function getExistingImagePathColumns(): array
    {
        return array_values(array_filter([
            $this->hasImageColumn('path') ? 'path' : null,
            $this->hasImageColumn('url') ? 'url' : null,
            $this->hasImageColumn('thumbnail_url') ? 'thumbnail_url' : null,
            $this->hasImageColumn('medium_url') ? 'medium_url' : null,
        ]));
    }

    private function getImageTableColumns(): array
    {
        if ($this->imageTableColumns !== null) {
            return $this->imageTableColumns;
        }

        if (!Schema::hasTable('images')) {
            return $this->imageTableColumns = [];
        }

        return $this->imageTableColumns = Schema::getColumnListing('images');
    }
}
