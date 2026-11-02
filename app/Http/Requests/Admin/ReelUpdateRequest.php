<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReelUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reelId = $this->route('reel') ?: $this->route('id');
        if (is_object($reelId)) {
            $reelId = $reelId->id;
        }

        return [
            // Tab 1: Cơ bản
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:191', Rule::unique('reels', 'slug')->ignore($reelId)],
            'caption' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:50'],
            'hashtags' => ['nullable'],
            'status' => ['required', 'in:published,draft,hidden,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'order_priority' => ['nullable', 'integer'],
            'published_at' => ['nullable', 'date'],

            // Tab 2: Media
            'video_url' => ['required', 'url', 'max:1000'],
            'poster_url' => ['nullable', 'url', 'max:1000'],
            'thumbnail_url' => ['nullable', 'url', 'max:1000'],
            'external_id' => ['nullable', 'string', 'max:100', Rule::unique('reels', 'external_id')->ignore($reelId)],
            'duration' => ['nullable', 'integer', 'min:0'],
            'width' => ['nullable', 'integer', 'min:1'],
            'height' => ['nullable', 'integer', 'min:1'],
            'aspect_ratio' => ['nullable', 'string', 'max:10'],
            'mime_type' => ['nullable', 'string', 'max:50'],
            'playback_type' => ['nullable', 'string', 'max:30'],
            'video_provider' => ['nullable', 'string', 'max:50'],
            'audio_title' => ['nullable', 'string', 'max:255'],

            // Tab 3: Tác giả
            'author_name' => ['nullable', 'string', 'max:100'],
            'author_handle' => ['nullable', 'string', 'max:100'],
            'author_avatar' => ['nullable', 'string', 'max:1000'],
            'author_verified' => ['nullable', 'boolean'],

            // Tab 4: Hãng
            'brand_id' => ['nullable', 'exists:brands,id'],

            // Tab 5: Sản phẩm
            'products' => ['nullable', 'array'],
            'products.*' => ['integer', 'exists:products,id'],

            // Tab 6: SEO
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề cho video Reel.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'slug.unique' => 'Đường dẫn (slug) này đã tồn tại, vui lòng chọn đường dẫn khác.',
            'video_url.required' => 'Vui lòng nhập đường dẫn URL video.',
            'video_url.url' => 'Đường dẫn video không đúng định dạng URL hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái video.',
            'external_id.unique' => 'Mã video nguồn (external ID) đã tồn tại trong hệ thống.',
        ];
    }
}

