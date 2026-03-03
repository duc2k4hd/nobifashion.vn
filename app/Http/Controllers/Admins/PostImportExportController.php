<?php
 
 namespace App\Http\Controllers\Admins;
 
 use App\Http\Controllers\Controller;
 use App\Models\Category;
 use App\Models\Post;
 use App\Models\Tag;
 use App\Models\Account;
 use Illuminate\Http\Request;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Auth;
 use Illuminate\Support\Str;
 
 class PostImportExportController extends Controller
 {
     protected $postService;
     protected $seoService;
 
     public function __construct(\App\Services\PostService $postService, \App\Services\SeoService $seoService)
     {
         $this->postService = $postService;
         $this->seoService = $seoService;
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
      * API lấy dữ liệu toàn bộ bài viết để Export qua JS
      */
     public function getExportData()
     {
         $posts = Post::with(['category', 'author'])->get();
         
         $data = $posts->map(function ($post) {
             return [
                 'ID' => $post->id,
                 'Tiêu đề' => $post->title,
                 'Slug' => $post->slug,
                 'Danh mục (Slug)' => $post->category?->slug ?? '',
                 'Nội dung' => $post->content,
                 'Tóm tắt' => $post->excerpt,
                 'Thumbnail URL' => $post->thumbnail,
                 'Alt ảnh' => $post->thumbnail_alt_text,
                 'Trạng thái' => $post->status,
                 'Nổi bật' => $post->is_featured ? 1 : 0,
                 'Tags (phẩy)' => $post->tags()->pluck('name')->implode(', '),
                 'Meta Title' => $post->meta_title,
                 'Meta Description' => $post->meta_description,
                 'Meta Keywords' => $post->meta_keywords,
                 'Meta Canonical' => $post->meta_canonical,
                 'Tác giả (Email)' => $post->author?->email ?? '',
                 'Ngày xuất bản' => $post->published_at ? $post->published_at->format('Y-m-d H:i:s') : '',
             ];
         });
 
         return response()->json([
             'success' => true,
             'data' => $data
         ]);
     }
 
     /**
      * API xử lý batch import bài viết (Tối ưu hóa Ultra Fast)
      */
     public function importBatch(Request $request)
     {
         $request->validate([
             'items' => 'required|array',
         ]);
 
         $items = $request->input('items');
         $successCount = 0;
         $errors = [];
         $author = Auth::user();
 
         // --- BƯỚC 1: EAGER LOADING TOÀN BỘ DỮ LIỆU LIÊN QUAN TRONG BATCH ---
         $ids = collect($items)->pluck('ID')->filter()->toArray();
         $categorySlugs = collect($items)->pluck('Danh mục (Slug)')->filter()->unique()->toArray();
         $authorEmails = collect($items)->pluck('Tác giả (Email)')->filter()->unique()->toArray();
 
         // Fetch data in bulk
         $existingPosts = Post::with(['tags', 'category'])->whereIn('id', $ids)->get()->keyBy('id');
         // Eager load categories and accounts with case-insensitive mapping
         $categoriesMap = Category::whereIn('slug', $categorySlugs)->get()->keyBy('slug');
         $accountsMap = Account::whereIn('email', $authorEmails)->get()->keyBy(fn($acc) => strtolower($acc->email));
 
         // --- BƯỚC 2: XỬ LÝ TỪNG ITEM SỬ DỤNG LOCAL CACHE ---
         foreach ($items as $index => $item) {
             try {
                 $id = !empty($item['ID']) ? (int)$item['ID'] : null;
                 
                 $payload = [
                     'title' => Str::limit(trim($item['Tiêu đề'] ?? ''), 250),
                     'slug' => !empty($item['Slug']) ? trim($item['Slug']) : null,
                     'category_slug' => trim($item['Danh mục (Slug)'] ?? ''),
                     'content' => $item['Nội dung'] ?? '',
                     'excerpt' => $item['Tóm tắt'] ?? '',
                     'thumbnail' => $item['Thumbnail URL'] ?? '',
                     'thumbnail_alt_text' => $item['Alt ảnh'] ?? '',
                     'status' => trim($item['Trạng thái'] ?? 'draft'),
                     'is_featured' => (bool)($item['Nổi bật'] ?? false),
                     'tag_names' => trim($item['Tags (phẩy)'] ?? ''),
                     'meta_title' => $item['Meta Title'] ?? '',
                     'meta_description' => $item['Meta Description'] ?? '',
                     'meta_keywords' => $item['Meta Keywords'] ?? '',
                     'meta_canonical' => $item['Meta Canonical'] ?? '',
                     'author_email' => trim($item['Tác giả (Email)'] ?? ''),
                     'published_at' => !empty(trim($item['Ngày xuất bản'] ?? '')) ? trim($item['Ngày xuất bản']) : null,
                 ];
 
                 if (empty($payload['title'])) {
                     throw new \Exception("Tiêu đề không được để trống.");
                 }
 
                 // Tìm category_id từ map (không query lẻ)
                 if (!empty($payload['category_slug'])) {
                     $category = $categoriesMap->get($payload['category_slug']);
                     $payload['category_id'] = $category?->id;
                 }
 
                 // Tìm tác giả từ map (không query lẻ)
                 $currentAuthor = $author;
                 if (!empty($payload['author_email'])) {
                     $targetAccount = $accountsMap->get(strtolower($payload['author_email']));
                     if ($targetAccount) {
                         $currentAuthor = $targetAccount;
                         $payload['account_id'] = $targetAccount->id;
                         $payload['created_by'] = $targetAccount->id; // Gán cho author() relation
                     }
                 }
 
                 // Cập nhật hoặc tạo mới
                 if ($id && ($post = $existingPosts->get($id))) {
                     // So sánh thông minh để bỏ qua update thừa
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
                         'is_featured' => (bool)$post->is_featured,
                         'meta_title' => $post->meta_title,
                         'meta_description' => $post->meta_description,
                         'meta_keywords' => $post->meta_keywords,
                         'meta_canonical' => $post->meta_canonical,
                         'category_id' => $post->category_id,
                         'account_id' => $post->account_id,
                         'created_by' => $post->created_by,
                     ];
 
                     foreach ($comparisons as $key => $oldValue) {
                         if ($payload[$key] != $oldValue) {
                             $hasChanged = true;
                             break;
                         }
                     }
 
                     if (!$hasChanged) {
                         if ($payload['category_slug'] != ($post->category?->slug ?? '')) $hasChanged = true;
                         if (trim($payload['tag_names']) != trim($currentTagsStr)) $hasChanged = true;
                     }
 
                     if (!$hasChanged) {
                         $successCount++;
                         continue;
                     }
 
                     $this->postService->update($post, $payload, $currentAuthor);
                     $successCount++;
                 } else {
                     $this->postService->create($payload, $currentAuthor);
                     $successCount++;
                 }
 
             } catch (\Exception $e) {
                 $errors[] = "Sản phẩm [" . ($item['Tiêu đề'] ?? 'Không tên') . "]: " . $e->getMessage();
             }
         }
 
         return response()->json([
             'success' => true,
             'success_count' => $successCount,
             'errors' => $errors
         ]);
     }
 }
