<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: FieldLog (Caderno de Campo)
 *
 * Registra cada atividade agrícola realizada em um talhão.
 * É o coração operacional do AgroMonitor.
 *
 * Regra de negócio — Cálculo de custo (executado no FieldLogService antes de salvar):
 *   total_cost = (machine_hours × asset.hourly_rate) + (input_quantity × input_unit_price)
 *   O valor é armazenado na tabela para performance em relatórios e PDFs.
 *
 * Regra de negócio — Geração automática de transação financeira:
 *   Quando generates_transaction = true, o FieldLogObserver cria automaticamente
 *   uma FinancialTransaction do tipo 'expense' com amount = total_cost.
 *
 * Relacionamentos (banco de tenant):
 * - N:1 com Plot             — obrigatório, todo registro pertence a um talhão.
 * - N:1 com Asset            — opcional, null se atividade for manual (sem máquina).
 * - 1:0..1 com FinancialTransaction — pode gerar uma movimentação financeira.
 */
class FieldLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plot_id',
        'asset_id',
        'user_id',
        'activity_type',
        'description',
        'log_date',
        'machine_hours',
        'input_name',
        'input_quantity',
        'input_unit_price',
        'total_cost',
        'generates_transaction',
    ];

    protected $casts = [
        'log_date'              => 'date',
        'machine_hours'         => 'decimal:1',
        'input_quantity'        => 'decimal:3',
        'input_unit_price'      => 'decimal:2',
        'total_cost'            => 'decimal:2',
        'generates_transaction' => 'boolean',
    ];

    /**
     * Talhão onde a atividade foi realizada.
     * Relação: N:1 (muitos registros → um talhão).
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * Ativo (máquina) utilizado na atividade. Nullable — atividades manuais não têm máquina.
     * Relação: N:1 (muitos registros → um ativo, opcional).
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Movimentação financeira gerada por este registro (quando generates_transaction = true).
     * Relação: 1:0..1 (um registro → zero ou uma transação financeira).
     */
    public function financialTransaction(): HasOne
    {
        return $this->hasOne(FinancialTransaction::class);
    }
}
