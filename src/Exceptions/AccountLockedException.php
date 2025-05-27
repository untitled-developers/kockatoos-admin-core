<?php

namespace UntitledDevelopers\KockatoosAdminCore\Exceptions;
use Exception;
class AccountLockedException extends Exception
{
    public function __construct($message = "Account is locked. Please contact support or try again later.", $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
