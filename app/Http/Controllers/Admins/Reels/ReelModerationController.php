<?php

namespace App\Http\Controllers\Admins\Reels;

use App\Http\Controllers\Controller;
use App\Models\CommentModerationLog;
use App\Models\CommentModerationRule;
use App\Models\CommentModerationWhitelist;
use App\Services\ReelsAdmin\ReelsAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReelModerationController extends Controller
{
    protected ReelsAdminService $adminService;

    public function __construct(ReelsAdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Trung tâm kiểm duyệt nội dung (Rules, Whitelist, Logs, Tester)
     */
    public function index(Request $request): View
    {
        $activeTab = $request->input('tab', 'rules');

        // Tab 1: Rules
        $rules = CommentModerationRule::orderBy('id', 'desc')->get();

        // Tab 2: Whitelist
        $whitelists = CommentModerationWhitelist::orderBy('id', 'desc')->get();

        // Tab 3: Logs
        $logs = CommentModerationLog::with(['anonymousUser', 'rule', 'comment'])
            ->latest('id')
            ->paginate(20, ['*'], 'logs_page');

        // Thống kê KPI kiểm duyệt
        $counts = [
            'total_rules' => $rules->count(),
            'active_rules' => $rules->where('is_active', true)->count(),
            'block_rules' => $rules->where('action', 'block')->count(),
            'review_rules' => $rules->where('action', 'review')->count(),
            'whitelists' => $whitelists->count(),
            'logs' => CommentModerationLog::count(),
        ];

        return view('admins.reels.moderation.index', compact('activeTab', 'rules', 'whitelists', 'logs', 'counts'));
    }

    /**
     * Thêm quy tắc kiểm duyệt mới
     */
    public function storeRule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pattern' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'in:profanity,link,harassment,spam,custom'],
            'language' => ['required', 'in:vi,en,all'],
            'severity' => ['required', 'in:low,medium,high'],
            'action' => ['required', 'in:block,review,warn'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule = CommentModerationRule::create([
            'pattern' => trim($validated['pattern']),
            'rule_type' => $validated['rule_type'],
            'language' => $validated['language'],
            'severity' => $validated['severity'],
            'action' => $validated['action'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->adminService->logAudit('create_moderation_rule', [
            'rule_id' => $rule->id,
            'pattern' => $rule->pattern,
            'action' => $rule->action,
        ]);

        return redirect()->route('admin.reels.moderation.index', ['tab' => 'rules'])
            ->with('success', "Đã thêm quy tắc kiểm duyệt: \"{$rule->pattern}\"");
    }

    /**
     * Cập nhật quy tắc kiểm duyệt
     */
    public function updateRule(Request $request, int $id): RedirectResponse
    {
        $rule = CommentModerationRule::findOrFail($id);

        $validated = $request->validate([
            'pattern' => ['required', 'string', 'max:255'],
            'rule_type' => ['required', 'in:profanity,link,harassment,spam,custom'],
            'language' => ['required', 'in:vi,en,all'],
            'severity' => ['required', 'in:low,medium,high'],
            'action' => ['required', 'in:block,review,warn'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule->update([
            'pattern' => trim($validated['pattern']),
            'rule_type' => $validated['rule_type'],
            'language' => $validated['language'],
            'severity' => $validated['severity'],
            'action' => $validated['action'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->adminService->logAudit('update_moderation_rule', [
            'rule_id' => $id,
            'pattern' => $rule->pattern,
        ]);

        return redirect()->route('admin.reels.moderation.index', ['tab' => 'rules'])
            ->with('success', 'Đã cập nhật quy tắc kiểm duyệt thành công.');
    }

    /**
     * Bật/tắt quy tắc kiểm duyệt
     */
    public function toggleRule(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $rule = CommentModerationRule::findOrFail($id);
        $rule->is_active = !$rule->is_active;
        $rule->save();

        $this->adminService->logAudit('toggle_moderation_rule', [
            'rule_id' => $id,
            'is_active' => $rule->is_active,
        ]);

        $msg = $rule->is_active ? 'Đã kích hoạt quy tắc.' : 'Đã vô hiệu hóa quy tắc.';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg, 'is_active' => $rule->is_active]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Xóa quy tắc kiểm duyệt
     */
    public function destroyRule(int $id): RedirectResponse
    {
        $rule = CommentModerationRule::findOrFail($id);
        $pattern = $rule->pattern;
        $rule->delete();

        $this->adminService->logAudit('delete_moderation_rule', ['rule_id' => $id, 'pattern' => $pattern]);

        return redirect()->route('admin.reels.moderation.index', ['tab' => 'rules'])
            ->with('success', "Đã xóa quy tắc \"{$pattern}\".");
    }

    /**
     * Thêm từ khóa vào danh sách Whitelist
     */
    public function storeWhitelist(Request $request): RedirectResponse
    {
        $word = trim((string) ($request->input('word') ?: $request->input('term', '')));
        $note = trim((string) ($request->input('note') ?: $request->input('reason', '')));

        if (empty($word)) {
            return back()->with('error', 'Vui lòng nhập cụm từ ngoại lệ.');
        }

        if (CommentModerationWhitelist::where('word', $word)->exists()) {
            return back()->with('error', "Cụm từ \"{$word}\" đã có trong danh sách ngoại lệ.");
        }

        $entry = CommentModerationWhitelist::create([
            'word' => $word,
            'note' => $note ?: null,
        ]);

        $this->adminService->logAudit('create_whitelist_entry', ['word' => $entry->word]);

        return redirect()->route('admin.reels.moderation.index', ['tab' => 'whitelist'])
            ->with('success', "Đã thêm từ \"{$entry->word}\" vào danh sách ngoại lệ.");
    }

    /**
     * Xóa từ khỏi Whitelist
     */
    public function destroyWhitelist(int $id): RedirectResponse
    {
        $entry = CommentModerationWhitelist::findOrFail($id);
        $word = $entry->word;
        $entry->delete();

        $this->adminService->logAudit('delete_whitelist_entry', ['word' => $word]);

        return redirect()->route('admin.reels.moderation.index', ['tab' => 'whitelist'])
            ->with('success', "Đã xóa \"{$word}\" khỏi danh sách ngoại lệ.");
    }

    /**
     * Khởi tạo bộ quy tắc kiểm duyệt mẫu tiếng Việt chuẩn cho thời trang
     */
    public function seedPresets(Request $request): RedirectResponse
    {
        $presetRules = [
            // Link & Spam lừa đảo
            ['pattern' => 'https?://', 'rule_type' => 'link', 'language' => 'all', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'zalo.me', 'rule_type' => 'link', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 't.me/', 'rule_type' => 'link', 'language' => 'all', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'bit.ly', 'rule_type' => 'link', 'language' => 'all', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => '/\\b(0|\\+84)(3|5|7|8|9)[0-9]{8}\\b/', 'rule_type' => 'spam', 'language' => 'vi', 'severity' => 'medium', 'action' => 'review'],
            // Từ ngữ thô tục / Nhạy cảm
            ['pattern' => 'đụ', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'đm', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'dcm', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'địt', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'vcl', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'medium', 'action' => 'review'],
            ['pattern' => 'lừa đảo', 'rule_type' => 'harassment', 'language' => 'vi', 'severity' => 'high', 'action' => 'review'],
            ['pattern' => 'đồ giả', 'rule_type' => 'harassment', 'language' => 'vi', 'severity' => 'medium', 'action' => 'review'],
            ['pattern' => 'hàng fake', 'rule_type' => 'harassment', 'language' => 'vi', 'severity' => 'medium', 'action' => 'review'],
            ['pattern' => 'buồi', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
            ['pattern' => 'cặc', 'rule_type' => 'profanity', 'language' => 'vi', 'severity' => 'high', 'action' => 'block'],
        ];

        $presetWhitelists = [
            ['word' => 'quần què', 'note' => 'Thuật ngữ địa phương / Phong cách thời trang'],
            ['word' => 'đầm xòe', 'note' => 'Thiết kế váy đầm'],
            ['word' => 'chất lừ', 'note' => 'Khen trang phục đẹp'],
        ];

        $addedRules = 0;
        foreach ($presetRules as $item) {
            $exists = CommentModerationRule::where('pattern', $item['pattern'])->exists();
            if (!$exists) {
                CommentModerationRule::create(array_merge($item, ['is_active' => true]));
                $addedRules++;
            }
        }

        $addedWl = 0;
        foreach ($presetWhitelists as $item) {
            $exists = CommentModerationWhitelist::where('word', $item['word'])->exists();
            if (!$exists) {
                CommentModerationWhitelist::create($item);
                $addedWl++;
            }
        }

        return redirect()->route('admin.reels.moderation.index', ['tab' => 'rules'])
            ->with('success', "Đã nạp thành công {$addedRules} quy tắc kiểm duyệt và {$addedWl} từ khóa ngoại lệ chuẩn tiếng Việt!");
    }

    /**
     * Công cụ tương tác: Kiểm tra thử một đoạn bình luận với các rules hiện hành
     */
    public function testRule(Request $request): JsonResponse
    {
        $text = trim((string) ($request->input('text') ?: $request->input('content', '')));
        if (empty($text)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập nội dung bình luận để kiểm tra.',
            ]);
        }

        $lowerText = mb_strtolower($text, 'UTF-8');

        // 1. Kiểm tra Whitelist trước
        $whitelists = CommentModerationWhitelist::pluck('word')->toArray();
        $matchedWhitelists = [];
        foreach ($whitelists as $w) {
            if (!empty($w) && mb_stripos($lowerText, mb_strtolower($w, 'UTF-8')) !== false) {
                $matchedWhitelists[] = $w;
            }
        }

        // 2. Kiểm tra Rules đang active
        $rules = CommentModerationRule::where('is_active', true)->get();
        $matchedRules = [];
        $finalAction = 'allowed';
        $highestSeverity = 'none';

        foreach ($rules as $rule) {
            $pattern = trim($rule->pattern);
            if (empty($pattern)) continue;

            $isMatched = false;

            // Nếu rule bắt đầu bằng / và kết thúc bằng / thì chạy regex an toàn
            if (str_starts_with($pattern, '/') && str_ends_with($pattern, '/')) {
                try {
                    $isMatched = (bool) preg_match($pattern . 'iu', $text);
                } catch (\Throwable $e) {}
            } else {
                $isMatched = (mb_stripos($lowerText, mb_strtolower($pattern, 'UTF-8')) !== false);
            }

            if ($isMatched) {
                // Kiểm tra có được bảo vệ bởi whitelist không
                $whitelisted = false;
                foreach ($matchedWhitelists as $wl) {
                    if (mb_stripos(mb_strtolower($wl, 'UTF-8'), mb_strtolower($pattern, 'UTF-8')) !== false) {
                        $whitelisted = true;
                        break;
                    }
                }

                $matchedRules[] = [
                    'id' => $rule->id,
                    'pattern' => $rule->pattern,
                    'rule_type' => $rule->rule_type,
                    'severity' => $rule->severity,
                    'action' => $rule->action,
                    'whitelisted' => $whitelisted,
                ];

                if (!$whitelisted) {
                    if ($rule->action === 'block') {
                        $finalAction = 'blocked';
                    } elseif ($rule->action === 'review' && $finalAction !== 'blocked') {
                        $finalAction = 'sent_to_review';
                    } elseif ($rule->action === 'warn' && $finalAction === 'allowed') {
                        $finalAction = 'warn';
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'input_text' => $text,
            'is_valid' => $finalAction === 'allowed',
            'action_result' => $finalAction,
            'matched_rules_count' => count($matchedRules),
            'matched_rules' => $matchedRules,
            'matched_whitelists' => $matchedWhitelists,
            'explanation' => match ($finalAction) {
                'blocked' => 'Nội dung chứa từ khóa vi phạm mức độ cao và sẽ bị CHẶN NGAY LẬP TỨC.',
                'sent_to_review' => 'Nội dung chứa từ khóa nghi vấn và sẽ được ĐƯA VÀO HÀNG ĐỢI DUYỆT (Pending).',
                'warn' => 'Nội dung hợp lệ có cảnh báo nhẹ, vẫn được đăng bình thường.',
                default => 'Nội dung hoàn toàn hợp lệ, đạt tiêu chuẩn cộng đồng.',
            },
        ]);
    }
}

