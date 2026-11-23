<?php

namespace Tests\Feature;

use App\Models\DeliveryArea;
use App\Support\Delivery\KuwaitAreas;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAreaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_loads_kuwait_areas_with_khaldiya_special_fee(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(20, DeliveryArea::query()->count());

        $this->assertDatabaseHas('delivery_areas', [
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '0.250',
        ]);

        $this->getJson('/api/delivery-areas/KHAL')
            ->assertOk()
            ->assertJsonPath('governorates.0', KuwaitAreas::governorates()[0])
            ->assertJsonStructure(['delivery_areas', 'delivery_areas_by_governorate']);
    }
}
