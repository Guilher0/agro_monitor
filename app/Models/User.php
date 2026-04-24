<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /**
     * Força o uso da conexão central (landlord) independentemente do contexto
     * de tenancy ativo. Usuários estão no banco agro_monitor, não nos bancos
     * de tenant.
     */
    protected $connection = 'mysql';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atributos atribuíveis em massa.
     * role: nível de acesso do usuário dentro do tenant (owner, manager, worker, admin).
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Tenant ao qual este usuário pertence.
     * Null indica um super-admin da plataforma (sem tenant associado).
     *
     * Relação: N:1 (muitos usuários → um tenant) no banco central (landlord).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
