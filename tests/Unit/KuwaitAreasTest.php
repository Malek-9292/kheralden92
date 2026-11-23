<?php

namespace Tests\Unit;

use App\Support\Delivery\KuwaitAreas;
use PHPUnit\Framework\TestCase;

class KuwaitAreasTest extends TestCase
{
    public function test_seed_areas_include_six_governorates_and_khaldiya_fee(): void
    {
        $this->assertCount(6, KuwaitAreas::governorates());

        $khaldiya = collect(KuwaitAreas::seedAreas())->firstWhere('name', 'الخالدية');
        $this->assertNotNull($khaldiya);
        $this->assertSame('0.250', $khaldiya['delivery_fee']);

        $salmiya = collect(KuwaitAreas::seedAreas())->firstWhere('name', 'السالمية');
        $this->assertSame('0.500', $salmiya['delivery_fee']);
    }
}
