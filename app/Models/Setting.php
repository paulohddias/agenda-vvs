<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações da agenda (antecedência mínima, etc.), editáveis pelo painel sem precisar
 * de deploy. Cada chave sobrescreve o valor equivalente de config('agenda.*') — ver
 * AppServiceProvider::boot(), que aplica isso em toda requisição.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    /** @return array<string, string> */
    public static function allCached(): array
    {
        if (! Schema::hasTable('settings')) {
            return []; // ainda não migrado (ex.: durante o primeiro deploy)
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => self::query()->pluck('value', 'key')->all());
    }

    public static function set(string $key, string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }
}
