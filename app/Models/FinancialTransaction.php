<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model: FinancialTransaction (Movimentação Financeira)
 *
 * Controle financeiro do produtor. Registra entradas (vendas de safra, subsídios)
 * e saídas (insumos, mão-de-obra, manutenção) vinculadas a talhões.
 *
 * Regra de negócio — Geração automática:
 *   Quando FieldLog.generates_transaction = true, o FieldLogObserver cria
 *   automaticamente um registro aqui com:
 *     type      = 'expense'
 *     amount    = field_log.total_cost
 *     category  = derivado do field_log.activity_type
 *
 * Regra de negócio — Lucro por Talhão (base do Dashboard ApexCharts):
 *   SELECT plot_id,
 *     SUM(CASE WHEN type='income'  THEN amount ELSE 0 END) as receita,
 *     SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as custo,
 *     (receita - custo) as lucro
 *   FROM financial_transactions
 *   GROUP BY plot_id
 *
 * Relacionamentos (banco de tenant):
 * - N:1 com FieldLog — opcional, null = lançamento manual pelo produtor.
 * - N:1 com Plot     — optional, null = despesa/receita geral da fazenda.
 */
class FinancialTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'field_log_id',
        'plot_id',
        'type',
        'category',
        'amount',
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Registro do caderno de campo que originou esta transação.
     * Null quando o lançamento é manual (ex: venda de soja, arrendamento).
     * Relação: N:1 (muitas transações → um registro de campo, opcional).
     */
    public function fieldLog(): BelongsTo
    {
        return $this->belongsTo(FieldLog::class);
    }

    /**
     * Talhão ao qual esta transação está vinculada.
     * Null quando é uma despesa/receita geral da fazenda sem talhão específico.
     * Relação: N:1 (muitas transações → um talhão, opcional).
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }
}
