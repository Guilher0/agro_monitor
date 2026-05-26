<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: CattleLot (Lote de Gado)
 *
 * Unidade central para gestão de rebanho de corte no AgroMonitor.
 * Permite acompanhar a quantidade de animais, peso médio, custos e simular retorno.
 *
 * Relacionamentos:
 * - 1:N com CattleWeightLog      — histórico de pesagens deste lote.
 * - 1:N com FinancialTransaction  — despesas e receitas vinculadas a este lote.
 */
class CattleLot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'animal_count',
        'initial_avg_weight_kg',
        'current_avg_weight_kg',
        'total_purchase_cost',
        'status',
        'uf',
        'sold_amount',
        'sold_at',
    ];

    protected $casts = [
        'animal_count' => 'integer',
        'initial_avg_weight_kg' => 'decimal:2',
        'current_avg_weight_kg' => 'decimal:2',
        'total_purchase_cost' => 'decimal:2',
        'sold_amount' => 'decimal:2',
        'sold_at' => 'date',
    ];

    /**
     * Histórico de pesagens do lote.
     * Relação: 1:N (um lote → muitas pesagens).
     */
    public function weightLogs(): HasMany
    {
        return $this->hasMany(CattleWeightLog::class);
    }

    /**
     * Transações financeiras vinculadas a este lote (despesas de manejo e receita de venda).
     * Relação: 1:N (um lote → muitas transações).
     */
    public function financialTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Atualiza o peso médio atual do lote com base na pesagem mais recente.
     * Caso não haja pesagens, retorna ao peso inicial.
     */
    public function updateCurrentWeight(): void
    {
        $latestLog = $this->weightLogs()
            ->orderByDesc('weight_date')
            ->orderByDesc('id')
            ->first();

        $this->update([
            'current_avg_weight_kg' => $latestLog ? $latestLog->avg_weight_kg : $this->initial_avg_weight_kg,
        ]);
    }
}
