<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DeliveryArea;
use App\Support\Delivery\KuwaitAreas;
use Illuminate\Database\Seeder;

class DeliveryAreaSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'KHAL')->firstOrFail();

        foreach (KuwaitAreas::seedAreas() as $index => $area) {
            DeliveryArea::query()->firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'governorate' => $area['governorate'],
                    'name' => $area['name'],
                ],
                [
                    'delivery_fee' => $area['delivery_fee'],
                    'minimum_order' => $area['minimum_order'],
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );
        }
    }
}
