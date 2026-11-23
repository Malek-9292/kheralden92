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

        $windows = $product->availabilityWindows()
            ->where('is_active', true)
            ->get();

        if ($windows->isEmpty()) {
            return true;
        }

        $localTime = ($at ?? now())->copy()->setTimezone('Asia/Kuwait')->format('H:i:s');

        return $windows->contains(
            fn ($window): bool => $localTime >= $window->start_time && $localTime <= $window->end_time,
        );
    }
}
