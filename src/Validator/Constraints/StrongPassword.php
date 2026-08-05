<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class StrongPassword extends Constraint
{
    public string $message = 'Le mot de passe doit contenir : {{ details }}.';
}
