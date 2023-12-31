<?php
 namespace App\Exception;

use Exception;
use Throwable;

class CollectionException extends Exception
{

    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        // some code

        // make sure everything is assigned properly
        parent::__construct('Collection error : ' . $message, $code, $previous);
    }

    // custom string representation of object
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
