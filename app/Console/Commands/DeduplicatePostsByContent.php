<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeduplicatePostsByContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:deduplicate-content {--force-delete : Xóa vĩnh viễn (force delete) thay vì xóa mềm (soft delete)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lọc các bài viết trùng lặp nội dung (content), giữ lại bài có view cao nhất và xóa các bài còn lại.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu kiểm tra và lọc các bài viết trùng nội dung (content)...');
        
        $isForceDelete = $this->option('force-delete');
        if ($isForceDelete) {
            $this->warn('Chế độ XÓA VĨNH VIỄN (Force Delete) đang được bật!');
        } else {
            $this->info('Chế độ Xóa mềm (Soft Delete) đang được sử dụng. Các bài viết sẽ được đưa vào thùng rác.');
        }

        // Tìm các nhóm bài viết trùng nội dung
        // Sử dụng hàm MD5 để băm chuỗi nội dung dài, giúp gom nhóm (GROUP BY) hiệu quả trên DB
        $duplicateGroups = Post::select(DB::raw('MD5(TRIM(content)) as content_hash'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->groupBy('content_hash')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isEmpty()) {
            $this->info('Tuyệt vời! Không tìm thấy bài viết nào trùng nội dung.');
            return;
        }

        $this->info('Tìm thấy ' . $duplicateGroups->count() . ' nhóm bài viết bị trùng nội dung.');

        $deletedCount = 0;

        foreach ($duplicateGroups as $dup) {
            $hash = $dup->content_hash;

            // Lấy tất cả bài viết dùng chung content hash này
            // Sắp xếp theo views giảm dần để bài có view cao nhất đứng đầu
            // Sau đó sắp xếp theo ID tăng dần (ưu tiên giữ bài đăng sớm nhất nếu view bằng nhau)
            $posts = Post::whereRaw('MD5(TRIM(content)) = ?', [$hash])
                ->orderBy('views', 'desc')
                ->orderBy('id', 'asc')
                ->get();

            if ($posts->isEmpty()) {
                continue;
            }

            // Bài đầu tiên là bài có view cao nhất -> Được giữ lại
            $keptPost = $posts->first();
            $this->line("--------------------------------------------------");
            $this->info(" -> Giữ lại bài viết ID: {$keptPost->id} | Views: {$keptPost->views} | Tiêu đề: {$keptPost->title}");

            // Các bài còn lại sẽ bị xóa
            $postsToDelete = $posts->slice(1);
            foreach ($postsToDelete as $post) {
                if ($isForceDelete) {
                    $post->forceDelete();
                } else {
                    $post->delete();
                }
                
                $deletedCount++;
                $this->line("    [Xóa] ID: {$post->id} | Views: {$post->views} | Tiêu đề: {$post->title}");
                
                // Ghi log để tiện kiểm tra lại
                Log::info("Đã xóa bài viết do trùng nội dung", [
                    'action' => $isForceDelete ? 'force_deleted' : 'soft_deleted',
                    'deleted_post_id' => $post->id,
                    'kept_post_id' => $keptPost->id
                ]);
            }
        }

        $this->line("==================================================");
        $this->info("Hoàn thành! Đã dọn dẹp tổng cộng {$deletedCount} bài viết trùng nội dung.");
    }
}
