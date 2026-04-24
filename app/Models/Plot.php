<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: Plot (Talhão)
 *
 * Unidade central de custo e produtividade do AgroMonitor.
 * Um talhão é uma subdivisão de área da fazenda com cultura e safra definidas.
 *
 * Regra de negócio — Custo Operacional:
 *   Custo = SUM(field_logs.total_cost WHERE plot_id = this.id)
 *
 * Regra de negócio — Lucro por Talhão (base do gráfico ApexCharts no Dashboard):
 *   Lucro = SUM(financial_transactions.amount WHERE type='income' AND plot_id = this.id)
 *          - SUM(financial_transactions.amount WHERE type='expense' AND plot_id = this.id)
 *
 * Relacionamentos (banco de tenant):
 * - 1:N com FieldLog              — registros de atividades neste talhão.
 * - 1:N com FinancialTransaction  — movimentações financeiras vinculadas ao talhão.
 */
class Plot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'area_hectares',
        'culture',
        'season',
        'location_coordinates',
        'soil_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'area_hectares'        => 'decimal:2',
        'location_coordinates' => 'array',
    ];

    /**
     * Registros do caderno de campo realizados neste talhão.
     * Relação: 1:N (um talhão → muitos registros).
     */
    public function fieldLogs(): HasMany
    {
        return $this->hasMany(FieldLog::class);
    }

    /**
     * Movimentações financeiras vinculadas a este talhão.
     * Relação: 1:N (um talhão → muitas transações financeiras).
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
