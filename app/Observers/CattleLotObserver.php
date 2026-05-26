<?php

namespace App\Observers;

use App\Models\CattleLot;
use App\Models\FinancialTransaction;

/**
 * CattleLotObserver
 *
 * Aplica as regras de negócio financeiras ao gerenciar o ciclo de vida dos lotes de gado:
 *
 * — Após atualizar (updated):
 *   Se o status foi alterado para 'sold' (lote vendido), gera automaticamente uma receita ('income')
 *   com o valor real de venda fornecido (sold_amount) e data da venda.
 *   Se o lote já estava vendido e o valor ou data foram atualizados, sincroniza a transação.
 *   Se o status voltou de 'sold' para 'active' (venda desfeita), remove a transação.
 *
 * — Antes de deletar (deleting):
 *   Soft-deletes todas as transações financeiras vinculadas a este lote (despesas de manejo e receitas).
 */
class CattleLotObserver
{
    /**
     * Sincroniza a transação de receita após alteração no lote.
     */
    public function updated(CattleLot $cattleLot): void
    {
        $existingSaleTransaction = FinancialTransaction::where('cattle_lot_id', $cattleLot->id)
            ->where('type', 'income')
            ->first();

        if ($cattleLot->status === 'sold') {
            if ($existingSaleTransaction) {
                // Sincroniza os dados caso tenham sido editados
                $existingSaleTransaction->update([
                    'amount' => $cattleLot->sold_amount,
                    'transaction_date' => $cattleLot->sold_at ?? now(),
                    'description' => "Venda do Lote de Gado: {$cattleLot->name} ({$cattleLot->animal_count} cabeças)",
                ]);
            } else {
                // Cria a transação de receita automática
                FinancialTransaction::create([
                    'tenant_id' => $cattleLot->tenant_id,
                    'cattle_lot_id' => $cattleLot->id,
                    'type' => 'income',
                    'category' => 'Venda de Gado',
                    'amount' => $cattleLot->sold_amount,
                    'description' => "Venda do Lote de Gado: {$cattleLot->name} ({$cattleLot->animal_count} cabeças)",
                    'transaction_date' => $cattleLot->sold_at ?? now(),
                ]);
            }
        } else {
            // Se o status voltou para ativo, remove a transação de venda caso ela exista
            $existingSaleTransaction?->delete();
        }
    }

    /**
     * Remove as transações associadas antes do soft-delete do lote.
     */
    public function deleting(CattleLot $cattleLot): void
    {
        // Deleta todas as movimentações vinculadas (receitas de venda e despesas de manejo)
        FinancialTransaction::where('cattle_lot_id', $cattleLot->id)->delete();
    }
}
