<?php

namespace App\Rules;

use App\Support\DocumentValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfOuCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (! DocumentValidator::isValid($digits)) {
            $fail('Informe um CPF ou CNPJ válido.');
        }
    }
}
