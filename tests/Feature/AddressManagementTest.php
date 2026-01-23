<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Address;
use App\Services\AddressLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AddressManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('SQLite driver not available.');
        }
    }

    protected function mockLookup(): void
    {
        $this->mock(AddressLookupService::class, function ($mock) {
            $mock->shouldReceive('validateLocation')
                ->andReturn([
                    'province' => ['id' => 1, 'name' => 'Hải Phòng'],
                    'district' => ['id' => 2, 'name' => 'Lê Chân'],
                    'ward' => ['code' => '0001', 'name' => 'Vĩnh Niệm'],
                ]);
        });
    }

    public function test_user_can_create_address_and_becomes_default(): void
    {
        $this->mockLookup();

        $user = Account::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.v1.addresses.store'), [
            'full_name' => 'Nguyễn Văn Test',
            'phone_number' => '0901234567',
            'detail_address' => '123 Thiên Lôi',
            'district' => 'Lê Chân',
            'province' => 'Hải Phòng',
            'postal_code' => '18000',
        ]);

        $response->assertCreated();
        $this->assertTrue(Address::first()->is_default);
    }

    public function test_user_cannot_access_other_address(): void
    {
        $this->mockLookup();

        $userA = Account::factory()->create();
        $userB = Account::factory()->create();

        $address = Address::factory()->for($userA, 'account')->create([
            'province' => 'Hải Phòng',
            'district' => 'Lê Chân',
        ]);

        Sanctum::actingAs($userB);

        $this->getJson(route('api.v1.addresses.show', $address))
            ->assertForbidden();
    }

    public function test_default_reassigned_after_deletion(): void
    {
        $this->mockLookup();

        $user = Account::factory()->create();
        $addressDefault = Address::factory()->for($user, 'account')->create(['is_default' => true]);
        $addressSecond = Address::factory()->for($user, 'account')->create(['is_default' => false]);

        Sanctum::actingAs($user);

        $this->deleteJson(route('api.v1.addresses.destroy', $addressDefault))->assertOk();

        $this->assertTrue($addressSecond->fresh()->is_default);
    }
}

