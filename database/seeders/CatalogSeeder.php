<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'KHAL')->firstOrFail();
        $categories = Category::query()
            ->where('branch_id', $branch->id)
            ->get()
            ->keyBy('slug');

        $placeholderSource = public_path('images/product-placeholder.jpg');
        if (! is_file($placeholderSource)) {
            throw new \RuntimeException('Missing product placeholder image at public/images/product-placeholder.jpg');
        }

        foreach ($this->catalog() as $categorySlug => $items) {
            $category = $categories->get($categorySlug);
            if ($category === null) {
                continue;
            }

            foreach ($items as $index => $item) {
                $slug = Str::slug($item['name']) ?: 'product-'.$categorySlug.'-'.$index;

                $product = Product::query()->firstOrCreate(
                    ['branch_id' => $branch->id, 'slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'bread_type' => $item['bread_type'] ?? null,
                        'ingredients' => $item['ingredients'] ?? null,
                        'price' => $item['price'],
                        'is_active' => true,
                        'is_best_seller' => (bool) ($item['is_best_seller'] ?? false),
                        'sort_order' => $index,
                    ],
                );

                if ($product->images()->exists()) {
                    continue;
                }

                $relative = 'products/'.$product->id.'/placeholder.jpg';
                Storage::disk('public')->put($relative, File::get($placeholderSource));
                $product->images()->create([
                    'path' => $relative,
                    'original_name' => 'placeholder.jpg',
                    'mime_type' => 'image/jpeg',
                    'size_bytes' => Storage::disk('public')->size($relative),
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }
        }
    }

    /**
     * Approved menu from docs/09-PRODUCT-CATALOG-DRAFT.md
     *
     * @return array<string, list<array{name:string,price:string,description?:string,ingredients?:string,bread_type?:string,is_best_seller?:bool}>>
     */
    private function catalog(): array
    {
        return [
            'eftar-alkhaldiya' => [
                ['name' => 'ماعون مشكل مقالي', 'price' => '1.000'],
                ['name' => 'ماعون فول', 'price' => '0.750'],
                ['name' => 'ماعون مسقعة', 'price' => '0.750'],
                ['name' => 'ماعون قدسية', 'price' => '0.750'],
                ['name' => 'ماعون حمص', 'price' => '0.750'],
                ['name' => 'ماعون عجة', 'price' => '1.500'],
                ['name' => 'ماعون لبنة', 'price' => '2.000'],
                ['name' => 'ماعون أجبان', 'price' => '3.000'],
                ['name' => 'ماعون ترويقة', 'price' => '6.500', 'is_best_seller' => true],
                ['name' => 'حمص وسط', 'price' => '0.750'],
                ['name' => 'حمص بيروتي', 'price' => '0.750'],
                ['name' => 'متبل وسط', 'price' => '0.750'],
                ['name' => 'فتوش', 'price' => '1.000', 'is_best_seller' => true],
                ['name' => 'تبولة', 'price' => '1.000'],
                ['name' => 'ورق عنب', 'price' => '1.500'],
                ['name' => 'بابا غنوج', 'price' => '1.250'],
                ['name' => 'خيار باللبن', 'price' => '1.250'],
                ['name' => 'سلطة شمندر', 'price' => '1.250'],
                ['name' => 'سلطة ذرة', 'price' => '1.250'],
                ['name' => 'سلطة خضراء', 'price' => '1.000'],
                ['name' => 'سلطة يونانية', 'price' => '1.250'],
                ['name' => 'سلطة جرجير', 'price' => '1.000'],
                ['name' => 'ماعون شيبس وسط', 'price' => '0.750'],
                ['name' => 'ماعون ثوم وسط', 'price' => '0.750'],
                ['name' => 'ماعون حمص كبير', 'price' => '1.500'],
                ['name' => 'ماعون متبل كبير', 'price' => '1.500'],
                ['name' => 'ماعون شيبس كبير', 'price' => '1.500'],
            ],
            'juices' => [
                ['name' => 'كوكتيل', 'price' => '0.750'],
                ['name' => 'برتقال', 'price' => '0.600'],
                ['name' => 'جزر', 'price' => '0.600'],
                ['name' => 'تفاح', 'price' => '0.750'],
                ['name' => 'عوار قلب', 'price' => '0.750'],
                ['name' => 'فراوله', 'price' => '0.750'],
                ['name' => 'مانجو', 'price' => '0.750'],
                ['name' => 'موز حليب', 'price' => '0.600'],
                ['name' => 'موز + مانجو', 'price' => '0.750'],
                ['name' => 'ليمون بالنعناع', 'price' => '0.600'],
                ['name' => 'كوكتيل شامي', 'price' => '0.750'],
                ['name' => 'مياه صحيه', 'price' => '0.150'],
                ['name' => 'مرطبات غازيه', 'price' => '0.150'],
                ['name' => 'بطل عصير صغير', 'price' => '1.500'],
                ['name' => 'بطل عصير كبير', 'price' => '3.500'],
                ['name' => 'جلاص ثلج', 'price' => '0.150'],
            ],
            'grills' => [
                ['name' => 'وجبة كباب لحم', 'price' => '2.750', 'is_best_seller' => true],
                ['name' => 'وجبة كباب دجاج', 'price' => '2.250'],
                ['name' => 'وجبة كفتة بالسيخ', 'price' => '2.500'],
                ['name' => 'وجبة مشويات', 'price' => '3.000'],
                ['name' => 'وجبة تكة', 'price' => '3.000'],
                ['name' => 'وجبة خشخاش', 'price' => '3.000'],
                ['name' => 'وجبة كباب فلسطيني', 'price' => '2.500'],
                ['name' => 'وجبة طاووق', 'price' => '3.000'],
                ['name' => 'وجبة دجاج ع الفحم', 'price' => '3.000'],
                ['name' => 'وجبة عربي لحم', 'price' => '2.250'],
                ['name' => 'وجبة عربي دجاج', 'price' => '2.250'],
                ['name' => 'وجبة عرايس لحم', 'price' => '2.000'],
                ['name' => 'وجبة عرايس دجاج', 'price' => '1.750'],
                ['name' => 'وجبة عرايس جبن', 'price' => '2.500'],
                ['name' => 'وجبة دجاج شواية', 'price' => '2.750'],
                ['name' => 'وجبة برياني دجاج', 'price' => '2.250'],
                ['name' => 'وجبة عرايس لحم جبن', 'price' => '2.500'],
            ],
            'grill-trays' => [
                [
                    'name' => 'صينية مشويات 3 أشخاص',
                    'price' => '12.000',
                    'ingredients' => "كباب لحم\nكباب دجاج\nكفتة\nطاووق\nخضار مشوية\nصلصات جانبية",
                ],
                [
                    'name' => 'صينية مشويات 4 أشخاص',
                    'price' => '14.000',
                    'ingredients' => "كباب لحم\nكباب دجاج\nكفتة\nطاووق\nتكة\nخضار مشوية\nصلصات جانبية",
                ],
                [
                    'name' => 'صينية مشويات 5 أشخاص',
                    'price' => '17.500',
                    'ingredients' => "كباب لحم\nكباب دجاج\nكفتة\nطاووق\nتكة\nعرايس\nخضار مشوية\nصلصات جانبية",
                    'is_best_seller' => true,
                ],
                [
                    'name' => 'صينية مشويات 6 أشخاص',
                    'price' => '20.000',
                    'ingredients' => "كباب لحم\nكباب دجاج\nكفتة\nطاووق\nتكة\nخشخاش\nعرايس\nخضار مشوية\nصلصات جانبية",
                ],
                [
                    'name' => 'صينية مشويات 8 أشخاص',
                    'price' => '24.000',
                    'ingredients' => 'تشكيلة مشويات كبيرة لثمانية أشخاص مع خضار وصلصات',
                ],
                [
                    'name' => 'صينية مشويات 10 أشخاص',
                    'price' => '28.000',
                    'ingredients' => 'تشكيلة مشويات كبيرة لعشرة أشخاص مع خضار وصلصات',
                ],
                ['name' => 'وجبة استيك لحم عجل', 'price' => '2.500'],
                ['name' => 'نص دجاج شواية بطاط', 'price' => '1.500'],
                ['name' => 'نص دجاج ع الفحم بطاط', 'price' => '1.500'],
                ['name' => 'نص برياني مع مرق', 'price' => '1.250'],
                ['name' => 'مجبوس دجاج', 'price' => '2.500'],
                ['name' => 'ماعون كفتة بالمرق', 'price' => '2.500'],
            ],
            'plates' => [
                ['name' => 'ماعون شاورما لحم', 'price' => '3.000'],
                ['name' => 'ماعون شاورما دجاج', 'price' => '3.000'],
                ['name' => 'ماعون شاورما لحم بالحمص', 'price' => '3.500'],
                ['name' => 'ماعون شاورما دجاج بالحمص', 'price' => '3.500'],
                ['name' => 'ماعون كبدة', 'price' => '2.500'],
                ['name' => 'ماعون تكه لحم بالطماطم', 'price' => '2.500'],
                ['name' => 'ماعون تكه دجاج بالطماطم', 'price' => '2.500'],
                ['name' => 'ماعون مشروم', 'price' => '2.000'],
            ],
            'light-sandwiches' => [
                ['name' => 'فلافل', 'price' => '0.100'],
                ['name' => 'مشكل', 'price' => '0.100'],
                ['name' => 'فول', 'price' => '0.100'],
                ['name' => 'مشكل فلافل مع حمص', 'price' => '0.150'],
                ['name' => 'مشكل فلافل خبز', 'price' => '0.150'],
                ['name' => 'مسقعة', 'price' => '0.150'],
                ['name' => 'عجة', 'price' => '0.150'],
                ['name' => 'عجة بطاط', 'price' => '0.150'],
                ['name' => 'بطاطس', 'price' => '0.100'],
                ['name' => 'بطاطس كاتشب مايونيز', 'price' => '0.150'],
                ['name' => 'بطاطس باذنجان خبز', 'price' => '0.150'],
                ['name' => 'صمون حمص سلطة', 'price' => '0.150'],
                ['name' => 'صمون حمص بطاط سلطة', 'price' => '0.150'],
                ['name' => 'بيض بالطماطم', 'price' => '0.150'],
                ['name' => 'بيض بالطماطم والبطاطس', 'price' => '0.150'],
                ['name' => 'لبنة كامل', 'price' => '0.300'],
                ['name' => 'جبن أبيض', 'price' => '0.350'],
                ['name' => 'كيس صمون 10 حبات', 'price' => '0.350', 'bread_type' => 'samoon'],
            ],
            'hot-cold-sandwiches' => [
                ['name' => 'شاورما لحم', 'price' => '0.650', 'is_best_seller' => true],
                ['name' => 'شاورما دجاج', 'price' => '0.650', 'is_best_seller' => true],
                ['name' => 'ساندويتش كباب', 'price' => '0.600'],
                ['name' => 'ساندويتش شيش طاووق', 'price' => '0.600'],
                ['name' => 'كفتة بالطماطم', 'price' => '0.500'],
                ['name' => 'كبة دجاج', 'price' => '0.400'],
                ['name' => 'تكة لحم بالطماطم', 'price' => '0.400'],
                ['name' => 'تكة دجاج بالطماطم', 'price' => '0.400'],
                ['name' => 'مشروم', 'price' => '0.400'],
                ['name' => 'مشروم + جبن', 'price' => '0.500'],
                ['name' => 'حلومي', 'price' => '0.500'],
                ['name' => 'طلياني', 'price' => '0.600'],
                ['name' => 'مسحوب', 'price' => '0.400'],
                ['name' => 'مرتديلا', 'price' => '0.400'],
                ['name' => 'مرتديلا + جبن', 'price' => '0.500'],
                ['name' => 'مرتديلا + حلوم', 'price' => '0.600'],
                ['name' => 'نقانق', 'price' => '0.400'],
                ['name' => 'نقانق جبن', 'price' => '0.500'],
                ['name' => 'نقانق بيض', 'price' => '0.400'],
                ['name' => 'نقانق بيض جبن', 'price' => '0.500'],
                ['name' => 'صمون لسان', 'price' => '0.600'],
            ],
            'saj-sandwiches' => [
                ['name' => 'فلافل صاج', 'price' => '0.400'],
                ['name' => 'مشكل مقالي صاج', 'price' => '0.400'],
                ['name' => 'شاورما دجاج صاج', 'price' => '0.850'],
                ['name' => 'شاورما لحم صاج', 'price' => '0.850'],
                ['name' => 'كباب صاج', 'price' => '0.850'],
                ['name' => 'شيش طاووق صاج', 'price' => '0.850'],
                ['name' => 'حلوم صاج', 'price' => '0.850'],
                ['name' => 'طلياني صاج', 'price' => '0.850'],
                ['name' => 'الأجبان بأنواعها', 'price' => '0.750'],
                ['name' => 'لبنة بأنواعها', 'price' => '0.600'],
                ['name' => 'مرتديلا بالجبن صاج', 'price' => '0.750'],
                ['name' => 'صاج لسان', 'price' => '0.850', 'bread_type' => 'saj'],
            ],
        ];
    }
}
