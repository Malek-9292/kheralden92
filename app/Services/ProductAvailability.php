<?php

namespace App\Services;

use App\Models\Product;
use Carbon\CarbonInterface;

final class ProductAvailability
{
    public function isAvailable(Product $product, ?CarbonInterface $at = null): bool
    {
        if (! $product->is_active) {
            return false;
        }

        $allWindows = $product->availabilityWindows()->get();

        if ($allWindows->isEmpty()) {
            return true;
        }

        $activeWindows = $allWindows->where('is_active', true);
        if ($activeWindows->isEmpty()) {
            return false;
        }

        $localTime = ($at ?? now())->copy()->setTimezone('Asia/Kuwait')->format('H:i:s');

        return $activeWindows->contains(
            fn ($window): bool => $localTime >= $window->start_time && $localTime <= $window->end_time,
        );
    }
}
