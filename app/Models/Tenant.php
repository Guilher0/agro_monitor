<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model: Tenant
 *
 * Representa uma fazenda/empresa produtora no modelo SaaS do AgroMonitor.
 * Cada tenant possui um banco de dados isolado gerenciado pelo stancl/tenancy.
 *
 * Colunas customizadas (via VirtualColumn do stancl):
 * - name: razão social ou nome da fazenda
 * - slug: identificador URL amigável (ex: "fazenda-verde-mg")
 * - plan: plano de assinatura (free | pro | enterprise)
 * - trial_ends_at: data de expiração do período de teste
 *
 * Relação no banco central (landlord):
 * - 1:N com users — um tenant pode ter vários usuários com roles diferentes.
 * - 1:N com domains — um tenant pode ter múltiplos domínios/subdomínios.
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Colunas personalizadas armazenadas dentro do JSON `data`.
     * O stancl/tenancy usa VirtualColumn para isso por padrão.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'plan',
            'trial_ends_at',
        ];
    }

    /**
     * Usuários associados a este tenant (banco central).
     * Relação: 1:N (um tenant → muitos usuários com roles distintas).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
