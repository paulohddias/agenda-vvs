<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca a razão social de um CNPJ na ReceitaWS (https://developers.receitaws.com.br).
 * Só serve de comodidade para o formulário: qualquer falha devolve null e a pessoa digita.
 */
class ReceitaWsService
{
    public function companyName(string $cnpj): ?string
    {
        $token = config('services.receitaws.token');

        if (! $token || strlen($cnpj) !== 14) {
            return null;
        }

        // Razão social quase nunca muda; guardar evita gastar consulta do plano a cada blur.
        $cached = Cache::get("receitaws:{$cnpj}");
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->get("https://receitaws.com.br/v1/cnpj/{$cnpj}");
        } catch (\Throwable $e) {
            Log::warning('Falha ao consultar ReceitaWS: '.$e->getMessage());

            return null;
        }

        // Em erro (CNPJ inexistente, limite estourado) a API responde {"status": "ERROR", "message": ...}.
        $name = trim((string) $response->json('nome'));

        if (! $response->successful() || $response->json('status') === 'ERROR' || $name === '') {
            return null;
        }

        Cache::put("receitaws:{$cnpj}", $name, now()->addDays(30));

        return $name;
    }
}
