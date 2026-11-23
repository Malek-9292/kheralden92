<?php

namespace App\Support\Delivery;

class KuwaitAreas
{
    public const KHALDIYA_FEE = '0.250';

    public const DEFAULT_FEE = '0.500';

    public const DEFAULT_MINIMUM = '0.000';

    /**
     * @return list<string>
     */
    public static function governorates(): array
    {
        return [
            'محافظة العاصمة',
            'محافظة حولي',
            'محافظة الفروانية',
            'محافظة الأحمدي',
            'محافظة مبارك الكبير',
            'محافظة الجهراء',
        ];
    }

    /**
     * Launch delivery coverage for فتوش الخالدية (fees from QA plan).
     *
     * @return list<array{governorate:string,name:string,delivery_fee:string,minimum_order:string}>
     */
    public static function seedAreas(): array
    {
        $areas = [];

        foreach ([
            'الخالدية' => self::KHALDIYA_FEE,
            'الشامية' => self::DEFAULT_FEE,
            'القادسية' => self::DEFAULT_FEE,
            'كيفان' => self::DEFAULT_FEE,
            'الروضة' => self::DEFAULT_FEE,
            'الدسمة' => self::DEFAULT_FEE,
            'الصليبيخات' => self::DEFAULT_FEE,
            'الدوحة' => self::DEFAULT_FEE,
            'غرناطة' => self::DEFAULT_FEE,
            'السرة' => self::DEFAULT_FEE,
            'اليرموك' => self::DEFAULT_FEE,
            'الشهداء' => self::DEFAULT_FEE,
        ] as $name => $fee) {
            $areas[] = [
                'governorate' => 'محافظة العاصمة',
                'name' => $name,
                'delivery_fee' => $fee,
                'minimum_order' => self::DEFAULT_MINIMUM,
            ];
        }

        foreach ([
            'محافظة حولي' => ['حولي', 'السالمية', 'الجابرية', 'الرميثية', 'سلوى', 'بيان'],
            'محافظة الفروانية' => ['الفروانية', 'خيطان', 'جليب الشيوخ', 'العارضية', 'الأندلس'],
            'محافظة الأحمدي' => ['الأحمدي', 'الفحيحيل', 'المنقف', 'أبو حليفة', 'فهد الأحمد'],
            'محافظة مبارك الكبير' => ['مبارك الكبير', 'العدان', 'القصور', 'صباح السالم'],
            'محافظة الجهراء' => ['الجهراء', 'القصر', 'النسيم', 'أمغرة'],
        ] as $governorate => $names) {
            foreach ($names as $name) {
                $areas[] = [
                    'governorate' => $governorate,
                    'name' => $name,
                    'delivery_fee' => self::DEFAULT_FEE,
                    'minimum_order' => self::DEFAULT_MINIMUM,
                ];
            }
        }

        return $areas;
    }
}
