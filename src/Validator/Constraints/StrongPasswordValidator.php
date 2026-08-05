<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class StrongPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof StrongPassword) {
            throw new UnexpectedTypeException($constraint, StrongPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $errors = [];
        if (\strlen($value) < 8) {
            $errors[] = 'au moins 8 caractères';
        }
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'une majuscule';
        }
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'une minuscule';
        }
        if (!preg_match('/\d/', $value)) {
            $errors[] = 'un chiffre';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $errors[] = 'un caractère spécial';
        }

        if ($errors !== []) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ details }}', implode(', ', $errors))
                ->addViolation();
        }
    }
}
