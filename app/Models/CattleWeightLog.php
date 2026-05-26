<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model: CattleWeightLog (Registro de Pesagem)
 *
 * Registra a data e o peso médio estimado para um lote de gado.
 * Dispara automaticamente a atualização do peso do lote pai.
 */
class CattleWeightLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'cattle_lot_id',
        'weight_date',
        'avg_weight_kg',
        'notes',
    ];

    protected $casts = [
        'weight_date' => 'date',
        'avg_weight_kg' => 'decimal:2',
    ];

    /**
     * Lote de gado ao qual esta pesagem pertence.
     * Relação: N:1 (muitas pesagens → um lote).
     */
    public function cattleLot(): BelongsTo
    {
        return $this->belongsTo(CattleLot::class);
    }

    /**
     * Boot do modelo para atualizar o peso do lote pai quando pesagens mudam.
     */
    protected static function booted(): void
    {
        static::saved(function (CattleWeightLog $log) {
            $log->cattleLot?->updateCurrentWeight();
        });

        static::deleted(function (CattleWeightLog $log) {
            $log->cattleLot?->updateCurrentWeight();
        });
    }
}
