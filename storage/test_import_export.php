<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Admins\PostImportExportController;
use App\Models\Account;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    $admin = Account::where('role', 'admin')->first() ?? Account::factory()->create(['role' => 'admin']);
    $category = Category::first() ?? Category::factory()->create();

    // Khởi tạo bài viết mẫu
    $testSlug = 'test-csv-bai-viet-' . uniqid();
    $post = Post::create([
        'title' => 'Test CSV Bài Viết Ban Đầu',
        'slug' => $testSlug,
        'excerpt' => 'Tom tat ban dau',
        'content' => 'Noi dung ban dau',
        'status' => 'draft',
        'category_id' => $category->id,
        'account_id' => $admin->id,
        'created_by' => $admin->id,
    ]);

    echo "Bài viết khởi tạo ID: {$post->id}\n";

    $controller = app(PostImportExportController::class);
    Auth::login($admin);

    // TEST 1: Có ID hợp lệ -> Bắt buộc cập nhật, chỉ các cột được chọn
    $req1 = Request::create('/admin/posts/import-batch', 'POST', [
        'items' => [
            [
                'ID' => $post->id,
                'Tiêu đề' => 'Tiêu đề mới từ ID',
                'Tóm tắt' => 'Tóm tắt mới nên bị bỏ qua nếu ko chọn',
                'Trạng thái' => 'published',
            ]
        ],
        'selected_columns' => ['Tiêu đề', 'Trạng thái']
    ]);
    $res1 = $controller->importBatch($req1)->getData(true);
    $post->refresh();
    $test1Pass = ($res1['success_count'] === 1 && $post->title === 'Tiêu đề mới từ ID' && $post->excerpt === 'Tom tat ban dau' && $post->status === 'published');
    echo "Test 1 (Có ID -> Bắt buộc cập nhật, chỉ cột chọn): " . ($test1Pass ? "PASS" : "FAIL") . "\n";
    if (!$test1Pass) {
        print_r($res1);
    }

    // TEST 2: Có ID không tồn tại -> Bắt buộc báo lỗi, KHÔNG tạo mới
    $req2 = Request::create('/admin/posts/import-batch', 'POST', [
        'items' => [
            [
                'ID' => 99999999,
                'Tiêu đề' => 'ID ảo không tồn tại',
                'Slug' => 'id-ao-khong-ton-tai',
            ]
        ],
    ]);
    $res2 = $controller->importBatch($req2)->getData(true);
    $idPost = Post::where('slug', 'id-ao-khong-ton-tai')->first();
    $test2Pass = ($res2['success_count'] === 0 && count($res2['errors']) === 1 && $idPost === null);
    echo "Test 2 (Có ID không tồn tại -> Báo lỗi, không tạo mới): " . ($test2Pass ? "PASS" : "FAIL") . "\n";
    if (!$test2Pass) {
        print_r($res2);
    }

    // TEST 3: Không có ID, có Slug đã tồn tại -> Khớp theo Slug để cập nhật
    $req3 = Request::create('/admin/posts/import-batch', 'POST', [
        'items' => [
            [
                'ID' => '',
                'Slug' => $testSlug,
                'Tiêu đề' => 'Tiêu đề cập nhật từ Slug',
                'Tóm tắt' => 'Tóm tắt cập nhật từ Slug',
            ]
        ],
        'selected_columns' => ['Tiêu đề', 'Tóm tắt']
    ]);
    $res3 = $controller->importBatch($req3)->getData(true);
    $post->refresh();
    $test3Pass = ($res3['success_count'] === 1 && $post->title === 'Tiêu đề cập nhật từ Slug' && $post->excerpt === 'Tóm tắt cập nhật từ Slug');
    echo "Test 3 (Không ID, khớp Slug -> Cập nhật theo Slug): " . ($test3Pass ? "PASS" : "FAIL") . "\n";
    if (!$test3Pass) {
        print_r($res3);
    }

    // TEST 4: Không có ID, Slug chưa tồn tại -> Tạo mới bài viết
    $newSlug = 'bai-viet-hoan-toan-moi-' . time();
    $req4 = Request::create('/admin/posts/import-batch', 'POST', [
        'items' => [
            [
                'ID' => '',
                'Slug' => $newSlug,
                'Tiêu đề' => 'Bài viết hoàn toàn mới 100%',
                'Nội dung' => '<p>Nội dung mới tinh</p>',
                'Trạng thái' => 'published',
            ]
        ],
        'selected_columns' => ['Tiêu đề', 'Slug', 'Nội dung', 'Trạng thái']
    ]);
    $res4 = $controller->importBatch($req4)->getData(true);
    $newPost = Post::where('slug', $newSlug)->first();
    $test4Pass = ($res4['success_count'] === 1 && $newPost !== null && $newPost->title === 'Bài viết hoàn toàn mới 100%');
    echo "Test 4 (Không ID, Slug mới -> Tạo mới bài viết): " . ($test4Pass ? "PASS" : "FAIL") . "\n";
    if (!$test4Pass) {
        print_r($res4);
    }

    // TEST 5: Xuất dữ liệu lọc cột (getExportData)
    $reqExport = Request::create('/admin/posts/export-data', 'GET', [
        'columns' => ['ID', 'Tiêu đề', 'Slug', 'Trạng thái']
    ]);
    $resExport = $controller->getExportData($reqExport)->getData(true);
    $firstRow = $resExport['data'][0] ?? [];
    $keys = array_keys($firstRow);
    $test5Pass = ($resExport['success'] === true && $keys === ['ID', 'Tiêu đề', 'Slug', 'Trạng thái']);
    echo "Test 5 (Xuất dữ liệu chỉ gồm đúng các cột đã chọn): " . ($test5Pass ? "PASS" : "FAIL") . "\n";
    if (!$test5Pass) {
        echo "Keys returned: " . implode(', ', $keys) . "\n";
    }

    echo "\n=== KẾT QUẢ: TẤT CẢ TEST ĐÃ HOÀN TẤT ===\n";
} finally {
    DB::rollBack();
    echo "Đã rollback toàn bộ database, dữ liệu hoàn toàn nguyên vẹn!\n";
}
