<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\HtmlCompressorService;
use App\Services\PostService;
use App\Services\SeoService;
use Carbon\Carbon;
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
     * Xuất trực tiếp file CSV dạng StreamedResponse (Tối ưu tuyệt đối O(1) Memory, không giới hạn số lượng bài viết, không lỗi font tiếng Việt)
     */
    public function exportCsv(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(600);

        $selectedColumns = $request->input('columns', ['ID', 'Tiêu đề', 'Slug', 'Nội dung']);
        if (! is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Tiêu đề', 'Slug', 'Nội dung'];
        }

        $query = $this->buildExportQuery($request);

        $withRelations = [];
        if (in_array('Danh mục (Slug)', $selectedColumns, true) || in_array('Danh mục (Tên)', $selectedColumns, true)) {
            $withRelations[] = 'category';
        }
        if (in_array('Tác giả (Email)', $selectedColumns, true)) {
            $withRelations[] = 'author';
        }
        if (in_array('Tags (phẩy)', $selectedColumns, true)) {
            $withRelations[] = 'tags';
        }

        if (! empty($withRelations)) {
            $query->with($withRelations);
        }

        $filename = 'posts_export_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($query, $selectedColumns) {
            $output = fopen('php://output', 'w');

            // Ghi UTF-8 BOM để Excel hiển thị đúng tiếng Việt có dấu
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Ghi dòng tiêu đề
            fputcsv($output, $selectedColumns);

            // Duyệt từng bản ghi bằng cursor để giải phóng RAM tức thì (O(1) Memory)
            foreach ($query->cursor() as $post) {
                $row = [];
                foreach ($selectedColumns as $col) {
                    $row[] = match ($col) {
                        'ID' => $post->id,
                        'Tiêu đề' => $post->title,
                        'Slug' => $post->slug,
                        'Danh mục (Tên)' => $post->category?->name ?? '',
                        'Danh mục (Slug)' => $post->category?->slug ?? '',
                        'Nội dung' => HtmlCompressorService::compress($post->content),
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
                        default => '',
                    };
                }
                fputcsv($output, $row);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Xây dựng query lọc bài viết dùng chung cho cả Export CSV và JSON
     */
    protected function buildExportQuery(Request $request)
    {
        $categoryFilter = $request->input('category_id');
        $isFilteringCategory = $request->has('category_id') && $categoryFilter !== null && $categoryFilter !== '';

        if ($request->input('status') === 'trashed') {
            $query = Post::onlyTrashed();
        } else {
            $query = Post::query();
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
        }

        // Lọc theo danh sách ID cụ thể (dành cho tính năng "Chỉ xuất những bài viết đang chọn")
        if ($request->filled('ids')) {
            $rawIds = $request->input('ids');
            $ids = is_array($rawIds)
                ? array_filter(array_map('intval', $rawIds))
                : array_filter(array_map('intval', explode(',', (string) $rawIds)));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        // Lọc theo danh mục (hỗ trợ cả trường hợp "chưa có danh mục": null, 0 hoặc orphan category)
        if ($isFilteringCategory) {
            if ($categoryFilter === 'none' || $categoryFilter === '0' || $categoryFilter === 'uncategorized') {
                $query->where(function ($sub) {
                    $sub->whereNull('category_id')
                        ->orWhere('category_id', 0)
                        ->orWhereDoesntHave('category');
                });
            } else {
                $query->where('category_id', (int) $categoryFilter);
            }
        }

        // Lọc theo tác giả nếu có
        if ($request->filled('author_id')) {
            $query->where('created_by', $request->integer('author_id'));
        }

        // Lọc theo tag nếu có
        if ($request->filled('tag_id')) {
            $tagId = $request->integer('tag_id');
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->where('tags.id', $tagId));
        }

        // Lọc thiếu thumbnail
        if ($request->filled('without_thumbnail')) {
            $query->whereNull('thumbnail');
        }

        // Lọc nổi bật
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Lọc theo ngày xuất bản
        if ($request->filled('date_from')) {
            $query->whereDate('published_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('published_at', '<=', $request->date('date_to'));
        }

        // Lọc theo từ khóa tìm kiếm nếu có
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sắp xếp (Hỗ trợ view_asc / view_desc / oldest / newest)
        $sort = $request->input('sort');
        if (in_array($sort, ['view_asc', 'views_asc', 'view_az', 'views_az'])) {
            $query->orderBy('views', 'asc')->orderByDesc('id');
        } elseif (in_array($sort, ['view_desc', 'views_desc', 'view_za', 'views_za'])) {
            $query->orderByDesc('views')->orderByDesc('id');
        } elseif ($sort === 'oldest') {
            $query->orderBy('id', 'asc');
        } else {
            $query->latest('id');
        }

        return $query;
    }

    /**
     * API lấy dữ liệu bài viết để Export qua JS (Hỗ trợ lọc & chọn cột)
     */
    public function getExportData(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $selectedColumns = $request->input('columns', ['ID', 'Tiêu đề', 'Slug', 'Nội dung']);
        if (! is_array($selectedColumns) || empty($selectedColumns)) {
            $selectedColumns = ['ID', 'Tiêu đề', 'Slug', 'Nội dung'];
        }

        $query = $this->buildExportQuery($request);

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

        $posts = $query->get();

        $data = $posts->map(function ($post) use ($selectedColumns) {
            $row = [
                'ID' => $post->id,
                'Tiêu đề' => $post->title,
                'Slug' => $post->slug,
                'Danh mục (Tên)' => $post->category?->name ?? '',
                'Danh mục (Slug)' => $post->category?->slug ?? '',
                'Nội dung' => HtmlCompressorService::compress($post->content),
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
        @set_time_limit(300);

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
            $itemSlug = trim((string) ($item['Slug'] ?? ''));
            $itemId = trim((string) ($item['ID'] ?? ''));
            $itemContent = trim((string) ($item['Nội dung'] ?? ''));

            // Bỏ qua dòng hoàn toàn trống (dòng trắng thừa sinh ra từ Excel)
            if ($itemTitle === '' && $itemSlug === '' && $itemId === '' && $itemContent === '') {
                continue;
            }

            $excelRow = isset($item['_excel_row']) ? (int) $item['_excel_row'] : ($index + 1);
            $itemIdentifier = ! empty($itemId)
                ? "ID {$itemId} (Dòng #{$excelRow})"
                : (! empty($itemSlug)
                    ? "Slug '{$itemSlug}' (Dòng #{$excelRow})"
                    : ($itemTitle ? "Dòng #{$excelRow} - {$itemTitle}" : "Dòng #{$excelRow}"));

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
                        'published_at' => ($isColSelected('Ngày xuất bản') && array_key_exists('Ngày xuất bản', $item) && $item['Ngày xuất bản'] !== null && trim((string) $item['Ngày xuất bản']) !== '')
                            ? ($this->normalizeDateTime($item['Ngày xuất bản']) ?? ($post->published_at ? $post->published_at->format('Y-m-d H:i:s') : null))
                            : ($post->published_at ? $post->published_at->format('Y-m-d H:i:s') : null),
                        'category_id' => $post->category_id,
                        'account_id' => $post->account_id,
                        'created_by' => $post->created_by,
                        'skip_responsive_images' => true,
                        'skip_revisions' => true,
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
                        'published_at' => $post->published_at ? $post->published_at->format('Y-m-d H:i:s') : null,
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
                        'status' => ($isColSelected('Trạng thái') && ! empty(trim((string) ($item['Trạng thái'] ?? ''))))
                            ? trim((string) $item['Trạng thái'])
                            : 'published',
                        'is_featured' => $isColSelected('Nổi bật') ? (bool) ($item['Nổi bật'] ?? false) : false,
                        'tag_names' => $isColSelected('Tags (phẩy)') ? trim((string) ($item['Tags (phẩy)'] ?? '')) : '',
                        'meta_title' => $isColSelected('Meta Title') ? (string) ($item['Meta Title'] ?? '') : '',
                        'meta_description' => $isColSelected('Meta Description') ? (string) ($item['Meta Description'] ?? '') : '',
                        'meta_keywords' => $isColSelected('Meta Keywords') ? (string) ($item['Meta Keywords'] ?? '') : '',
                        'meta_canonical' => $isColSelected('Meta Canonical') ? (string) ($item['Meta Canonical'] ?? '') : '',
                        'published_at' => ($isColSelected('Ngày xuất bản') && array_key_exists('Ngày xuất bản', $item) && $item['Ngày xuất bản'] !== null && trim((string) $item['Ngày xuất bản']) !== '')
                            ? ($this->normalizeDateTime($item['Ngày xuất bản']) ?? now()->format('Y-m-d H:i:s'))
                            : now()->format('Y-m-d H:i:s'),
                        'skip_responsive_images' => true,
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

        return HtmlCompressorService::compress($content);
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

    /**
     * Chuẩn hoá thông minh ngày tháng từ Excel / CSV sang định dạng Y-m-d H:i:s
     * Xử lý triệt để:
     * 1. Số Excel Serial Date (ví dụ: 46297.453... -> 2026-10-02 10:53:00)
     * 2. Chuỗi có khoảng trắng thừa (ví dụ: "02-10-2026  10:53:00")
     * 3. Các định dạng ngày tháng thông dụng tại Việt Nam (d/m/Y, d-m-Y)
     * 4. Định dạng chuẩn quốc tế (Y-m-d, ISO 8601)
     */
    private function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $tz = config('app.timezone', 'Asia/Ho_Chi_Minh');

        // 1. Trường hợp số Excel Serial Date (Ví dụ: 46297.453... -> 2026-10-02 10:53:00)
        // 25569 = 01/01/1970 trong Excel, Excel không lưu timezone nên quy đổi qua UTC để giữ đúng giờ người dùng nhập
        if (is_numeric($value)) {
            $num = (float) $value;
            if ($num >= 20000 && $num <= 100000) {
                $seconds = round(($num - 25569) * 86400);
                return Carbon::createFromTimestampUTC((int) $seconds)->format('Y-m-d H:i:s');
            }
            if ($num > 100000000) {
                return Carbon::createFromTimestamp((int) $num, $tz)->format('Y-m-d H:i:s');
            }
        }

        // 2. Chuẩn hoá khoảng trắng thừa (ví dụ "02-10-2026  10:53:00")
        $cleanValue = preg_replace('/\s+/', ' ', $value);

        // 3. Chuỗi ISO 8601 có múi giờ Z hoặc offset (ví dụ "2026-10-02T03:53:00.000Z")
        if (str_contains($cleanValue, 'T') && (str_ends_with($cleanValue, 'Z') || preg_match('/[+-]\d{2}:?\d{2}$/', $cleanValue))) {
            try {
                return Carbon::parse($cleanValue)->setTimezone($tz)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
            }
        }

        // 4. Các định dạng ngày tháng kiểu Việt Nam & quốc tế
        $formats = [
            'd-m-Y H:i:s',
            'd/m/Y H:i:s',
            'd-m-Y H:i',
            'd/m/Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd-m-Y',
            'd/m/Y',
            'Y-m-d',
            'Y/m/d H:i:s',
            'Y/m/d',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $cleanValue, $tz);
                if ($parsed && $parsed->format($format) === $cleanValue) {
                    if (! str_contains($format, 'H')) {
                        $parsed->startOfDay();
                    }
                    return $parsed->format('Y-m-d H:i:s');
                }
            } catch (\Throwable) {
            }
        }

        // 5. Fallback tự động parse bằng Carbon thông thường
        try {
            return Carbon::parse($cleanValue, $tz)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
