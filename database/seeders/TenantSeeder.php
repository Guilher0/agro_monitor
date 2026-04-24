<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * TenantSeeder
 *
 * Cria o tenant de demonstração "Fazenda Demo" e o usuário owner.
 * Inicializa o contexto de tenancy e delega os dados de demo ao DemoDataSeeder.
 *
 * Credenciais do usuário demo:
 *   E-mail: owner@fazenda-demo.test
 *   Senha:  password
 *
 * Uso:
 *   php artisan db:seed --class=TenantSeeder
 *   php artisan db:seed   (chamado pelo DatabaseSeeder)
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────────────────────────────────────────────
        // 1. Cria o tenant no banco landlord (agro_monitor)
        // ──────────────────────────────────────────────────────────────────────
        /** @var \App\Models\Tenant $tenant */
        $tenant = Tenant::create([
            'id'            => 'fazenda-demo',
            'name'          => 'Fazenda Demo',
            'slug'          => 'fazenda-demo',
            'plan'          => 'pro',
            'trial_ends_at' => null,
        ]);

        // Domínio para acesso multi-tenant (usado pelo stancl/tenancy)
        $tenant->domains()->create(['domain' => 'fazenda-demo.localhost']);

        // ──────────────────────────────────────────────────────────────────────
        // 2. Cria o usuário owner no banco landlord, vinculado ao tenant
        // ──────────────────────────────────────────────────────────────────────
        $owner = User::create([
            'name'              => 'João da Silva',
            'email'             => 'owner@fazenda-demo.test',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'tenant_id'         => $tenant->id,
            'role'              => 'owner',
        ]);

        // Cria um usuário manager de exemplo
        User::create([
            'name'              => 'Maria Souza',
            'email'             => 'manager@fazenda-demo.test',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'tenant_id'         => $tenant->id,
            'role'              => 'manager',
        ]);

        // ──────────────────────────────────────────────────────────────────────
        // 3. Inicializa o contexto de tenancy e popula o banco do tenant
        //    O DemoDataSeeder opera no banco agro_tenant_fazenda-demo
        // ──────────────────────────────────────────────────────────────────────
        tenancy()->initialize($tenant);

        $this->call(DemoDataSeeder::class);

        tenancy()->end();
    }
}
