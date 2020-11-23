<?php

namespace Lturi\SymfonyExtensions\Framework\Validators;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class SafeString extends Constraint
{
    /** @var string */
    public $message = 'The string "{{ string }}" does not contains a XSS safe string.';
}
