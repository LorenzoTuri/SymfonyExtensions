<?php

namespace Lturi\SymfonyExtensions\Framework\Exception;

use Exception;
use Throwable;

class UnauthorizedUserException extends Exception {
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct("Unauthorized user", $code, $previous);
    }
}