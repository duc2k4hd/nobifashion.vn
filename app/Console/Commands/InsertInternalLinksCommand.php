<?php

/**
 * ====================================================================================
 * COMMAND: post:insert-internal-links
 * ====================================================================================
 * TỰ ĐỘNG CHÈN INTERNAL LINKS CHO BÀI VIẾT (CHUẨN SEO TOPIC CLUSTER - HIỆU NĂNG CAO)
 *
 * 1. NGUYÊN LÝ HOẠT ĐỘNG:
 *    - Bài viết CÓ thẻ <figure>: Chèn liên kết ngay dưới </figure> (nếu chưa có Xem thêm:).
 *    - Bài viết KHÔNG CÓ <figure>: Chèn liên kết ngay trên mỗi thẻ <h2> (nếu chưa có Xem thêm:).
 *    - Format liên kết:
 *        <p>
 *            &gt;&gt;&gt; Xem thêm: <a target="_blank" rel="noopener noreferrer" href="https://nobifashion.vn/blog/{slug}">{Title}</a>
 *        </p>
 *    - Thuật toán Random xoay vòng cân bằng (Round-robin Balanced Random):
 *        + Ưu tiên bài viết cùng danh mục; nếu thiếu/không có danh mục thì bù từ danh mục khác.
 *        + Không tự link tới chính mình, không trùng link trong cùng 1 bài.
 *        + Theo dõi tần suất link trên toàn website: bài nào ít được link sẽ được ưu tiên random trước.
 *          Tuyệt đối KHÔNG BỊ lặp đi lặp lại một vài bài viết cố định.
 *    - Hiệu năng & An toàn:
 *        + Xử lý theo chunkById + giải phóng RAM định kỳ: chạy mượt với hàng triệu bài viết.
 *        + Thao tác trực tiếp theo offset chuỗi (ngược từ dưới lên), giữ nguyên 100% tiếng Việt UTF-8.
 *
 * 2. TỔNG HỢP CÁC LỆNH CHẠY THƯỜNG DÙNG:
 *
 *    ① Chạy thử nghiệm (Dry-Run - Không sửa Database, in mẫu Preview):
 *       php artisan post:insert-internal-links --dry-run --preview
 *
 *    ② Chạy thử nghiệm trên duy nhất 1 bài viết theo ID:
 *       php artisan post:insert-internal-links --id=40 --dry-run --preview
 *
 *    ③ Chạy thực thi cập nhật thật vào Database (Đơn luồng):
 *       php artisan post:insert-internal-links --force
 *
 *    ④ Chạy siêu tốc đa luồng song song (Multi-process, chia đều core CPU):
 *       php artisan post:insert-internal-links --workers=4 --force
 *
 *    ⑤ Chạy với cấu hình tùy chỉnh (Batch size lớn, domain riêng, đa luồng):
 *       php artisan post:insert-internal-links --workers=4 --chunk=500 --domain=https://nobifashion.vn --force
 *
 *    ⑥ Giới hạn số lượng bài viết cần xử lý (ví dụ 100 bài đầu tiên):
 *       php artisan post:insert-internal-links --limit=100 --force
 * ====================================================================================
 */

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class InsertInternalLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:insert-internal-links
                            {--workers=1 : Số lượng luồng worker chạy song song}
                            {--worker-id= : ID luồng worker hiện tại (0..N-1, dùng nội bộ)}
                            {--total-workers= : Tổng số luồng worker (dùng nội bộ)}
                            {--chunk=200 : Số lượng bài viết mỗi batch}
                            {--limit=0 : Giới hạn tổng số bài viết cần xử lý}
                            {--id= : Chỉ xử lý duy nhất 1 bài viết theo ID}
                            {--domain=https://nobifashion.vn : Domain gốc cho internal link}
                            {--dry-run : Chạy thử nghiệm, kiểm tra và hiển thị kết quả mà không ghi vào DB}
                            {--preview : In đoạn HTML mẫu trước/sau khi chèn}
                            {--force : Bỏ qua bước xác nhận trước khi thực hiện}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tự động chèn internal link vào dưới thẻ <figure> (hoặc trên <h2> nếu không có figure) cho toàn bộ bài viết';

    /**
     * Cache kho ứng viên bài viết phục vụ chèn link
     *
     * @var array
     */
    protected array $categoryPool = [];
    protected array $allPool = [];

    /**
     * Theo dõi tần suất mỗi bài viết được chọn làm internal link
     * để thuật toán tự động phân tán ngẫu nhiên đều khắp toàn bộ website
     *
     * @var array<int, int>
     */
    protected array $globalLinkUsageCount = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $workers = max(1, (int) $this->option('workers'));
        $workerId = $this->option('worker-id');
        $totalWorkers = $this->option('total-workers');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');
        $targetId = $this->option('id') ? (int) $this->option('id') : null;

        // Nếu người dùng chọn chạy đa luồng từ Master process
        if ($workers > 1 && $workerId === null && !$targetId) {
            return $this->runMultiWorkers($workers);
        }

        $this->displayBanner($isDryRun, $workerId, $totalWorkers);

        if (!$isDryRun && !$isForce && $workerId === null) {
            if (!$this->confirm('⚠️ Bạn có chắc chắn muốn quét và cập nhật nội dung bài viết trong Database không?', true)) {
                $this->warn('Đã hủy thao tác.');
                return 0;
            }
        }

        $startTime = microtime(true);

        // 1. Tải trước kho ứng viên bài viết (chỉ select nhẹ nhàng id, title, slug, category_id)
        $this->info('📦 Đang tải danh sách bài viết ứng viên để lấy Internal Link...');
        $this->loadCandidatePool();
        $this->line("   ✓ Đã tải " . count($this->allPool) . " bài viết khả dụng vào kho liên kết.");

        if (empty($this->allPool)) {
            $this->error('❌ Không tìm thấy bài viết published nào để tạo liên kết!');
            return 1;
        }

        // 2. Chuẩn bị query bài viết cần cập nhật
        $chunkSize = max(10, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));
        $domain = rtrim($this->option('domain') ?: 'https://nobifashion.vn', '/');
        $isPreview = (bool) $this->option('preview');

        $query = DB::table('posts')
            ->select('id', 'title', 'slug', 'category_id', 'content')
            ->whereNull('deleted_at');

        if ($targetId) {
            $query->where('id', $targetId);
        }

        // Phân bổ dữ liệu cho worker nếu đang chạy đa tiến trình
        if ($workerId !== null && $totalWorkers !== null && (int)$totalWorkers > 1) {
            $wId = (int) $workerId;
            $tWorkers = (int) $totalWorkers;
            $query->whereRaw('MOD(id, ?) = ?', [$tWorkers, $wId]);
        }

        $totalRecords = (clone $query)->count();
        if ($limit > 0 && $limit < $totalRecords) {
            $totalRecords = $limit;
        }

        $this->info("🔍 Bắt đầu quét " . number_format($totalRecords) . " bài viết (Batch size: {$chunkSize})...");

        $processedCount = 0;
        $updatedCount = 0;
        $totalLinksInserted = 0;
        $figureCount = 0;
        $h2Count = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - Đã chèn: %links% links - %memory%');
        $bar->setMessage('0', 'links');
        $bar->setMessage($this->formatMemory(memory_get_usage(true)), 'memory');
        $bar->start();

        // Xử lý theo chunkById để giải phóng RAM triệt để
        $query->orderBy('id')->chunkById($chunkSize, function ($posts) use (
            &$processedCount,
            &$updatedCount,
            &$totalLinksInserted,
            &$figureCount,
            &$h2Count,
            &$skippedCount,
            &$errorCount,
            $limit,
            $domain,
            $isDryRun,
            $isPreview,
            $bar
        ) {
            foreach ($posts as $post) {
                if ($limit > 0 && $processedCount >= $limit) {
                    return false; // Dừng chunkById
                }

                $processedCount++;

                try {
                    $originalContent = $post->content ?? '';
                    if (trim($originalContent) === '') {
                        $skippedCount++;
                        $bar->advance();
                        continue;
                    }

                    $result = $this->processPostContent($post, $originalContent, $domain);

                    if ($result['inserted_links'] > 0) {
                        $updatedCount++;
                        $totalLinksInserted += $result['inserted_links'];

                        if ($result['used_strategy'] === 'figure') {
                            $figureCount++;
                        } else {
                            $h2Count++;
                        }

                        if (!$isDryRun) {
                            DB::table('posts')
                                ->where('id', $post->id)
                                ->update(['content' => $result['new_content']]);
                        }

                        if ($isPreview && $updatedCount <= 3) {
                            $this->line("");
                            $this->info("--- [PREVIEW BÀI VIẾT #{$post->id}: {$post->title}] ---");
                            $this->line("Chiến lược: " . ($result['used_strategy'] === 'figure' ? 'Dưới <figure>' : 'Trên <h2>'));
                            $this->line("Số link chèn: {$result['inserted_links']}");
                            $this->line("Các link chèn vào:\n" . implode("\n", $result['inserted_html']));
                            $this->line("--------------------------------------------------");
                        }
                    } else {
                        $skippedCount++;
                    }
                } catch (\Throwable $e) {
                    $errorCount++;
                    $this->warn("\n⚠️ Lỗi khi xử lý bài viết #{$post->id}: " . $e->getMessage());
                }

                $bar->setMessage((string)$totalLinksInserted, 'links');
                $bar->setMessage($this->formatMemory(memory_get_usage(true)), 'memory');
                $bar->advance();
            }

            // Giải phóng bộ nhớ sau mỗi batch
            unset($posts);
            gc_collect_cycles();
        });

        $bar->finish();
        $this->line("");

        $duration = round(microtime(true) - $startTime, 2);
        $peakMemory = $this->formatMemory(memory_get_peak_usage(true));

        $this->displaySummary([
            'Trạng thái' => $isDryRun ? 'DRY-RUN (Không thay đổi DB)' : 'THÀNH CÔNG (Đã lưu DB)',
            'Tổng bài viết quét' => number_format($processedCount),
            'Số bài chèn liên kết' => number_format($updatedCount),
            '  ├─ Chèn dưới <figure>' => number_format($figureCount),
            '  └─ Chèn trên <h2> (fallback)' => number_format($h2Count),
            'Số bài giữ nguyên/đã có link' => number_format($skippedCount),
            'Tổng internal links đã chèn' => number_format($totalLinksInserted),
            'Số lỗi ngoại lệ' => number_format($errorCount),
            'Thời gian thực thi' => "{$duration} giây",
            'Bộ nhớ RAM đỉnh điểm' => $peakMemory,
        ]);

        return $errorCount === 0 ? 0 : 1;
    }

    /**
     * Xử lý nội dung của một bài viết cụ thể
     *
     * @param object $post
     * @param string $content
     * @param string $domain
     * @return array
     */
    protected function processPostContent(object $post, string $content, string $domain): array
    {
        $figurePositions = $this->getTopLevelFigurePositions($content);
        $insertedLinks = 0;
        $insertedHtmlList = [];
        $usedStrategy = null;

        if (!empty($figurePositions)) {
            // Chiến lược 1: Bài viết CÓ <figure> -> Chèn dưới thẻ figure chưa có Xem thêm
            $usedStrategy = 'figure';
            $targetsToInsert = [];

            foreach ($figurePositions as $fig) {
                if (!$this->hasNextXemThemLink($content, $fig['end'])) {
                    $targetsToInsert[] = $fig['end'];
                }
            }

            if (!empty($targetsToInsert)) {
                $neededCount = count($targetsToInsert);
                $pickedPosts = $this->pickLinksForPost(
                    (int) $post->id,
                    $post->category_id ? (int) $post->category_id : null,
                    $neededCount,
                    $content
                );

                if (!empty($pickedPosts)) {
                    // Chèn từ dưới lên trên để không làm lệch offset
                    $reversedTargets = array_reverse($targetsToInsert);
                    $reversedPicked = array_reverse(array_slice($pickedPosts, 0, count($targetsToInsert)));

                    foreach ($reversedTargets as $idx => $endPos) {
                        if (!isset($reversedPicked[$idx])) continue;
                        $linkPost = $reversedPicked[$idx];
                        $linkSnippet = $this->formatInternalLink($linkPost['title'], $linkPost['slug'], $domain);

                        $content = substr_replace($content, $linkSnippet, $endPos, 0);
                        $insertedLinks++;
                        $insertedHtmlList[] = trim($linkSnippet);
                    }
                }
            }
        } else {
            // Chiến lược 2: Bài viết KHÔNG CÓ <figure> -> Chèn trên mỗi cặp <h2>
            $usedStrategy = 'h2';
            $h2Positions = $this->getH2Positions($content);
            $targetsToInsert = [];

            foreach ($h2Positions as $h2Pos) {
                if (!$this->hasPreviousXemThemLink($content, $h2Pos)) {
                    $targetsToInsert[] = $h2Pos;
                }
            }

            if (!empty($targetsToInsert)) {
                $neededCount = count($targetsToInsert);
                $pickedPosts = $this->pickLinksForPost(
                    (int) $post->id,
                    $post->category_id ? (int) $post->category_id : null,
                    $neededCount,
                    $content
                );

                if (!empty($pickedPosts)) {
                    // Chèn từ dưới lên trên để không làm lệch offset
                    $reversedTargets = array_reverse($targetsToInsert);
                    $reversedPicked = array_reverse(array_slice($pickedPosts, 0, count($targetsToInsert)));

                    foreach ($reversedTargets as $idx => $startPos) {
                        if (!isset($reversedPicked[$idx])) continue;
                        $linkPost = $reversedPicked[$idx];
                        $linkSnippet = $this->formatInternalLink($linkPost['title'], $linkPost['slug'], $domain);

                        $content = substr_replace($content, $linkSnippet . "\n", $startPos, 0);
                        $insertedLinks++;
                        $insertedHtmlList[] = trim($linkSnippet);
                    }
                }
            }
        }

        return [
            'new_content' => $content,
            'inserted_links' => $insertedLinks,
            'inserted_html' => $insertedHtmlList,
            'used_strategy' => $usedStrategy,
        ];
    }

    /**
     * Tìm tất cả vị trí các khối <figure> ngoài cùng (top-level figure), xử lý chính xác cả figure lồng nhau
     *
     * @param string $html
     * @return array Danh sách mảng ['start' => int, 'end' => int]
     */
    protected function getTopLevelFigurePositions(string $html): array
    {
        $ranges = [];
        $offset = 0;
        $length = strlen($html);

        while ($offset < $length) {
            if (!preg_match('/<figure\b[^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
                break;
            }

            $startPos = $match[0][1];
            $cursor = $startPos + strlen($match[0][0]);
            $depth = 1;

            while ($depth > 0 && $cursor < $length) {
                if (!preg_match('/(<\/?figure\b[^>]*>)/i', $html, $tagMatch, PREG_OFFSET_CAPTURE, $cursor)) {
                    // Nếu thẻ figure không đóng đàng hoàng, coi như đóng tại vị trí cursor
                    break;
                }

                $tagStr = $tagMatch[0][0];
                $tagPos = $tagMatch[0][1];
                $cursor = $tagPos + strlen($tagStr);

                if (str_starts_with(strtolower($tagStr), '</figure')) {
                    $depth--;
                    if ($depth === 0) {
                        $ranges[] = [
                            'start' => $startPos,
                            'end' => $cursor,
                        ];
                        $offset = $cursor;
                        break;
                    }
                } else {
                    $depth++;
                }
            }

            if ($depth > 0) {
                $offset = $cursor;
            }
        }

        return $ranges;
    }

    /**
     * Kiểm tra xem ngay sau thẻ </figure> có thẻ <p> chứa Xem thêm chưa
     *
     * @param string $html
     * @param int $endPos
     * @return bool
     */
    protected function hasNextXemThemLink(string $html, int $endPos): bool
    {
        $after = substr($html, $endPos, 600);
        // Tìm thẻ <p> đầu tiên ngay sau figure (bỏ qua khoảng trắng, xuống dòng)
        if (preg_match('/^\s*<p\b[^>]*>([\s\S]*?)<\/p>/iu', $after, $matches)) {
            $pContent = $matches[1];
            if (preg_match('/(?:&gt;&gt;&gt;|>>>)?\s*Xem thêm\s*:/iu', $pContent)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tìm tất cả vị trí mở của các thẻ <h2>
     *
     * @param string $html
     * @return array Danh sách offset start của từng thẻ <h2>
     */
    protected function getH2Positions(string $html): array
    {
        $positions = [];
        if (preg_match_all('/<h2\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $positions[] = $match[1];
            }
        }
        return $positions;
    }

    /**
     * Kiểm tra xem ngay trước thẻ <h2> đã có thẻ <p> chứa Xem thêm chưa
     *
     * @param string $html
     * @param int $h2StartPos
     * @return bool
     */
    protected function hasPreviousXemThemLink(string $html, int $h2StartPos): bool
    {
        $chunkSize = min(500, $h2StartPos);
        $beforeChunk = substr($html, $h2StartPos - $chunkSize, $chunkSize);

        // Kiểm tra xem thẻ <p> nằm ngay cuối trước <h2> có chứa "Xem thêm:" hay không
        if (preg_match('/<p\b[^>]*>([\s\S]*?)<\/p>\s*$/iu', $beforeChunk, $matches)) {
            $pContent = $matches[1];
            if (preg_match('/(?:&gt;&gt;&gt;|>>>)?\s*Xem thêm\s*:/iu', $pContent)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tạo định dạng HTML đoạn internal link theo đúng yêu cầu
     *
     * @param string $title
     * @param string $slug
     * @param string $domain
     * @return string
     */
    protected function formatInternalLink(string $title, string $slug, string $domain): string
    {
        $url = "{$domain}/blog/{$slug}";
        $cleanTitle = htmlspecialchars(trim(strip_tags($title)), ENT_QUOTES, 'UTF-8');

        return "\n<p>\n    &gt;&gt;&gt; Xem thêm: <a target=\"_blank\" rel=\"noopener noreferrer\" href=\"{$url}\">{$cleanTitle}</a>\n</p>";
    }

    /**
     * Tải trước ứng viên vào bộ nhớ RAM siêu gọn nhẹ
     */
    protected function loadCandidatePool(): void
    {
        // Chỉ lấy các trường tối thiểu cần thiết để siêu nhẹ RAM
        // Giới hạn pool tối đa 20,000 bài mới nhất nếu có hàng triệu bài để giữ RAM ở mức < 10MB
        $candidates = DB::table('posts')
            ->select('id', 'title', 'slug', 'category_id')
            ->where('status', 'published')
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit(20000)
            ->get();

        $this->categoryPool = [];
        $this->allPool = [];
        $this->globalLinkUsageCount = [];

        foreach ($candidates as $c) {
            $item = [
                'id' => (int) $c->id,
                'title' => (string) $c->title,
                'slug' => (string) $c->slug,
                'category_id' => $c->category_id ? (int) $c->category_id : null,
            ];

            $this->allPool[] = $item;
            $this->globalLinkUsageCount[(int) $c->id] = 0;

            if ($c->category_id) {
                $this->categoryPool[(int) $c->category_id][] = $item;
            }
        }
    }

    /**
     * Chọn N bài viết không trùng lặp:
     * - Ưu tiên cùng danh mục
     * - Nếu không đủ hoặc không có danh mục: lấy thêm từ các danh mục khác
     * - Không lấy chính bài viết hiện tại
     * - Các bài chọn trong cùng 1 bài bắt buộc phải khác nhau
     *
     * @param int $currentPostId
     * @param int|null $categoryId
     * @param int $neededCount
     * @param string $existingContent
     * @return array
     */
    protected function pickLinksForPost(
        int $currentPostId,
        ?int $categoryId,
        int $neededCount,
        string $existingContent = ''
    ): array {
        $selected = [];
        $usedSlugs = [];

        // 1. Quét các slug đã xuất hiện trong bài viết hiện tại để tránh trỏ trùng
        if (preg_match_all('/href=[\'"][^\'"]*\/blog\/([a-zA-Z0-9\-_]+)[\'"]/i', $existingContent, $matches)) {
            foreach ($matches[1] as $sl) {
                $usedSlugs[$sl] = true;
            }
        }

        // 2. Ưu tiên lấy từ cùng danh mục (áp dụng Random phân tán đều, xoay vòng)
        if ($categoryId && !empty($this->categoryPool[$categoryId])) {
            $categoryCandidates = $this->getRandomBalancedCandidates(
                $this->categoryPool[$categoryId],
                $currentPostId,
                $usedSlugs
            );

            foreach ($categoryCandidates as $cand) {
                $selected[] = $cand;
                $usedSlugs[$cand['slug']] = true;
                $this->globalLinkUsageCount[$cand['id']] = ($this->globalLinkUsageCount[$cand['id']] ?? 0) + 1;

                if (count($selected) >= $neededCount) {
                    return $selected;
                }
            }
        }

        // 3. Nếu thiếu bài: bù từ danh mục khác trong allPool (cũng Random phân tán đều, xoay vòng)
        $globalCandidates = $this->getRandomBalancedCandidates(
            $this->allPool,
            $currentPostId,
            $usedSlugs
        );

        foreach ($globalCandidates as $cand) {
            $selected[] = $cand;
            $usedSlugs[$cand['slug']] = true;
            $this->globalLinkUsageCount[$cand['id']] = ($this->globalLinkUsageCount[$cand['id']] ?? 0) + 1;

            if (count($selected) >= $neededCount) {
                return $selected;
            }
        }

        return $selected;
    }

    /**
     * Lọc và xáo trộn ngẫu nhiên các ứng viên theo thuật toán xoay vòng cân bằng (Round-robin Balanced Random)
     * Ưu tiên các bài viết ít được chèn link nhất trước, kết hợp shuffle ngẫu nhiên tuyệt đối trong từng nhóm.
     * Đảm bảo mọi bài viết đều được chọn ngẫu nhiên đều đặn, không có tình trạng bài nào cũng dùng cùng 1 internal link.
     *
     * @param array $candidates
     * @param int $currentPostId
     * @param array $usedSlugs
     * @return array
     */
    protected function getRandomBalancedCandidates(array $candidates, int $currentPostId, array $usedSlugs): array
    {
        $filtered = [];
        foreach ($candidates as $cand) {
            if ($cand['id'] === $currentPostId) continue;
            if (isset($usedSlugs[$cand['slug']])) continue;
            $filtered[] = $cand;
        }

        if (empty($filtered)) {
            return [];
        }

        // Nhóm ứng viên theo số lần đã được chọn làm internal link
        $groupedByUsage = [];
        foreach ($filtered as $cand) {
            $count = $this->globalLinkUsageCount[$cand['id']] ?? 0;
            $groupedByUsage[$count][] = $cand;
        }

        // Sắp xếp các nhóm từ ít được chèn nhất đến nhiều nhất
        ksort($groupedByUsage);

        $result = [];
        foreach ($groupedByUsage as $usageCount => $items) {
            // Xáo trộn ngẫu nhiên tuyệt đối bên trong nhóm có cùng độ ưu tiên
            shuffle($items);
            foreach ($items as $item) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Quản lý chạy đa tiến trình song song (Master process spawn N workers)
     */
    protected function runMultiWorkers(int $totalWorkers): int
    {
        $this->info("🚀 Đang khởi động {$totalWorkers} luồng worker chạy song song...");
        $startTime = microtime(true);

        $processes = [];
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');

        $baseArgs = [
            $phpBinary,
            $artisanPath,
            'post:insert-internal-links',
            "--total-workers={$totalWorkers}",
            "--chunk=" . $this->option('chunk'),
            "--domain=" . $this->option('domain'),
        ];

        if ($this->option('dry-run')) {
            $baseArgs[] = '--dry-run';
        }
        if ($this->option('force') || true) {
            $baseArgs[] = '--force';
        }
        if ($this->option('limit')) {
            $baseArgs[] = "--limit=" . $this->option('limit');
        }

        for ($i = 0; $i < $totalWorkers; $i++) {
            $cmd = array_merge($baseArgs, ["--worker-id={$i}", "--workers=1"]);
            $process = new Process($cmd);
            $process->setTimeout(null);
            $process->start();
            $processes[$i] = $process;
            $this->line("   ✓ Worker #{$i} đã khởi chạy (PID: " . $process->getPid() . ")");
        }

        $this->info("⏳ Đang đồng bộ xử lý các luồng dữ liệu song song...");

        // Chờ tất cả process hoàn tất và in output
        while (count($processes) > 0) {
            foreach ($processes as $i => $proc) {
                if (!$proc->isRunning()) {
                    $exitCode = $proc->getExitCode();
                    $output = trim($proc->getOutput());
                    if ($output) {
                        $this->line("\n[Worker #{$i} Output]:\n" . $output);
                    }
                    if ($exitCode !== 0) {
                        $this->error("❌ Worker #{$i} kết thúc với mã lỗi: {$exitCode}");
                        $errorOutput = $proc->getErrorOutput();
                        if ($errorOutput) {
                            $this->error($errorOutput);
                        }
                    } else {
                        $this->info("✨ Worker #{$i} đã hoàn thành thành công!");
                    }
                    unset($processes[$i]);
                }
            }
            usleep(200000); // 200ms
        }

        $totalDuration = round(microtime(true) - $startTime, 2);
        $this->info("🎉 Toàn bộ {$totalWorkers} luồng worker đã hoàn tất trong {$totalDuration} giây!");

        return 0;
    }

    /**
     * Định dạng dung lượng RAM đọc được
     */
    protected function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Hiển thị Banner mở đầu
     */
    protected function displayBanner(bool $isDryRun, ?string $workerId, ?string $totalWorkers): void
    {
        $workerInfo = ($workerId !== null) ? " (Worker #{$workerId}/{$totalWorkers})" : "";
        $mode = $isDryRun ? "CHẾ ĐỘ KIỂM TRA (DRY-RUN - KHÔNG LƯU DB)" : "CHẾ ĐỘ THỰC THI (CẬP NHẬT DATABASE)";

        $this->line("======================================================================");
        $this->info("   TỰ ĐỘNG CHÈN INTERNAL LINKS CHO BÀI VIẾT NOBI FASHION{$workerInfo}");
        $this->line("   Chế độ: <fg=" . ($isDryRun ? "yellow" : "green") . ";options=bold>{$mode}</>");
        $this->line("======================================================================");
    }

    /**
     * Hiển thị bảng tổng kết
     */
    protected function displaySummary(array $stats): void
    {
        $this->line("======================================================================");
        $this->info("                      BẢNG TỔNG KẾT KẾT QUẢ");
        $this->line("======================================================================");
        
        $rows = [];
        foreach ($stats as $key => $val) {
            $rows[] = [$key, $val];
        }
        $this->table(['Hạng mục', 'Số liệu'], $rows);
        $this->line("======================================================================");
    }
}
