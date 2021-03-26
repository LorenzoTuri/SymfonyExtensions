<?php

namespace Lturi\SymfonyExtensions\Framework\Exception;

use Exception;
use Throwable;

class UnrecognizableFilterException extends Exception {
    public function __construct($filterType, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            "Unrecognizable filter {$filterType}",
            $code,
            $previous
        );
    }
}