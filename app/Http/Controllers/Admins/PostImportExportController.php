<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\PostService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostImportExportController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected SeoService $seoService,
    ) {
        $this->middleware(['auth:web', 'admin']);
    }

    /**
     * Hiển thị form upload Excel cho bài viết
     */
    public function importForm()
    {
        return view('admins.posts.import');
    }

    /**
     * API lấy dữ liệu toàn bộ bài viết để Export qua JS (Hỗ trợ lọc & chọn cột)
     */
    public function getExportData(Request $request)
    {
        $selectedColumns = $request->input('columns', ['ID', 'Tiêu đề', 'Slug', 'Nội dung']);
        if (! is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Tiêu đề', 'Slug', 'Nội dung'];
        }

        $query = Post::query();

        // Lọc theo trạng thái nếu có
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Lọc theo danh mục nếu có
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Lọc theo từ khóa tìm kiếm nếu có
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Tối ưu Eager Loading chỉ khi người dùng chọn cột tương ứng
        $withRelations = [];
        if (empty($selectedColumns) || in_array('Danh mục (Slug)', $selectedColumns, true) || in_array('Danh mục (Tên)', $selectedColumns, true)) {
            $withRelations[] = 'category';
        }
        if (empty($selectedColumns) || in_array('Tác giả (Email)', $selectedColumns, true)) {
            $withRelations[] = 'author';
        }
        if (empty($selectedColumns) || in_array('Tags (phẩy)', $selectedColumns, true)) {
            $withRelations[] = 'tags';
        }

        if (! empty($withRelations)) {
            $query->with($withRelations);
        }

        $posts = $query->latest('id')->get();

        $data = $posts->map(function ($post) use ($selectedColumns) {
            $row = [
                'ID' => $post->id,
                'Tiêu đề' => $post->title,
                'Slug' => $post->slug,
                'Danh mục (Tên)' => $post->category?->name ?? '',
                'Danh mục (Slug)' => $post->category?->slug ?? '',
                'Nội dung' => $post->content,
                'Tóm tắt' => $post->excerpt,
                'Thumbnail URL' => $post->thumbnail,
                'Alt ảnh' => $post->thumbnail_alt_text,
                'Trạng thái' => $post->status,
                'Nổi bật' => $post->is_featured ? 1 : 0,
                'Tags (phẩy)' => $post->relationLoaded('tags') ? $post->tags->pluck('name')->implode(', ') : '',
                'Meta Title' => $post->meta_title,
                'Meta Description' => $post->meta_description,
                'Meta Keywords' => $post->meta_keywords,
                'Meta Canonical' => $post->meta_canonical,
                'Tác giả (Email)' => $post->author?->email ?? '',
                'Ngày xuất bản' => $post->published_at ? $post->published_at->format('Y-m-d H:i:s') : '',
            ];

            if (empty($selectedColumns)) {
                return $row;
            }

            // Chỉ giữ lại các cột người dùng đã chọn
            $filtered = [];
            foreach ($selectedColumns as $col) {
                if (array_key_exists($col, $row)) {
                    $filtered[$col] = $row[$col];
                }
            }

            return $filtered;
        });

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * API xử lý batch import bài viết (Tối ưu hóa Ultra Fast, chọn cột & logic khớp bài viết chuẩn xác)
     */
    public function importBatch(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'selected_columns' => 'nullable|array',
        ]);

        $items = $request->input('items');
        $selectedColumns = $request->input('selected_columns');
        $isColumnFilterActive = is_array($selectedColumns) && ! empty($selectedColumns);

        $successCount = 0;
        $errors = [];
        $author = Auth::user();

        // Helper kiểm tra xem cột có được chọn để nhập không
        $isColSelected = function (string $colName) use ($isColumnFilterActive, $selectedColumns): bool {
            if (! $isColumnFilterActive) {
                return true;
            }

            return in_array($colName, $selectedColumns, true);
        };

        // --- BƯỚC 1: EAGER LOADING TOÀN BỘ DỮ LIỆU LIÊN QUAN TRONG BATCH ---
        $ids = [];
        $slugs = [];
        $categorySlugs = collect($items)->pluck('Danh mục (Slug)')->filter()->unique()->toArray();
        $categoryNames = collect($items)->pluck('Danh mục (Tên)')->filter()->unique()->toArray();
        $authorEmails = collect($items)->pluck('Tác giả (Email)')->filter()->unique()->toArray();

        foreach ($items as $item) {
            if (! empty($item['ID'])) {
                $ids[] = (int) $item['ID'];
            } else {
                $slug = ! empty($item['Slug'])
                    ? trim((string) $item['Slug'])
                    : Str::slug(trim((string) ($item['Tiêu đề'] ?? '')));
                if ($slug !== '') {
                    $slugs[] = $slug;
                }
            }
        }

        $ids = array_values(array_unique($ids));
        $slugs = array_values(array_unique($slugs));

        // Bulk load posts, categories, accounts
        $existingPostsById = ! empty($ids)
            ? Post::with(['tags', 'category'])->whereIn('id', $ids)->get()->keyBy('id')
            : collect();

        $existingPostsBySlug = ! empty($slugs)
            ? Post::with(['tags', 'category'])->whereIn('slug', $slugs)->get()->keyBy('slug')
            : collect();

        $categoriesMapBySlug = ! empty($categorySlugs)
            ? PostCategory::whereIn('slug', $categorySlugs)->get()->keyBy('slug')
            : collect();

        $categoriesMapByName = ! empty($categoryNames)
            ? PostCategory::whereIn('name', $categoryNames)->get()->keyBy(fn ($c) => mb_strtolower($c->name))
            : collect();

        $accountsMap = ! empty($authorEmails)
            ? Account::whereIn('email', $authorEmails)->get()->keyBy(fn ($acc) => strtolower($acc->email))
            : collect();

        // --- BƯỚC 2: XỬ LÝ TỪNG ITEM VỚI LOGIC KHỚP BÀI VIẾT CHUẨN XÁC ---
        foreach ($items as $index => $item) {
            $itemTitle = trim((string) ($item['Tiêu đề'] ?? ''));
            $itemIdentifier = ! empty($item['ID']) ? "ID {$item['ID']}" : ($item['Slug'] ?? $itemTitle ?: 'Dòng #'.($index + 1));

            try {
                $hasId = ! empty($item['ID']);
                $id = $hasId ? (int) $item['ID'] : null;

                $post = null;
                $isUpdate = false;

                // Quy tắc 1: Nếu có ID thì BẮT BUỘC đó là cập nhật
                if ($hasId) {
                    $post = $existingPostsById->get($id);
                    if (! $post) {
                        throw new \Exception("Bài viết với ID = {$id} không tồn tại trên hệ thống. (Theo quy tắc: Khi có ID bắt buộc phải là cập nhật).");
                    }
                    $isUpdate = true;
                } else {
                    // Quy tắc 2 & 3: Nếu không có ID thì khớp theo Slug; nếu không có slug thì tự sinh slug từ tiêu đề
                    $slug = ! empty($item['Slug'])
                        ? trim((string) $item['Slug'])
                        : Str::slug($itemTitle);

                    if ($slug !== '') {
                        $post = $existingPostsBySlug->get($slug);
                    }

                    if ($post) {
                        $isUpdate = true; // Khớp theo slug -> cập nhật
                    } else {
                        $isUpdate = false; // Không có ID và slug chưa tồn tại -> TẠO MỚI
                    }
                }

                // Xây dựng payload dựa trên selected_columns
                if ($isUpdate) {
                    // CẬP NHẬT BÀI VIẾT HIỆN CÓ: Chỉ thay đổi những cột được chọn
                    $payload = [
                        'title' => $isColSelected('Tiêu đề') && array_key_exists('Tiêu đề', $item)
                            ? Str::limit(trim((string) $item['Tiêu đề']), 250)
                            : $post->title,
                        'slug' => $isColSelected('Slug') && ! empty($item['Slug'])
                            ? trim((string) $item['Slug'])
                            : $post->slug,
                        'content' => $isColSelected('Nội dung')
                            ? $this->joinImportedContent($item)
                            : $post->content,
                        'excerpt' => $isColSelected('Tóm tắt') && array_key_exists('Tóm tắt', $item)
                            ? (string) $item['Tóm tắt']
                            : $post->excerpt,
                        'thumbnail' => $isColSelected('Thumbnail URL') && array_key_exists('Thumbnail URL', $item)
                            ? (string) $item['Thumbnail URL']
                            : $post->thumbnail,
                        'thumbnail_alt_text' => $isColSelected('Alt ảnh') && array_key_exists('Alt ảnh', $item)
                            ? (string) $item['Alt ảnh']
                            : $post->thumbnail_alt_text,
                        'status' => $isColSelected('Trạng thái') && array_key_exists('Trạng thái', $item)
                            ? trim((string) $item['Trạng thái'])
                            : $post->status,
                        'is_featured' => $isColSelected('Nổi bật') && array_key_exists('Nổi bật', $item)
                            ? (bool) $item['Nổi bật']
                            : (bool) $post->is_featured,
                        'tag_names' => $isColSelected('Tags (phẩy)') && array_key_exists('Tags (phẩy)', $item)
                            ? trim((string) $item['Tags (phẩy)'])
                            : $post->tags->pluck('name')->implode(', '),
                        'meta_title' => $isColSelected('Meta Title') && array_key_exists('Meta Title', $item)
                            ? (string) $item['Meta Title']
                            : $post->meta_title,
                        'meta_description' => $isColSelected('Meta Description') && array_key_exists('Meta Description', $item)
                            ? (string) $item['Meta Description']
                            : $post->meta_description,
                        'meta_keywords' => $isColSelected('Meta Keywords') && array_key_exists('Meta Keywords', $item)
                            ? (string) $item['Meta Keywords']
                            : $post->meta_keywords,
                        'meta_canonical' => $isColSelected('Meta Canonical') && array_key_exists('Meta Canonical', $item)
                            ? (string) $item['Meta Canonical']
                            : $post->meta_canonical,
                        'published_at' => $isColSelected('Ngày xuất bản') && array_key_exists('Ngày xuất bản', $item)
                            ? (! empty(trim((string) $item['Ngày xuất bản'])) ? trim((string) $item['Ngày xuất bản']) : null)
                            : ($post->published_at ? $post->published_at->format('Y-m-d H:i:s') : null),
                        'category_id' => $post->category_id,
                        'account_id' => $post->account_id,
                        'created_by' => $post->created_by,
                    ];

                    $catId = $this->resolvePostCategoryId($item, $categoriesMapBySlug, $categoriesMapByName, $isColSelected);
                    if ($catId !== null) {
                        $payload['category_id'] = $catId;
                    }

                    $currentAuthor = $author;
                    if ($isColSelected('Tác giả (Email)') && ! empty($item['Tác giả (Email)'])) {
                        $authorEmail = strtolower(trim((string) $item['Tác giả (Email)']));
                        $targetAccount = $accountsMap->get($authorEmail);
                        if ($targetAccount) {
                            $currentAuthor = $targetAccount;
                            $payload['account_id'] = $targetAccount->id;
                        }
                    }

                    // So sánh dữ liệu thông minh để bỏ qua update thừa
                    $hasChanged = false;
                    $currentTagsStr = $post->tags->pluck('name')->implode(', ');

                    $comparisons = [
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'content' => $post->content,
                        'excerpt' => $post->excerpt,
                        'thumbnail' => $post->thumbnail,
                        'thumbnail_alt_text' => $post->thumbnail_alt_text,
                        'status' => $post->status,
                        'is_featured' => (bool) $post->is_featured,
                        'meta_title' => $post->meta_title,
                        'meta_description' => $post->meta_description,
                        'meta_keywords' => $post->meta_keywords,
                        'meta_canonical' => $post->meta_canonical,
                        'category_id' => $post->category_id,
                        'account_id' => $post->account_id,
                    ];

                    foreach ($comparisons as $key => $oldVal) {
                        if ($payload[$key] != $oldVal) {
                            $hasChanged = true;
                            break;
                        }
                    }

                    if (! $hasChanged && trim($payload['tag_names']) != trim($currentTagsStr)) {
                        $hasChanged = true;
                    }

                    if (! $hasChanged) {
                        $successCount++;

                        continue;
                    }

                    $this->postService->update($post, $payload, $currentAuthor);
                    $successCount++;
                } else {
                    // TẠO MỚI BÀI VIẾT
                    $title = $isColSelected('Tiêu đề') ? Str::limit(trim((string) ($item['Tiêu đề'] ?? '')), 250) : '';
                    if (empty($title)) {
                        throw new \Exception('Tiêu đề bài viết không được để trống khi tạo mới.');
                    }

                    $slug = $isColSelected('Slug') && ! empty($item['Slug'])
                        ? trim((string) $item['Slug'])
                        : Str::slug($title);

                    $payload = [
                        'title' => $title,
                        'slug' => $slug,
                        'content' => $isColSelected('Nội dung') ? $this->joinImportedContent($item) : '',
                        'excerpt' => $isColSelected('Tóm tắt') ? (string) ($item['Tóm tắt'] ?? '') : '',
                        'thumbnail' => $isColSelected('Thumbnail URL') ? (string) ($item['Thumbnail URL'] ?? '') : '',
                        'thumbnail_alt_text' => $isColSelected('Alt ảnh') ? (string) ($item['Alt ảnh'] ?? '') : '',
                        'status' => $isColSelected('Trạng thái') ? trim((string) ($item['Trạng thái'] ?? 'draft')) : 'draft',
                        'is_featured' => $isColSelected('Nổi bật') ? (bool) ($item['Nổi bật'] ?? false) : false,
                        'tag_names' => $isColSelected('Tags (phẩy)') ? trim((string) ($item['Tags (phẩy)'] ?? '')) : '',
                        'meta_title' => $isColSelected('Meta Title') ? (string) ($item['Meta Title'] ?? '') : '',
                        'meta_description' => $isColSelected('Meta Description') ? (string) ($item['Meta Description'] ?? '') : '',
                        'meta_keywords' => $isColSelected('Meta Keywords') ? (string) ($item['Meta Keywords'] ?? '') : '',
                        'meta_canonical' => $isColSelected('Meta Canonical') ? (string) ($item['Meta Canonical'] ?? '') : '',
                        'published_at' => $isColSelected('Ngày xuất bản') && ! empty(trim((string) ($item['Ngày xuất bản'] ?? '')))
                            ? trim((string) $item['Ngày xuất bản'])
                            : null,
                    ];

                    $payload['category_id'] = $this->resolvePostCategoryId($item, $categoriesMapBySlug, $categoriesMapByName, $isColSelected);

                    $currentAuthor = $author;
                    if ($isColSelected('Tác giả (Email)') && ! empty($item['Tác giả (Email)'])) {
                        $authorEmail = strtolower(trim((string) $item['Tác giả (Email)']));
                        $targetAccount = $accountsMap->get($authorEmail);
                        if ($targetAccount) {
                            $currentAuthor = $targetAccount;
                            $payload['account_id'] = $targetAccount->id;
                            $payload['created_by'] = $targetAccount->id;
                        }
                    }

                    $newPost = $this->postService->create($payload, $currentAuthor);
                    $existingPostsBySlug->put($newPost->slug, $newPost);
                    $successCount++;
                }
            } catch (\Throwable $e) {
                $errors[] = "[{$itemIdentifier}]: {$e->getMessage()}";
            }
        }

        return response()->json([
            'success' => true,
            'success_count' => $successCount,
            'errors' => $errors,
        ]);
    }

    private function joinImportedContent(array $item): string
    {
        $content = (string) ($item['Nội dung'] ?? '');

        for ($part = 2; $part <= 100; $part++) {
            $column = "Nội dung {$part}";
            if (! array_key_exists($column, $item)) {
                break;
            }

            $content .= (string) $item[$column];
        }

        return $content;
    }

    private function resolvePostCategoryId(array $item, &$categoriesMapBySlug, &$categoriesMapByName, callable $isColSelected): ?int
    {
        $hasSlugCol = $isColSelected('Danh mục (Slug)');
        $hasNameCol = $isColSelected('Danh mục (Tên)') || $isColSelected('Danh mục');

        if (! $hasSlugCol && ! $hasNameCol) {
            return null;
        }

        $rawCatSlug = trim((string) ($item['Danh mục (Slug)'] ?? ''));
        $rawCatName = trim((string) ($item['Danh mục (Tên)'] ?? ($item['Danh mục'] ?? '')));

        if ($rawCatSlug === '' && $rawCatName === '') {
            return null;
        }

        // Tìm theo slug trước
        if ($rawCatSlug !== '') {
            $cat = $categoriesMapBySlug->get($rawCatSlug);
            if (! $cat) {
                // Tự động tạo mới PostCategory nếu chưa có
                $name = $rawCatName !== '' ? $rawCatName : Str::headline($rawCatSlug);
                $cat = PostCategory::create([
                    'name' => $name,
                    'slug' => $rawCatSlug,
                    'is_active' => true,
                ]);
                $categoriesMapBySlug->put($rawCatSlug, $cat);
                $categoriesMapByName->put(mb_strtolower($cat->name), $cat);
            }

            return (int) $cat->id;
        }

        // Tìm theo name
        if ($rawCatName !== '') {
            $key = mb_strtolower($rawCatName);
            $cat = $categoriesMapByName->get($key);
            if (! $cat) {
                // Tự động tạo mới PostCategory nếu chưa có
                $slug = Str::slug($rawCatName);
                $cat = PostCategory::create([
                    'name' => $rawCatName,
                    'slug' => $slug,
                    'is_active' => true,
                ]);
                $categoriesMapBySlug->put($cat->slug, $cat);
                $categoriesMapByName->put($key, $cat);
            }

            return (int) $cat->id;
        }

        return null;
    }
}
