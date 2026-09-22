<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Confere a resposta do Google reCAPTCHA v2 direto na API de verificação,
 * sem depender de um pacote (o mais usado, anhskohbo/no-captcha, ainda exige
 * uma versão do Guzzle mais antiga que a que o Laravel 13 já usa).
 */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Confirme que você não é um robô.');

            return;
        }

        $secret = config('recaptcha.secret_key');

        if (! $secret) {
            Log::warning('RECAPTCHA_SECRET_KEY não configurada; validação de captcha ignorada.');

            return;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secret,
                'response' => $value,
            ]);

            if (! $response->successful() || ! ($response->json('success') ?? false)) {
                $fail('Não foi possível confirmar o captcha. Tente novamente.');
            }
        } catch (\Throwable $e) {
            Log::error('Falha ao verificar reCAPTCHA: '.$e->getMessage());
            $fail('Não foi possível confirmar o captcha. Tente novamente.');
        }
    }
}
