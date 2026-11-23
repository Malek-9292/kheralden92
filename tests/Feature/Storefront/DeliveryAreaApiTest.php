<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\DeliveryArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAreaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_delivery_areas_show_only_active_areas_with_fees_and_minimums(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '1.000',
            'minimum_order' => '5.000',
            'is_active' => true,
        ]);

        DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'منطقة غير متاحة',
            'delivery_fee' => '2.000',
            'minimum_order' => '8.000',
            'is_active' => false,
        ]);

        $this->getJson('/api/delivery-areas/KHAL')
            ->assertOk()
            ->assertJsonCount(1, 'delivery_areas')
            ->assertJsonPath('delivery_areas.0.name', 'الخالدية')
            ->assertJsonPath('delivery_areas.0.delivery_fee', '1.000')
            ->assertJsonPath('delivery_areas.0.minimum_order', '5.000');

        $branch->update(['is_active' => false]);

        $this->getJson('/api/delivery-areas/KHAL')->assertNotFound();
    }
}
