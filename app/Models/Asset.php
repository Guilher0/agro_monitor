<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: Asset (Ativo Agrícola)
 *
 * Representa máquinas e equipamentos da fazenda: tratores, colheitadeiras,
 * pulverizadores, implementos, etc.
 *
 * Regra de negócio — Alerta de Manutenção:
 * O accessor `needs_maintenance` retorna true quando:
 *   (total_hours - hours_at_last_maintenance) >= maintenance_alert_hours
 * A interface deve exibir esses ativos com a cor amber-500 do design system.
 *
 * Regra de negócio — Custo Operacional:
 * O custo de um FieldLog com este ativo = machine_hours × hourly_rate.
 *
 * Relacionamentos (banco de tenant):
 * - 1:N com FieldLog — um ativo pode aparecer em múltiplos registros de campo.
 */
class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'serial_number',
        'purchase_date',
        'hourly_rate',
        'total_hours',
        'hours_at_last_maintenance',
        'last_maintenance_at',
        'maintenance_alert_hours',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'last_maintenance_at' => 'date',
        'hourly_rate' => 'decimal:2',
        'total_hours' => 'decimal:1',
        'hours_at_last_maintenance' => 'decimal:1',
    ];

    /**
     * Verifica se o ativo está próximo ou passou do limite de manutenção.
     * Usado para exibir o alerta amber-500 no painel e lista de ativos.
     */
    public function getNeedsMaintenanceAttribute(): bool
    {
        $hoursWithoutMaintenance = $this->total_hours - $this->hours_at_last_maintenance;

        return $hoursWithoutMaintenance >= $this->maintenance_alert_hours;
    }

    /**
     * Registros do caderno de campo que utilizaram este ativo.
     * Relação: 1:N (um ativo → muitos registros de campo).
     */
    public function fieldLogs(): HasMany
    {
        return $this->hasMany(FieldLog::class);
    }
}
