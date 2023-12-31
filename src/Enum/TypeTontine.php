<?php

namespace App\Enum;

class TypeTontine
{
    public const PROPERTY_PURCHASE = "property_purchase";
    public const ROTATING = "rotating";

    public const ACCUMULATIVE = "accumulative";

    public const MIX = "mix";

    public static function allTypeSanction(): array
    {
        return [
            self::PROPERTY_PURCHASE,
            self::ROTATING,
            self::ACCUMULATIVE,
            self::MIX
        ];
    }
}
