<?php

namespace App\Enum;

class PeriodTontine
{
    public const MONTHLY = 'monthly';

    public const WEEKLY = 'weekly';

    public const TWICEWEEKLY = 'two_week';

    //trimestre
    public const QUARTERLY = 'quarterly';

    public const HALFYEARLY = 'half_yearly';

    public const ANNUALLY = 'annually';

    public static function allPeriods(): array
    {
        return [
            self::MONTHLY,
            self::WEEKLY,
            self::TWICEWEEKLY,
            self::QUARTERLY,
            self::HALFYEARLY,
            self::ANNUALLY,
        ];
    }
}
