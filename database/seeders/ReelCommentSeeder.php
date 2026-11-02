<?php

namespace Database\Seeders;

use App\Models\AnonymousUser;
use App\Models\CommentSticker;
use App\Models\Reel;
use App\Models\ReelComment;
use App\Services\AnonymousNicknameGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReelCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Bắt đầu khởi tạo dữ liệu bình luận mẫu cho toàn bộ Reels...');

        // 1. Đảm bảo có sẵn một pool Anonymous Users đa dạng (khoảng 150 người)
        $this->ensureAnonymousUsersPool(150);
        $anonUsers = AnonymousUser::where('status', 'active')->where('is_banned', false)->get();
        if ($anonUsers->isEmpty()) {
            $this->command->error('Không tìm thấy Anonymous Users!');
            return;
        }

        // 2. Lấy danh sách Stickers
        $stickers = CommentSticker::where('is_active', true)->get();
        $stickerIds = $stickers->pluck('id')->all();

        // 3. Chuẩn bị kho dữ liệu câu hỏi / bình luận phong phú
        $rootTemplates = [
            // Kích cỡ & Phom dáng
            'Mẫu cao mét bao nhiêu mặc size gì vậy shop ơi? Nhìn form ôm đẹp quá!',
            'Mình 1m73 nặng 65kg thì mặc size L có bị rộng quá không ạ? Tư vấn giúp mình với.',
            'Cao 1m68 nặng 60kg mặc form rộng xíu thì lấy size nào shop ơi? 🔥',
            'Ai mua rồi cho mình hỏi form này là oversize hay regular fit vậy mọi người?',
            'Bạn nữ 1m58 48kg mặc ké của người yêu size M ổn không nhỉ 😂 Cute xỉu!',
            '1m78 74kg vai rộng mặc XL có ôm nách không shop? Thích kiểu vải này quá.',
            'Mình thích mặc phủ mông một chút thì nên up 1 size không ạ?',
            'Bảng size chuẩn không mọi người, mình sợ bị chật ngực khi vận động mạnh.',
            'Shop tư vấn giúp mình 1m70 62kg thích mặc vừa vặn tôn dáng nha ❤️',
            'Cổ áo có bị chật không ad? Mình hay bị kích vòng cổ mấy mẫu thun khác.',
            'Độ dài áo chạm gối hay trên gối vậy bạn mẫu?',
            'Dáng người hơi đậm một chút mặc màu này có bị lộ khuyết điểm không shop?',

            // Khen ngợi & Đánh giá chất lượng
            'Chất vải sờ thích cực kỳ luôn, mặc mát rượi mát rượi, đi nắng không hề bí 👍',
            'Màu này ở ngoài đẹp hơn trên video nhiều luôn á mọi người, phối đồ siêu sang!',
            'Vừa nhận hàng hồi chiều, đường may tỉ mỉ đóng gói xịn sò 10/10.',
            'Phối đồ đỉnh chóp thật sự, nhìn trẻ trung năng động hẳn ra 😍',
            'Mua lần thứ 3 của Nobi rồi, chưa bao giờ thất vọng về chất lượng luôn.',
            'Form áo cứng cáp giặt máy 2 lần rồi không thấy bị dão cổ hay bai màu.',
            'Màu basic dễ phối đồ dã man, mặc đi làm hay đi cà phê cuối tuần đều hợp lý.',
            'Hàng đẹp xịn xò, săn được giá sale thế này quá hời luôn ạ 🔥 Quá cháy!',
            'Chất cotton mát mịn, thấm hút mồ hôi tốt cho những ngày nắng nóng.',
            'Thiết kế tối giản mà sang xịn mịn ghê, 100 điểm không có nhưng!',
            'Đóng gói cẩn thận có cả túi thơm, mở hộp ra là ưng cái bụng liền ✨',
            'Mặc lên ai cũng khen đẹp, bạn bè cứ hỏi mua ở đâu hoài haha.',
            'Hàng hiệu giá Việt Nam, chất lượng không thua gì mấy brand quốc tế.',
            'Màu sắc chuẩn hình, không bị lệch tone hay bạc màu nha anh em.',

            // Tư vấn phối đồ (OOTD)
            'Áo này phối với quần jean ống suông hay jogger thì cháy hơn shop?',
            'Mix cùng giày sneaker trắng là chuẩn bài phong cách đường phố luôn rồi.',
            'Màu này kết hợp với short đen đi biển hoặc dạo phố hợp lý không ad ơi?',
            'Có set đồ nào mặc đôi với bạn gái cùng tone màu này không shop?',
            'Áo khoác ngoài nào hợp với set này nhất vậy các bác? Xin gợi ý với ạ.',
            'Set đồ nhìn cuốn quá, nhìn mẫu phối là muốn rinh ngay cả cây về tủ.',
            'Đi làm văn phòng mặc kiểu này nhìn lịch sự mà vẫn thoải mái năng động ghê.',
            'Thêm một chiếc túi đeo chéo nhỏ nữa là chuẩn outfit xuống phố cuối tuần.',

            // Mua sắm & Giao nhận
            'Hà Nội đặt hôm nay thì tầm mấy ngày nhận được hàng vậy shop?',
            'Shop có hỗ trợ đổi size tận nơi nếu mặc không vừa không ạ?',
            'Còn mã giảm giá hay freeship gì cho đơn đầu tiên không shop ơi?',
            'Vừa chốt 2 màu đen với be xong, hóng hàng về từng ngày luôn á.',
            'Giao hàng nhanh quá, hôm qua đặt trưa nay shipper đã gọi nhận rồi.',
            'Mẫu này còn đủ size không shop ơi để em qua store thử trực tiếp?',
            'Bỏ vào giỏ hàng rồi chờ lương về là múc liền tay 😍 Đẹp mê ly!',
            'Có chương trình tặng kèm sticker hay quà tặng gì trong BST này không shop?',
            'Đã nhận hàng, chất lượng tuyệt vời ông mặt trời 👏',
            'Bấm đặt hàng ngay kẻo cháy hàng size L, mẫu này hot quá!',
        ];

        $replyTemplates = [
            'Dạ Nobi Fashion chào bạn ạ! Bạn chọn size L là ôm vừa vặn tôn dáng chuẩn nhé ❤️',
            'Dạ shop hỗ trợ đổi size miễn phí tận nhà trong 30 ngày nên bạn cứ yên tâm trải nghiệm nha!',
            'Chuẩn luôn bác ơi, mình mua rồi giặt máy thoải mái không xù lông hay bai dão đâu.',
            'Nên up lên 1 size nhé bạn, form mặc rộng xíu nhìn cá tính và năng động hơn nhiều.',
            'Giao hoả tốc nội thành chỉ 2 tiếng là tới nơi bạn nhé!',
            'Mình cũng mua màu be này rồi, mặc lên sáng da cực kỳ luôn á.',
            'Hàng chuẩn chỉ lắm bạn ơi, quất ngay đi không hết đợt sale này đó 🔥',
            'Cảm ơn bạn đã luôn tin tưởng và ủng hộ Nobi Fashion ạ!',
            'Mix cùng quần jean ống suông trắng hoặc đen là bao ngầu luôn bạn nha.',
            'Chất này mặc mùa hè hay mùa thu đều bao mát mẻ thoải mái luôn.',
            'Dạ mẫu trên clip cao 1m78 nặng 70kg đang diện size L bạn nhé!',
            'Form này regular fit hơi suông nhẹ, mặc cực tôn dáng ngực và vai nha bạn.',
            'Đồng ý với bác, mình cũng vừa nhận hàng hôm qua ưng ý hết nấc!',
        ];

        // 4. Lấy toàn bộ Reels
        $reels = Reel::orderBy('id')->get();
        $totalReels = $reels->count();
        $this->command->info("Tìm thấy {$totalReels} reels cần xử lý.");

        $totalInserted = 0;

        foreach ($reels as $index => $reel) {
            $targetCount = (int) $reel->comment_count;
            if ($targetCount <= 0) {
                // Nếu chưa có số lượng comment, gán mục tiêu ngẫu nhiên từ 15 đến 55
                $targetCount = rand(15, 55);
            }

            // Kiểm tra số comment hiện có
            $currentCount = ReelComment::where('reel_id', $reel->id)->count();
            $needed = $targetCount - $currentCount;

            if ($needed <= 0) {
                continue;
            }

            // Tính số lượng Root comments (~70%) và Replies (~30%)
            $numRoot = max(1, (int) round($needed * 0.72));
            $numReplies = max(0, $needed - $numRoot);

            // Thời gian cơ sở: rải rác từ 1 đến 20 ngày trước
            $baseTime = Carbon::now()->subDays(rand(1, 20))->subHours(rand(1, 23));

            $createdRootIds = [];

            // A. Tạo Root Comments
            for ($i = 0; $i < $numRoot; $i++) {
                $user = $anonUsers->random();
                $isSticker = (!empty($stickerIds) && rand(1, 100) <= 12);
                $stickerId = $isSticker ? $stickerIds[array_rand($stickerIds)] : null;
                $commentTime = (clone $baseTime)->addMinutes(rand(5, 1440));

                $text = $isSticker
                    ? $this->getStickerCaption($stickerId)
                    : $rootTemplates[array_rand($rootTemplates)];

                $commentId = DB::table('reel_comments')->insertGetId([
                    'reel_id' => $reel->id,
                    'user_id' => null,
                    'anonymous_user_id' => $user->id,
                    'parent_id' => null,
                    'author_name' => $user->nickname,
                    'content' => $text,
                    'comment_type' => $isSticker ? 'STICKER' : 'TEXT',
                    'sticker_id' => $stickerId,
                    'likes_count' => rand(0, 38),
                    'replies_count' => 0,
                    'status' => 'approved',
                    'created_at' => $commentTime,
                    'updated_at' => $commentTime,
                    'deleted_at' => null,
                ]);

                $createdRootIds[] = $commentId;
                $totalInserted++;
            }

            // B. Tạo Replies cho các Root Comments vừa tạo
            if ($numReplies > 0 && !empty($createdRootIds)) {
                $rootReplyMap = [];

                for ($j = 0; $j < $numReplies; $j++) {
                    $parentId = $createdRootIds[array_rand($createdRootIds)];
                    $user = $anonUsers->random();
                    $isSticker = (!empty($stickerIds) && rand(1, 100) <= 8);
                    $stickerId = $isSticker ? $stickerIds[array_rand($stickerIds)] : null;
                    $replyTime = (clone $baseTime)->addMinutes(rand(60, 2800));

                    $replyText = $isSticker
                        ? '❤️'
                        : $replyTemplates[array_rand($replyTemplates)];

                    DB::table('reel_comments')->insert([
                        'reel_id' => $reel->id,
                        'user_id' => null,
                        'anonymous_user_id' => $user->id,
                        'parent_id' => $parentId,
                        'author_name' => $user->nickname,
                        'content' => $replyText,
                        'comment_type' => $isSticker ? 'STICKER' : 'TEXT',
                        'sticker_id' => $stickerId,
                        'likes_count' => rand(0, 15),
                        'replies_count' => 0,
                        'status' => 'approved',
                        'created_at' => $replyTime,
                        'updated_at' => $replyTime,
                        'deleted_at' => null,
                    ]);

                    $rootReplyMap[$parentId] = ($rootReplyMap[$parentId] ?? 0) + 1;
                    $totalInserted++;
                }

                // Cập nhật replies_count cho các root comment
                foreach ($rootReplyMap as $pId => $rCount) {
                    DB::table('reel_comments')->where('id', $pId)->increment('replies_count', $rCount);
                }
            }

            if (($index + 1) % 50 === 0 || $index === $totalReels - 1) {
                $curr = $index + 1;
                $progress = round(($curr / $totalReels) * 100);
                $this->command->line("Tiến độ: {$progress}% ({$curr}/{$totalReels} reels đã xử lý)");
            }
        }

        $this->command->info("Đã tạo thêm {$totalInserted} bình luận mẫu thực tế vào bảng reel_comments.");

        // 5. ĐỒNG BỘ 100% CHUẨN XÁC:
        // Cập nhật lại cột comment_count trong bảng reels chính xác bằng số lượng bản ghi trong reel_comments
        $this->command->info('Đang đồng bộ chính xác comment_count của toàn bộ video trong bảng reels...');
        
        DB::statement("
            UPDATE reels r
            SET r.comment_count = (
                SELECT COUNT(*)
                FROM reel_comments rc
                WHERE rc.reel_id = r.id
                  AND rc.status = 'approved'
                  AND rc.deleted_at IS NULL
            )
        ");

        $this->command->info('ĐỒNG BỘ HOÀN TẤT 100%!');
    }

    /**
     * Tạo sẵn một tập Anonymous Users đa dạng nếu chưa đủ
     */
    protected function ensureAnonymousUsersPool(int $targetCount): void
    {
        $current = AnonymousUser::count();
        if ($current >= $targetCount) {
            return;
        }

        $needed = $targetCount - $current;
        $this->command->line("Đang tạo bổ sung {$needed} Anonymous Users...");

        for ($i = 0; $i < $needed; $i++) {
            $rawToken = Str::random(64);
            $tokenHash = hash('sha256', $rawToken);
            $meta = AnonymousNicknameGenerator::generateUniqueNickname();

            AnonymousUser::create([
                'token_hash' => $tokenHash,
                'public_id' => 'anon_' . Str::lower(Str::random(16)),
                'nickname' => $meta['nickname'],
                'avatar_key' => $meta['avatar_key'],
                'avatar_color' => $meta['avatar_color'],
                'status' => 'active',
                'is_banned' => false,
                'last_seen_at' => now()->subMinutes(rand(1, 10000)),
            ]);
        }
    }

    /**
     * Mô tả sticker ngắn gọn vui nhộn
     */
    protected function getStickerCaption(?int $stickerId): string
    {
        return match ($stickerId) {
            1 => 'Thả tim nè shop ơi ❤️',
            2 => 'Chào cả nhà nha 👋',
            3 => 'Quẩy lên nào ae 💃',
            4 => 'Nháy mắt cái nè 😉',
            5 => 'Cháy quá cháy luôn 🔥',
            6 => 'Lung linh lấp lánh ✨',
            7 => 'Hài hước quá trời haha 😂',
            8 => 'Đỉnh chóp luôn shop 🤯',
            9 => 'Outfit 10/10 đẹp mê ly 👗',
            10 => 'Chốt đơn liền tay thôi 🛍️',
            11 => '100 điểm không có nhưng 💯',
            12 => 'Form dáng chuẩn quá xá ✨',
            default => 'Tuyệt vời quá!',
        };
    }
}
