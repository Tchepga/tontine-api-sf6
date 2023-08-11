<?php

namespace App\Enum;

class ErrorCode
{
    public const EM01 = "EM01"; // duplicate user
    public const EM02 = "EM02"; // bad request

    public const EM03 = "EM03"; // duplicate tontine

    public const EM04 = "EM04"; // The amount is greater than the cash flow amount
    public const EM05 = "EM05"; // max date of loan have been crossed

    public const EM06 = "EM06"; // Member have already voted

}
