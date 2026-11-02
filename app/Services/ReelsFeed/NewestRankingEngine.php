<?php

namespace App\Services\ReelsFeed;

use App\Models\Reel;

class NewestRankingEngine
{
    /**
     * Lấy danh sách Reel IDs mới nhất dựa trên published_at và id
     *
     * @return array Danh sách Reel IDs sắp xếp mới nhất
     */
    public function generateNewestIds(): array
    {
        return Reel::query()
            ->published()
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderByDesc('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }
}

