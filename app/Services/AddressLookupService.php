<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AddressLookupService
{
    public function __construct(
        protected ?string $ghnToken = null,
        protected ?string $ghnBaseUrl = null,
        protected int $cacheSeconds = 21600 // 6h
    ) {
        $this->ghnToken = $ghnToken ?? config('services.ghn.token');
        $this->ghnBaseUrl = rtrim($ghnBaseUrl ?? config('services.ghn.base_url'), '/') . '/';
    }

    public function getProvinces(): Collection
    {
        return Cache::remember('locations:provinces', $this->cacheSeconds, fn () => $this->fetchProvinces());
    }

    public function getDistricts(int $provinceId): Collection
    {
        return Cache::remember("locations:districts:{$provinceId}", $this->cacheSeconds, fn () => $this->fetchDistricts($provinceId));
    }

    public function getWards(int $districtId): Collection
    {
        return Cache::remember("locations:wards:{$districtId}", $this->cacheSeconds, fn () => $this->fetchWards($districtId));
    }

    public function validateLocation(string $province, string $district, ?string $ward = null): array
    {
        $provinceData = $this->matchByName($this->getProvinces(), $province, 'ProvinceName');

        if (!$provinceData) {
            throw new \InvalidArgumentException('Tỉnh/Thành không hợp lệ.');
        }

        $districtData = $this->matchByName($this->getDistricts($provinceData['ProvinceID']), $district, 'DistrictName');

        if (!$districtData) {
            throw new \InvalidArgumentException('Quận/Huyện không hợp lệ.');
        }

        $wardData = null;
        if ($ward) {
            $wardData = $this->matchByName($this->getWards($districtData['DistrictID']), $ward, 'WardName');
            if (!$wardData) {
                throw new \InvalidArgumentException('Xã/Phường không hợp lệ.');
            }
        }

        return [
            'province' => [
                'id' => $provinceData['ProvinceID'],
                'name' => $provinceData['ProvinceName'],
                'code' => $provinceData['Code'] ?? null,
            ],
            'district' => [
                'id' => $districtData['DistrictID'],
                'name' => $districtData['DistrictName'],
            ],
            'ward' => $wardData ? [
                'code' => $wardData['WardCode'],
                'name' => $wardData['WardName'],
            ] : null,
        ];
    }

    protected function fetchProvinces(): Collection
    {
        $response = $this->request('master-data/province');

        return collect($response->json('data', []));
    }

    protected function fetchDistricts(int $provinceId): Collection
    {
        $response = $this->request('master-data/district', [
            'province_id' => $provinceId,
        ], 'post');

        return collect($response->json('data', []));
    }

    protected function fetchWards(int $districtId): Collection
    {
        $response = $this->request('master-data/ward', [
            'district_id' => $districtId,
        ], 'post');

        return collect($response->json('data', []));
    }

    protected function request(string $path, array $payload = [], string $method = 'get')
    {
        $http = Http::withHeaders([
            'token' => $this->ghnToken,
            'Content-Type' => 'application/json',
        ]);

        $url = $this->ghnBaseUrl . ltrim($path, '/');

        $response = $method === 'post'
            ? $http->post($url, $payload)
            : $http->get($url, $payload);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response;
    }

    protected function matchByName(Collection $collection, string $keyword, string $field)
    {
        $normalizedKeyword = $this->normalizeName($keyword);

        return $collection->first(function ($item) use ($field, $normalizedKeyword) {
            $value = $this->normalizeName($item[$field] ?? '');

            if ($value === $normalizedKeyword) {
                return true;
            }

            $extensions = collect($item['NameExtension'] ?? []);

            return $extensions->contains(function ($extension) use ($normalizedKeyword) {
                return $this->normalizeName($extension) === $normalizedKeyword;
            });
        });
    }

    protected function normalizeName(?string $value): string
    {
        return trim(mb_strtolower($value ?? ''));
    }
}

