<?php

namespace App\Enum;

class StatusSanction
{
    public const PENDING = 'pending';

    public const EXECUTED = 'executed';

    public const REJECTED = 'rejected';

    public static function getStatusSanction(): array
    {
        return [
            self::PENDING,
            self::EXECUTED,
            self::REJECTED
        ];
    }
}
