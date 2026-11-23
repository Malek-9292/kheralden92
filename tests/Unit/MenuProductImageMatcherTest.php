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

    public function test_live_menu_zip_spellings_match_catalog_names(): void
    {
        $matcher = new MenuProductImageMatcher;
        $potato = "\u{0628}\u{0637}\u{0627}\u{0637}"; // بطاط (live ZIP spelling)
        $images = [
            ['stem' => 'مسقعه', 'path' => '/tmp/a.jpg', 'filename' => 'مسقعه.jpg'],
            ['stem' => 'جبن ابيض', 'path' => '/tmp/b.jpg', 'filename' => 'جبن ابيض.jpg'],
            ['stem' => 'فروالة', 'path' => '/tmp/c.jpg', 'filename' => 'فروالة.jpg'],
            ['stem' => 'مياه صحية', 'path' => '/tmp/d.jpg', 'filename' => 'مياه صحية.jpg'],
            ['stem' => 'ساندويش كباب', 'path' => '/tmp/e.jpg', 'filename' => 'ساندويش كباب.jpg'],
            ['stem' => 'وجبة كفتة بالشبك', 'path' => '/tmp/f.jpg', 'filename' => 'وجبة كفتة بالشبك.jpg'],
            ['stem' => 'الاجبان بأنواعها', 'path' => '/tmp/g.jpg', 'filename' => 'الاجبان بأنواعها.jpg'],
            ['stem' => "{$potato} كاتشب مايونيز", 'path' => '/tmp/i.jpg', 'filename' => "{$potato} كاتشب مايونيز.jpg"],
            ['stem' => 'شاورما دجاج صاج', 'path' => '/tmp/j.jpg', 'filename' => 'شاورما دجاج صاج.jpg'],
            ['stem' => 'شاورما دجاج', 'path' => '/tmp/k.jpg', 'filename' => 'شاورما دجاج.jpg'],
        ];

        $this->assertSame('مسقعه.jpg', $matcher->bestMatch('مسقعة', $images)['filename']);
        $this->assertSame('جبن ابيض.jpg', $matcher->bestMatch('جبن أبيض', $images)['filename']);
        $this->assertSame('فروالة.jpg', $matcher->bestMatch('فراوله', $images)['filename']);
        $this->assertSame('مياه صحية.jpg', $matcher->bestMatch('مياه صحيه', $images)['filename']);
        $this->assertSame('ساندويش كباب.jpg', $matcher->bestMatch('ساندويتش كباب', $images)['filename']);
        $this->assertSame('وجبة كفتة بالشبك.jpg', $matcher->bestMatch('وجبة كفتة بالسيخ', $images)['filename']);
        $this->assertSame('الاجبان بأنواعها.jpg', $matcher->bestMatch('الأجبان بأنواعها', $images)['filename']);
        $this->assertSame("{$potato} كاتشب مايونيز.jpg", $matcher->bestMatch('بطاطس كاتشب مايونيز', $images)['filename']);
        $this->assertSame('شاورما دجاج صاج.jpg', $matcher->bestMatch('شاورما دجاج صاج', $images)['filename']);
        $this->assertSame('شاورما دجاج.jpg', $matcher->bestMatch('شاورما دجاج', $images)['filename']);
    }
}
