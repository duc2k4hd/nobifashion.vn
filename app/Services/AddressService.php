<?php

namespace App\Services;

use App\Events\AddressChanged;
use App\Models\Account;
use App\Models\Address;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function __construct(
        protected AddressLookupService $lookupService
    ) {
    }

    public function listForAccount(Account $account)
    {
        return $account->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getDefaultForAccount(Account $account): ?Address
    {
        return Cache::remember(
            $this->defaultCacheKey($account->id),
            3600,
            fn () => $account->addresses()->default()->first()
        );
    }

    public function create(Account $account, array $data): Address
    {
        if ($account->addresses()->count() >= (int) config('orders.address_limit', 20)) {
            throw new \RuntimeException('Bạn chỉ có thể lưu tối đa 20 địa chỉ.');
        }

        return DB::transaction(function () use ($account, $data) {
            $normalized = $this->lookupService->validateLocation(
                $data['province'],
                $data['district'],
                $data['ward'] ?? null
            );

            $data['province'] = $normalized['province']['name'];
            $data['province_code'] = $normalized['province']['id'];
            $data['district'] = $normalized['district']['name'];
            $data['district_code'] = $normalized['district']['id'];
            $data['ward'] = $normalized['ward']['name'] ?? null;
            $data['ward_code'] = $normalized['ward']['code'] ?? null;

            $data['account_id'] = $account->id;
            $data['country'] = $data['country'] ?? 'Việt Nam';

            if (!$account->addresses()->exists()) {
                $data['is_default'] = true;
            }

            $address = Address::create($data);

            if ($address->is_default) {
                $this->setExclusiveDefault($address);
            }

            $this->flushDefaultCache($account->id);

            event(new AddressChanged($address, 'created', $account->id, 'Thêm địa chỉ mới'));

            return $address->fresh();
        });
    }

    public function update(Address $address, array $data, ?Account $actor = null): Address
    {
        return DB::transaction(function () use ($address, $data, $actor) {
            $changes = [];

            if (isset($data['province'], $data['district'])) {
            $normalized = $this->lookupService->validateLocation(
                    $data['province'],
                    $data['district'],
                    $data['ward'] ?? null
                );

                $data['province'] = $normalized['province']['name'];
                $data['province_code'] = $normalized['province']['id'];
                $data['district'] = $normalized['district']['name'];
                $data['district_code'] = $normalized['district']['id'];
                $data['ward'] = $normalized['ward']['name'] ?? null;
                $data['ward_code'] = $normalized['ward']['code'] ?? null;
            }

            $originalDefault = $address->is_default;

            $address->fill($data);
            if (!$address->isDirty()) {
                return $address;
            }

            $changes = $address->getDirty();
            $address->save();

            if (($changes['is_default'] ?? null) && $address->is_default) {
                $this->setExclusiveDefault($address);
            }

            if (array_key_exists('is_default', $changes) && !$changes['is_default'] && $originalDefault) {
                $this->ensureDefaultExists($address->account_id, $address);
            }

            $this->flushDefaultCache($address->account_id);

            event(new AddressChanged(
                $address->fresh(),
                'updated',
                $actor?->id,
                'Cập nhật địa chỉ',
                $changes
            ));

            return $address->fresh();
        });
    }

    public function delete(Address $address, ?Account $actor = null): void
    {
        DB::transaction(function () use ($address, $actor) {
            $wasDefault = $address->is_default;
            $accountId = $address->account_id;
            $address->delete();

            if ($wasDefault) {
                $this->ensureDefaultExists($accountId);
            }

            $this->flushDefaultCache($accountId);

            event(new AddressChanged($address, 'deleted', $actor?->id, 'Xóa địa chỉ'));
        });
    }

    public function setDefault(Address $address, ?Account $actor = null): Address
    {
        return DB::transaction(function () use ($address, $actor) {
            if ($address->is_default) {
                return $address;
            }

            $address->is_default = true;
            $address->save();

            $this->setExclusiveDefault($address);
            $this->flushDefaultCache($address->account_id);

            event(new AddressChanged($address->fresh(), 'set_default', $actor?->id, 'Đặt làm địa chỉ mặc định'));

            return $address->fresh();
        });
    }

    protected function ensureDefaultExists(int $accountId, ?Address $fallback = null): void
    {
        $currentDefault = Address::forAccount($accountId)->default()->first();
        if ($currentDefault) {
            return;
        }

        $address = Address::forAccount($accountId)->orderByDesc('updated_at')->first() ?? $fallback;

        if ($address) {
            $address->update(['is_default' => true]);
            $this->setExclusiveDefault($address);
        }
    }

    protected function setExclusiveDefault(Address $address): void
    {
        Address::forAccount($address->account_id)
            ->where('id', '!=', $address->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    protected function flushDefaultCache(int $accountId): void
    {
        Cache::forget($this->defaultCacheKey($accountId));
    }

    protected function defaultCacheKey(int $accountId): string
    {
        return "address:default:{$accountId}";
    }
}

