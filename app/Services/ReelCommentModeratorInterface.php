<?php

namespace App\Services;

interface ReelCommentModeratorInterface
{
    /**
     * Kiểm duyệt nội dung bình luận
     * 
     * @param string $content
     * @param array $context [anonymous_user_id, ip, comment_type, sticker_id]
     * @return array [
     *     'decision' => 'ALLOW'|'BLOCK'|'REVIEW',
     *     'reason' => string|null,
     *     'flagged_word' => string|null,
     *     'user_message' => string|null
     * ]
     */
    public function moderate(string $content, array $context = []): array;
}

