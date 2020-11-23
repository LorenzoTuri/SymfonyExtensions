<?php

namespace Lturi\SymfonyExtensions\Framework\Validators;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SafeStringValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        // Only match SafeString
        if (!$constraint instanceof SafeString) {
            throw new UnexpectedTypeException($constraint, SafeString::class);
        }
        // And non-empty values, empty is valid
        if (null === $value || '' === $value) {
            return;
        }
        // Usable only on strings
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }
        // Should not contain script tags
        if (stripos($value, "<script") !== false) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
        // Take care of html tag events, like [onclick="doSomethingWrong()"]
        if (preg_match("/on([a-zA-Z]+)=\"[^\"]+\"/", $value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
        // TODO: add violations for other XSS possibilities
    }
}
