<?php

namespace Lturi\SymfonyExtensions\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class UnauthorizedUserException extends HttpException {
    public function __construct (string $message = null, \Throwable $previous = null, array $headers = [], ?int $code = 0)
    {
        parent::__construct(401, $message, $previous, $headers, $code);
    }
}