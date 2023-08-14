<?php


namespace App\Enum;

class TypeEvent
{
    public const BIRTHDAY = 'birthday';

    public const GRIEF = 'grief';

    public const WEDDING = 'wedding';

    public const FUNERAL = 'funeral';

    public const NEWBORN = 'newborn';

    public const ILLNESS = 'illness';

    public const MEETING = 'meeting';

    public static function allTypeEvent(): array
    {
        return [
            self::BIRTHDAY,
            self::GRIEF,
            self::WEDDING,
            self::FUNERAL,
            self::NEWBORN,
            self::ILLNESS,
            self::MEETING,
        ];
    }
}
