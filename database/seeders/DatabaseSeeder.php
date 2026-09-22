<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Credenciais do admin vêm do .env (ADMIN_EMAIL / ADMIN_PASSWORD).
     * Em produção, defina valores próprios antes de rodar o seed.
     */
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@agenda.local')]);
        $admin->name = 'Administrador';
        $admin->password = Hash::make(env('ADMIN_PASSWORD', 'trocar-esta-senha'));
        $admin->role = User::ROLE_ADMIN;
        $admin->email_verified_at = now();
        $admin->save();

        // Atendente único por enquanto; a estrutura já suporta vários no futuro.
        $staff = Staff::firstOrCreate(['name' => 'Atendimento']);

        if ($staff->availabilityRules()->doesntExist()) {
            foreach ([1, 2, 3, 4, 5] as $weekday) { // segunda a sexta
                $staff->availabilityRules()->create([
                    'weekday' => $weekday,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                ]);
            }
        }

        // Produtos são cadastrados pelo admin no painel — o seeder não inventa nenhum.
    }
}
