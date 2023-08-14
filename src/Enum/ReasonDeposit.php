<?php

namespace App\Enum;

class ReasonDeposit
{
    public const TONTINARD_DEPOSIT = 'Deposit tontinard';

    public const SANCTION = 'sanction';

    public const REFUND = 'refund';

    public const EVENT = 'event';

    public static function allsReasonDeposit(): array
    {
        return [
            self::TONTINARD_DEPOSIT,
            self::SANCTION,
            self::REFUND,
            self::EVENT,
        ];
    }
}
