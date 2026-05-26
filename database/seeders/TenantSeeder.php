<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
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
        /** @var Tenant $tenant */
        $tenant = Tenant::updateOrCreate(
            ['id' => 'fazenda-demo'],
            [
                'name' => 'Fazenda Demo',
                'slug' => 'fazenda-demo',
                'plan' => 'pro',
                'trial_ends_at' => null,
            ]
        );

        // Domínio para acesso multi-tenant (usado pelo stancl/tenancy)
        $tenant->domains()->firstOrCreate(['domain' => 'fazenda-demo.localhost']);

        // ──────────────────────────────────────────────────────────────────────
        // 2. Cria o usuário owner no banco landlord, vinculado ao tenant
        // ──────────────────────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'owner@fazenda-demo.test'],
            [
                'name' => 'João da Silva',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
            ]
        );

        // Cria um usuário manager de exemplo
        User::updateOrCreate(
            ['email' => 'manager@fazenda-demo.test'],
            [
                'name' => 'Maria Souza',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'role' => 'manager',
            ]
        );

        // Se o tenant já existia (estado parcial), garante criação/migração do banco antes do seed.
        $databaseName = $tenant->database()->getName();
        $databaseManager = $tenant->database()->manager();

        if (! $databaseManager->databaseExists($databaseName)) {
            $databaseManager->createDatabase($tenant);
        }

        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->getTenantKey()],
            '--force' => true,
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
