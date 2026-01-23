<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressSuggestionService
{
    public function __construct(
        protected ?string $googleApiKey = null,
        protected int $cacheSeconds = 1800
    ) {
        $this->googleApiKey = $googleApiKey ?? config('services.google.places_api_key');
    }

    public function suggest(string $keyword): array
    {
        if (!$this->googleApiKey || strlen($keyword) < 3) {
            return [];
        }

        $cacheKey = 'address:suggest:' . md5($keyword);

        return Cache::remember($cacheKey, $this->cacheSeconds, function () use ($keyword) {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                'input' => $keyword,
                'key' => $this->googleApiKey,
                'types' => 'address',
                'language' => 'vi',
                'components' => 'country:vn',
            ]);

            if ($response->failed()) {
                return [];
            }

            return collect($response->json('predictions', []))
                ->map(function ($item) {
                    return [
                        'description' => $item['description'] ?? null,
                        'place_id' => $item['place_id'] ?? null,
                        'terms' => $item['terms'] ?? [],
                    ];
                })
                ->take(8)
                ->values()
                ->all();
        });
    }

    public function resolvePlace(string $placeId): array
    {
        if (!$this->googleApiKey) {
            return [];
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'key' => $this->googleApiKey,
            'language' => 'vi',
            'fields' => 'address_component,formatted_address,geometry',
        ]);

        if ($response->failed()) {
            return [];
        }

        $result = $response->json('result', []);
        $components = collect($result['address_components'] ?? []);

        return [
            'formatted_address' => $result['formatted_address'] ?? null,
            'latitude' => data_get($result, 'geometry.location.lat'),
            'longitude' => data_get($result, 'geometry.location.lng'),
            'province' => $this->extractComponent($components, 'administrative_area_level_1'),
            'district' => $this->extractComponent($components, 'administrative_area_level_2'),
            'ward' => $this->extractComponent($components, 'sublocality_level_1'),
            'postal_code' => $this->extractComponent($components, 'postal_code'),
            'country' => $this->extractComponent($components, 'country'),
        ];
    }

    protected function extractComponent($components, string $type): ?string
    {
        $component = $components
            ->first(fn ($component) => in_array($type, $component['types'] ?? []));

        return $component['long_name'] ?? null;
    }
}

