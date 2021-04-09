<?php

namespace Lturi\SymfonyExtensions\Framework\Exception;

use Exception;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Traversable;

class EntityValidationException extends Exception {
    /**
     * EntityValidationException constructor.
     * @param Traversable<ConstraintViolation> $messages
     * @param string $entityName
     */
    public function __construct (Traversable $messages, string $entityName)
    {
        $errors = [];
        /** @var ConstraintViolation $validationError */
        foreach ($messages as $validationError) {
            if ($validationError->getConstraint()->getTargets() === Constraint::CLASS_CONSTRAINT) {
                $errors[] = [
                    "path" => "$entityName.".$validationError->getPropertyPath(),
                    "message" => $validationError->getMessage()
                ];
            } else {
                $errors[] = [
                    "path" => "$entityName.".$validationError->getPropertyPath(),
                    "message" => $validationError->getMessage()
                ];
            }
        }
        parent::__construct(implode("\n", array_map(function ($error) {
            return $error["path"].":".$error["message"];
        }, $errors)));
    }
}