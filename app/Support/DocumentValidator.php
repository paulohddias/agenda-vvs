<?php

namespace App\Support;

/** Valida CPF (11 dígitos) e CNPJ (14 dígitos) pelo dígito verificador oficial. */
class DocumentValidator
{
    public static function isValid(string $digits): bool
    {
        return match (strlen($digits)) {
            11 => self::isValidCpf($digits),
            14 => self::isValidCnpj($digits),
            default => false,
        };
    }

    public static function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($pos = 9; $pos <= 10; $pos++) {
            $sum = 0;
            for ($i = 0; $i < $pos; $i++) {
                $sum += (int) $cpf[$i] * ($pos + 1 - $i);
            }
            $digit = ($sum * 10) % 11;
            if ($digit === 10) {
                $digit = 0;
            }
            if ($digit !== (int) $cpf[$pos]) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $calc = function (string $cnpj, int $length) {
            // Peso do primeiro dígito é (tamanho - 7); depois desce até 2 e volta para 9.
            $weights = [];
            $factor = $length - 7;
            for ($i = 0; $i < $length; $i++) {
                $weights[] = $factor;
                $factor--;
                if ($factor < 2) {
                    $factor = 9;
                }
            }

            $sum = 0;
            for ($i = 0; $i < $length; $i++) {
                $sum += (int) $cnpj[$i] * $weights[$i];
            }
            $digit = $sum % 11;

            return $digit < 2 ? 0 : 11 - $digit;
        };

        return $calc($cnpj, 12) === (int) $cnpj[12] && $calc($cnpj, 13) === (int) $cnpj[13];
    }
}
