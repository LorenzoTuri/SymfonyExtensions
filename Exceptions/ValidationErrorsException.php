<?php

namespace Lturi\SymfonyExtensions\Exceptions;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ValidationErrorsException extends BadRequestHttpException{
    public function __construct (\Traversable $messages, \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        $errors = [];
        foreach ($messages as $validationError) {
            $propertyPath =
                $validationError->getPropertyPath() ?
                    $validationError->getPropertyPath() :
                    get_class($validationError->getRoot());
            $errors[] = $propertyPath.": ".$validationError->getMessage();
        }
        parent::__construct(implode(", ", $errors), $previous, $code, $headers);
    }
}