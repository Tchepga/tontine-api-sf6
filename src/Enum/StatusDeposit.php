<?php

namespace App\Enum;

class StatusDeposit
{
   public const PENDING = 'pending';

   public const VALIDATED = 'validated';

   public const REJECTED = 'rejected';

   public static function allStatusDeposits(): array
   {
       return [
           self::PENDING,
           self::VALIDATED,
           self::REJECTED
       ];
   }
}
