<?php

namespace Tests\Unit;

use App\Support\MenuProductImageMatcher;
use PHPUnit\Framework\TestCase;

class MenuProductImageMatcherTest extends TestCase
{
    public function test_fuzzy_normalization_and_variant_matching_prefers_longest_stem(): void
    {
        $matcher = new MenuProductImageMatcher;
        $images = [
            ['stem' => 'شاورما لحم', 'path' => '/tmp/a.jpg', 'filename' => 'شاورما لحم.jpg'],
            ['stem' => 'مشروم', 'path' => '/tmp/b.jpg', 'filename' => 'مشروم.jpg'],
            ['stem' => 'مشروم + جبن', 'path' => '/tmp/c.jpg', 'filename' => 'مشروم + جبن.jpg'],
            ['stem' => 'تكا لحم بالطماط', 'path' => '/tmp/d.jpg', 'filename' => 'تكا لحم بالطماط.jpg'],
            ['stem' => 'تقانق', 'path' => '/tmp/e.jpg', 'filename' => 'تقانق.jpg'],
            ['stem' => 'حلوم', 'path' => '/tmp/f.jpg', 'filename' => 'حلوم.jpg'],
            ['stem' => 'كبدة دجاج', 'path' => '/tmp/g.jpg', 'filename' => 'كبدة دجاج.jpg'],
            ['stem' => 'كفتة بالصلصة', 'path' => '/tmp/h.jpg', 'filename' => 'كفتة بالصلصة.jpg'],
            ['stem' => 'مسحب', 'path' => '/tmp/i.jpg', 'filename' => 'مسحب.jpg'],
            ['stem' => 'كباب', 'path' => '/tmp/j.jpg', 'filename' => 'كباب.jpg'],
        ];

        $this->assertSame('شاورما لحم.jpg', $matcher->bestMatch('شاورما لحم صاج', $images)['filename']);
        $this->assertSame('مشروم + جبن.jpg', $matcher->bestMatch('مشروم + جبن', $images)['filename']);
        $this->assertSame('مشروم.jpg', $matcher->bestMatch('مشروم', $images)['filename']);
        $this->assertSame('تكا لحم بالطماط.jpg', $matcher->bestMatch('تكة لحم بالطماطم', $images)['filename']);
        $this->assertSame('تقانق.jpg', $matcher->bestMatch('نقانق جبن', $images)['filename']);
        $this->assertSame('حلوم.jpg', $matcher->bestMatch('حلومي', $images)['filename']);
        $this->assertSame('كبدة دجاج.jpg', $matcher->bestMatch('كبة دجاج', $images)['filename']);
        $this->assertSame('كفتة بالصلصة.jpg', $matcher->bestMatch('كفتة بالطماطم', $images)['filename']);
        $this->assertSame('مسحب.jpg', $matcher->bestMatch('مسحوب', $images)['filename']);
        $this->assertSame('كباب.jpg', $matcher->bestMatch('ساندويتش كباب', $images)['filename']);
        $this->assertNull($matcher->bestMatch('فتوش', $images));
    }
}
