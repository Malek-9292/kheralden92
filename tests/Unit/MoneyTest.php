<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_converts_kwd_strings_to_and_from_fils(): void
    {
        $this->assertSame(1500, Money::toFils('1.500'));
        $this->assertSame(150, Money::toFils('0.150'));
        $this->assertSame(1000, Money::toFils('1'));
        $this->assertSame('1.500', Money::fromFils(1500));
        $this->assertSame('0.150', Money::fromFils(150));
        $this->assertSame('0.000', Money::fromFils(0));
    }

    public function test_rejects_invalid_amounts_and_negative_fils(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toFils('1.2345');
    }

    public function test_add_and_multiply_stay_in_integer_fils(): void
    {
        $this->assertSame(2750, Money::addFils(1500, 1250));
        $this->assertSame(4500, Money::multiplyFils(1500, 3));
    }
}
