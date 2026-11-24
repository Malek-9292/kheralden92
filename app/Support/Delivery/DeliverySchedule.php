<?php

namespace App\Support\Delivery;

use App\Models\Branch;
use Carbon\Carbon;

final class DeliverySchedule
{
    public const START_TIME = '11:30';

    public static function isOpen(Branch $branch): bool
    {
        return Carbon::now($branch->timezone)->format('H:i') >= self::START_TIME;
    }

    public static function closedMessage(): string
    {
        return 'خدمة التوصيل تبدأ يوميًا الساعة 11:30 صباحًا.';
    }
}
