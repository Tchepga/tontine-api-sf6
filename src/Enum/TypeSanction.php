<?php

namespace App\Enum;

class TypeSanction
{
    public const SANCTION_PRESENCE = 'sanction_presence';

    public const SANCTION_LATE = 'sanction_late';

    public const SANCTION_LOAN_DATE_CROSS = 'sanction_loan_date_cross';

    public static function allSanctions(): array
    {
        return [
            self::SANCTION_PRESENCE,
            self::SANCTION_LATE,
            self::SANCTION_LOAN_DATE_CROSS
        ];
    }
}
