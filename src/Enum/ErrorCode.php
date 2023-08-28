<?php

namespace App\Enum;

class ErrorCode
{
    public const DUPLICATE_USER = "EM01"; // duplicate user
    public const BAD_REQUEST = "EM02"; // bad request

    public const DULICATE_TONTINE = "EM03"; // duplicate tontine

    public const AMOUNT_REJECTED = "EM04"; // The amount is greater than the cash flow amount
    public const DELAY_EXPIRED = "EM05"; // max date of loan have been crossed

    public const ALREADY_VOTED = "EM06"; // Member have already voted

    public const UNAUTHORIZED_USER = "EM07"; // invalid credentials

}
