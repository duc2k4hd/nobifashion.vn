<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TagService
{
    /**
     * Tạo tag mới
     */
    public function create(array $data): Tag
    {
        return DB::transaction(function () use ($data) {
            // Tạo slug nếu chưa có
            if (empty($data['slug'])) {
                $data['slug'] = Tag::generateSlug($data['name']);
            } else {
                // Validate slug unique
                $data['slug'] = Tag::generateSlug($data['slug']);
            }

            $tag = Tag::create($data);

            // Tăng usage_count cho entity nếu có
            if ($tag->entity_id && $tag->entity_type) {
                $this->updateEntityUsageCount($tag->entity_type, $tag->entity_id);
            }

            return $tag;
        });
    }

    /**
     * Cập nhật tag
     */
    public function update(Tag $tag, array $data): Tag
    {
        return DB::transaction(function () use ($tag, $data) {
            $oldEntityType = $tag->entity_type;
            $oldEntityId = $tag->entity_id;

            // Xử lý slug nếu thay đổi name
            if (isset($data['name']) && $data['name'] !== $tag->name) {
                if (empty($data['slug']) || $data['slug'] === $tag->slug) {
                    $data['slug'] = Tag::generateSlug($data['name'], $tag->id);
                }
            }

            // Nếu thay đổi entity, cập nhật usage_count
            if (isset($data['entity_type']) || isset($data['entity_id'])) {
                $newEntityType = $data['entity_type'] ?? $tag->entity_type;
                $newEntityId = $data['entity_id'] ?? $tag->entity_id;

                if ($oldEntityType !== $newEntityType || $oldEntityId !== $newEntityId) {
                    // Giảm usage_count cho entity cũ
                    if ($oldEntityType && $oldEntityId) {
                        $this->updateEntityUsageCount($oldEntityType, $oldEntityId);
                    }
                    // Tăng usage_count cho entity mới
                    if ($newEntityType && $newEntityId) {
                        $this->updateEntityUsageCount($newEntityType, $newEntityId);
                    }
                }
            }

            $tag->update($data);

            return $tag->fresh();
        });
    }

    /**
     * Xóa tag (soft delete)
     */
    public function delete(Tag $tag): bool
    {
        return DB::transaction(function () use ($tag) {
            // Giảm usage_count cho entity
            if ($tag->entity_type && $tag->entity_id) {
                $this->updateEntityUsageCount($tag->entity_type, $tag->entity_id);
            }

            return $tag->delete();
        });
    }

    /**
     * Xóa hàng loạt
     */
    public function deleteMultiple(array $ids): int
    {
        $deleted = 0;
        
        DB::transaction(function () use ($ids, &$deleted) {
            $tags = Tag::whereIn('id', $ids)->get();
            
            foreach ($tags as $tag) {
                if ($this->delete($tag)) {
                    $deleted++;
                }
            }
        });

        return $deleted;
    }

    /**
     * Gộp tags (merge)
     */
    public function merge(Tag $sourceTag, Tag $targetTag): Tag
    {
        return DB::transaction(function () use ($sourceTag, $targetTag) {
            // Di chuyển tất cả entity từ source sang target
            // (Trong trường hợp này, mỗi tag chỉ gắn với 1 entity, nên chỉ cần cập nhật)
            
            // Nếu target tag chưa có entity, lấy từ source
            if (!$targetTag->entity_id && $sourceTag->entity_id) {
                $targetTag->update([
                    'entity_id' => $sourceTag->entity_id,
                    'entity_type' => $sourceTag->entity_type,
                ]);
            }

            // Cập nhật usage_count
            $this->updateEntityUsageCount($targetTag->entity_type, $targetTag->entity_id);
            
            // Xóa source tag
            $this->delete($sourceTag);

            return $targetTag->fresh();
        });
    }

    /**
     * Gợi ý tags dựa trên keyword
     */
    public function suggest(string $keyword, ?string $entityType = null, int $limit = 10): array
    {
        $query = Tag::query();

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        $tags = $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('slug', 'like', "%{$keyword}%");
            })
            ->active()
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get();

        return $tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'entity_type' => $tag->entity_type,
                'usage_count' => $tag->usage_count,
            ];
        })->toArray();
    }

    /**
     * Gợi ý tags dựa trên content (AI-suggest nâng cao)
     */
    public function suggestFromContent(string $content, ?string $entityType = null, int $limit = 5): array
    {
        // 1. Làm sạch content
        $content = strip_tags($content);
        $content = mb_strtolower($content, 'UTF-8');
        
        // 2. Loại bỏ stop words (từ phổ biến không có ý nghĩa)
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with',
            'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had',
            'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must',
            'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
            'what', 'which', 'who', 'when', 'where', 'why', 'how', 'can', 'cannot',
            'và', 'của', 'với', 'cho', 'từ', 'về', 'trong', 'trên', 'dưới', 'sau', 'trước',
            'là', 'có', 'được', 'sẽ', 'đã', 'đang', 'này', 'đó', 'khi', 'nếu', 'thì',
        ];
        
        // 3. Extract keywords với nhiều chiến lược
        $keywords = $this->extractKeywords($content, $stopWords);
        
        // 4. Tìm tags khớp với keywords
        $suggestedTags = [];
        $matchedTagIds = [];
        
        foreach ($keywords as $keyword => $weight) {
            // Tìm tags có tên hoặc slug chứa keyword
            $query = Tag::query()->active();
            
            if ($entityType) {
                if ($entityType === 'product') {
                    $query->where(function($q) {
                        $q->where('entity_type', \App\Models\Product::class)
                          ->orWhere('entity_type', 'product');
                    });
                } elseif ($entityType === 'post') {
                    $query->where(function($q) {
                        $q->where('entity_type', \App\Models\Post::class)
                          ->orWhere('entity_type', 'post');
                    });
                } else {
                    $query->where('entity_type', $entityType);
                }
            }
            
            $tags = $query->where(function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('slug', 'like', "%{$keyword}%");
                })
                ->orderByDesc('usage_count')
                ->limit(5)
                ->get();
            
            foreach ($tags as $tag) {
                if (!in_array($tag->id, $matchedTagIds)) {
                    $matchedTagIds[] = $tag->id;
                    $suggestedTags[] = [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'entity_type' => $tag->entity_type,
                        'usage_count' => $tag->usage_count,
                        'relevance_score' => $weight * ($tag->usage_count / 100 + 1), // Tính điểm relevance
                    ];
                }
            }
        }
        
        // 5. Sort theo relevance score và usage_count
        $uniqueTags = collect($suggestedTags)
            ->unique('id')
            ->sortByDesc(function($tag) {
                return $tag['relevance_score'] * log($tag['usage_count'] + 1);
            })
            ->take($limit)
            ->map(function($tag) {
                unset($tag['relevance_score']);
                return $tag;
            })
            ->values()
            ->toArray();

        return $uniqueTags;
    }

    /**
     * Extract keywords từ content với trọng số
     */
    protected function extractKeywords(string $content, array $stopWords): array
    {
        // 1. Loại bỏ dấu câu và ký tự đặc biệt
        $content = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $content);
        
        // 2. Tách thành từ
        $words = preg_split('/\s+/', $content);
        $words = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        // 3. Đếm tần suất xuất hiện
        $wordFreq = array_count_values($words);
        
        // 4. Tạo bigrams (2 từ liên tiếp) - thường là cụm từ quan trọng
        $bigrams = [];
        $wordsArray = array_values($words);
        for ($i = 0; $i < count($wordsArray) - 1; $i++) {
            $bigram = $wordsArray[$i] . ' ' . $wordsArray[$i + 1];
            if (!isset($bigrams[$bigram])) {
                $bigrams[$bigram] = 0;
            }
            $bigrams[$bigram]++;
        }
        
        // 5. Tính trọng số: bigrams có trọng số cao hơn, từ xuất hiện nhiều có trọng số cao hơn
        $keywords = [];
        
        // Bigrams (trọng số x2)
        foreach ($bigrams as $bigram => $freq) {
            if ($freq > 1) { // Chỉ lấy bigram xuất hiện > 1 lần
                $keywords[$bigram] = $freq * 2;
            }
        }
        
        // Single words (trọng số theo tần suất)
        foreach ($wordFreq as $word => $freq) {
            if ($freq > 1 && strlen($word) > 3) {
                $keywords[$word] = ($keywords[$word] ?? 0) + $freq;
            }
        }
        
        // 6. Sort theo trọng số
        arsort($keywords);
        
        // 7. Lấy top keywords
        return array_slice($keywords, 0, 15, true);
    }

    /**
     * Cập nhật usage_count cho entity
     */
    protected function updateEntityUsageCount(string $entityType, int $entityId): void
    {
        $count = Tag::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_active', true)
            ->count();

        // Cập nhật usage_count cho tất cả tags của entity này
        Tag::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->update(['usage_count' => $count]);
    }

    /**
     * Gắn tag cho entity
     */
    public function assignToEntity(string $entityType, int $entityId, array $tagIds): array
    {
        return DB::transaction(function () use ($entityType, $entityId, $tagIds) {
            $assignedTags = [];

            foreach ($tagIds as $tagId) {
                $tag = Tag::find($tagId);
                if ($tag) {
                    // Cập nhật entity cho tag
                    $tag->update([
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ]);
                    $assignedTags[] = $tag;
                }
            }

            // Cập nhật usage_count
            $this->updateEntityUsageCount($entityType, $entityId);

            return $assignedTags;
        });
    }

    /**
     * Bỏ tag khỏi entity
     */
    public function removeFromEntity(string $entityType, int $entityId, array $tagIds): bool
    {
        return DB::transaction(function () use ($entityType, $entityId, $tagIds) {
            $removed = Tag::where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->whereIn('id', $tagIds)
                ->delete();

            // Cập nhật usage_count
            $this->updateEntityUsageCount($entityType, $entityId);

            return $removed > 0;
        });
    }

    /**
     * Lấy tags theo entity
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        return Tag::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->active()
            ->orderBy('name')
            ->get()
            ->toArray();
    }
}

