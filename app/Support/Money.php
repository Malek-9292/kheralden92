<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Integer-fils money helpers for KWD (3 decimal places).
 * Never use floating-point arithmetic for monetary values.
 */
final class Money
{
    public static function toFils(string $amount): int
    {
        $amount = trim($amount);

        if ($amount === '' || ! preg_match('/^\d+(\.\d{1,3})?$/', $amount)) {
            throw new InvalidArgumentException('Invalid KWD amount: '.$amount);
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    public static function fromFils(int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Negative fils amounts are not supported.');
        }

        return intdiv($amount, 1000).'.'.str_pad((string) ($amount % 1000), 3, '0', STR_PAD_LEFT);
    }

    public static function addFils(int ...$amounts): int
    {
        $total = 0;

        foreach ($amounts as $amount) {
            $total += $amount;
        }

        return $total;
    }

    public static function multiplyFils(int $unitFils, int $quantity): int
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        return $unitFils * $quantity;
    }
}
