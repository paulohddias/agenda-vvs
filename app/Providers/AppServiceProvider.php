<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sem rota "dashboard": um admin já logado que abrir /admin/login vai para o painel, não para o site público.
        RedirectIfAuthenticated::redirectUsing(fn () => route('admin.dashboard'));

        // Valores editados em /admin/settings sobrescrevem os padrões de config/agenda.php,
        // sem precisar de deploy. Protegido para nunca quebrar um artisan/migrate antes do banco existir.
        try {
            foreach (Setting::allCached() as $key => $value) {
                config(["agenda.$key" => is_numeric($value) ? (int) $value : $value]);
            }
        } catch (\Throwable) {
            // Banco ainda sem a tabela settings (ex.: antes da primeira migration) — segue com os padrões.
        }
    }
}
