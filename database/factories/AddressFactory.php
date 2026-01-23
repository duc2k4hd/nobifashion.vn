<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'full_name' => $this->faker->name(),
            'phone_number' => '0' . $this->faker->numberBetween(1000000000, 9999999999),
            'detail_address' => $this->faker->streetAddress(),
            'ward' => $this->faker->citySuffix(),
            'district' => $this->faker->city(),
            'province' => $this->faker->state(),
            'province_code' => $this->faker->numberBetween(1, 999),
            'district_code' => $this->faker->numberBetween(1, 9999),
            'ward_code' => (string) $this->faker->numberBetween(10000, 99999),
            'postal_code' => $this->faker->postcode(),
            'country' => 'Việt Nam',
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'address_type' => $this->faker->randomElement(['home', 'work']),
            'notes' => $this->faker->sentence(),
            'is_default' => false,
        ];
    }
}

