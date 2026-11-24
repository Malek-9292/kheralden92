<?php

namespace Tests\Feature\Storefront;

use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Models\DeliveryAreaGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAreaGroupPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_area_inherits_group_prices_and_can_override_them(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $group = DeliveryAreaGroup::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مجموعة العاصمة',
            'delivery_fee' => '1.000',
            'minimum_order' => '3.000',
            'is_active' => true,
        ]);

        $inherited = DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'delivery_area_group_id' => $group->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '0.000',
            'minimum_order' => '0.000',
            'is_active' => true,
        ]);

        $override = DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'delivery_area_group_id' => $group->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'قرطبة',
            'delivery_fee' => '0.000',
            'minimum_order' => '0.000',
            'delivery_fee_override' => '1.500',
            'minimum_order_override' => '5.000',
            'is_active' => true,
        ]);

        $this->getJson('/api/delivery-areas/KHAL')
            ->assertOk()
            ->assertJsonPath('delivery_areas.0.delivery_fee', '1.000')
            ->assertJsonPath('delivery_areas.0.minimum_order', '3.000')
            ->assertJsonPath('delivery_areas.1.delivery_fee', '1.500')
            ->assertJsonPath('delivery_areas.1.minimum_order', '5.000');

        $this->assertSame('1.000', $inherited->fresh()->effectiveDeliveryFee());
        $this->assertSame('3.000', $inherited->fresh()->effectiveMinimumOrder());
        $this->assertSame('1.500', $override->fresh()->effectiveDeliveryFee());
        $this->assertSame('5.000', $override->fresh()->effectiveMinimumOrder());
    }

    public function test_paused_group_hides_its_areas_from_storefront(): void
    {
        $branch = Branch::query()->create([
            'code' => 'KHAL',
            'name' => 'فتوش الخالدية',
            'timezone' => 'Asia/Kuwait',
            'is_active' => true,
        ]);

        $group = DeliveryAreaGroup::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مجموعة متوقفة',
            'delivery_fee' => '1.000',
            'minimum_order' => '2.000',
            'is_active' => false,
        ]);

        DeliveryArea::query()->create([
            'branch_id' => $branch->id,
            'delivery_area_group_id' => $group->id,
            'governorate' => 'محافظة العاصمة',
            'name' => 'الخالدية',
            'delivery_fee' => '0.000',
            'minimum_order' => '0.000',
            'is_active' => true,
        ]);

        $this->getJson('/api/delivery-areas/KHAL')
            ->assertOk()
            ->assertJsonCount(0, 'delivery_areas');
    }
}
