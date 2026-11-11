<?php

namespace Tests\Feature\Api;

use App\Enums\PlaceStatus;
use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\Place;
use App\Models\PlaceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaceRequestApiTest extends TestCase
{
    use RefreshDatabase;

    private Market $market;

    private MarketBlock $block;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->market = Market::factory()->create();
        $this->block = MarketBlock::create([
            'market_id' => $this->market->id,
            'name' => 'Bloc A',
            'code' => 'A',
            'description' => 'Bloc de test',
            'total_places' => 2,
            'is_active' => true,
        ]);
    }

    private function makePlace(string $number, ?User $chief = null): Place
    {
        return Place::create([
            'market_id' => $this->market->id,
            'market_block_id' => $this->block->id,
            'number' => $number,
            'status' => $chief ? PlaceStatus::Occupied->value : PlaceStatus::Available->value,
            'chief_user_id' => $chief?->id,
            'qr_code' => 'TEST-'.$number,
        ]);
    }

    private function payload(Place $place): array
    {
        return [
            'market_id' => $this->market->id,
            'place_id' => $place->id,
            'merchant_name' => 'Jean Test',
            'merchant_phone' => '+25779000000',
            'category' => 'Fruits',
            'description' => 'Je souhaite vendre des fruits.',
        ];
    }

    public function test_user_without_place_can_request_a_place(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/place-requests', $this->payload($this->makePlace('A-01')))
            ->assertCreated();
    }

    public function test_user_with_assigned_place_cannot_request_another_place(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMMERCANT');
        $this->makePlace('A-01', $user);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/place-requests', $this->payload($this->makePlace('A-02')))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');

        $this->assertSame(0, PlaceRequest::count());
    }

    public function test_profile_exposes_has_assigned_place(): void
    {
        $user = User::factory()->create();
        $user->assignRole('COMMERCANT');
        $this->makePlace('A-01', $user);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.has_assigned_place', true);
    }

    public function test_cannot_approve_request_of_user_who_already_has_a_place(): void
    {
        $admin = User::factory()->create(['managed_market_id' => $this->market->id]);
        $admin->assignRole('SUPER_ADMIN');

        $user = User::factory()->create();
        $user->assignRole('COMMERCANT');
        $this->makePlace('A-01', $user);
        $free = $this->makePlace('A-02');

        $request = PlaceRequest::create([
            'market_id' => $this->market->id,
            'place_id' => $free->id,
            'merchant_name' => 'Jean Test',
            'merchant_phone' => '+25779000000',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/place-requests/{$request->id}/approve")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');

        $this->assertSame(PlaceStatus::Available, $free->fresh()->status);
    }
}
