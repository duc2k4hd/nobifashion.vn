<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('web')->check();
    }

    public function rules(): array
    {
        $routeBrand = optional($this->route('brand'));
        $brandId = $routeBrand?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($brandId),
            ],
            'description' => ['nullable', 'string'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'followers_count' => ['nullable', 'integer', 'min:0'],
            'rating_score' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'joined_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'banner_slides' => ['nullable', 'array'],
            'banner_slides.*.tag' => ['nullable', 'string', 'max:255'],
            'banner_slides.*.title' => ['nullable', 'string', 'max:255'],
            'banner_slides.*.highlight' => ['nullable', 'string', 'max:255'],
            'banner_slides.*.image' => ['nullable', 'string', 'max:255'],
            'banner_slides.*.image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:500'],
            'faqs.*.answer' => ['nullable', 'string'],
            'logo' => ['nullable', 'file', 'image', 'max:4096'],
            'website' => ['nullable', 'url', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'meta_canonical' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'assigned_product_ids' => ['nullable', 'array'],
            'assigned_product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['followers_count'] = isset($data['followers_count']) ? (int) $data['followers_count'] : 0;
        $data['rating_score'] = isset($data['rating_score']) ? (float) $data['rating_score'] : 5.0;
        $data['joined_years'] = isset($data['joined_years']) ? (int) $data['joined_years'] : 0;

        // Chuẩn hóa và lọc sạch banner_slides
        if (!empty($data['banner_slides']) && is_array($data['banner_slides'])) {
            $cleanedSlides = [];
            foreach ($data['banner_slides'] as $slide) {
                $tag = trim((string) ($slide['tag'] ?? ''));
                $title = trim((string) ($slide['title'] ?? ''));
                $highlight = trim((string) ($slide['highlight'] ?? ''));
                $image = trim((string) ($slide['image'] ?? ''));

                if ($tag !== '' || $title !== '' || $highlight !== '' || $image !== '' || isset($slide['image_file'])) {
                    $cleanedSlides[] = [
                        'tag' => $tag,
                        'title' => $title,
                        'highlight' => $highlight,
                        'image' => $image ?: null,
                    ];
                }
            }
            $data['banner_slides'] = $cleanedSlides;
        } else {
            $data['banner_slides'] = [];
        }

        // Chuẩn hóa và lọc sạch faqs
        if (!empty($data['faqs']) && is_array($data['faqs'])) {
            $cleanedFaqs = [];
            foreach ($data['faqs'] as $faq) {
                $question = trim((string) ($faq['question'] ?? ''));
                $answer = trim((string) ($faq['answer'] ?? ''));
                if ($question !== '' || $answer !== '') {
                    $cleanedFaqs[] = [
                        'question' => $question,
                        'answer' => $answer,
                    ];
                }
            }
            $data['faqs'] = $cleanedFaqs;
        } else {
            $data['faqs'] = [];
        }

        return $data;
    }
}
