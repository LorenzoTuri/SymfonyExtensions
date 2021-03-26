<?php

namespace Lturi\SymfonyExtensions\Framework\Exception;

use Exception;
use Throwable;

class EntityIdNotFoundException extends Exception {
    public function __construct(
        $id = "",
        $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct("Id {$id} not found", $code, $previous);
    }
}