<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Cria:
     * 1. Um usuário admin global (sem tenant) para gerenciamento do sistema.
     * 2. O tenant de demonstração com dados realistas via TenantSeeder.
     */
    public function run(): void
    {
        // Usuário admin da plataforma (role: admin, sem tenant)
        User::firstOrCreate(
            ['email' => 'admin@agromonitor.app'],
            [
                'name' => 'Admin AgroMonitor',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tenant_id' => null,
            ]
        );

        // Tenant de demonstração com fazenda fictícia e dados realistas
        $this->call(TenantSeeder::class);
    }
}
