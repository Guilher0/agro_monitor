<?php

namespace App\Observers;

use App\Models\FieldLog;
use App\Models\FinancialTransaction;

/**
 * FieldLogObserver
 *
 * Escuta os eventos do Model FieldLog e aplica as regras de negócio:
 *
 * — Após criar (created):
 *   Se generates_transaction = true, cria automaticamente uma FinancialTransaction
 *   do tipo 'expense' com amount = total_cost do registro.
 *
 * — Após atualizar (updated):
 *   Se generates_transaction = true e a transação já existir, sincroniza o valor.
 *   Se generates_transaction foi alterado para true, cria a transação.
 *   Se generates_transaction foi alterado para false, remove a transação.
 *
 * — Antes de deletar (deleting):
 *   Soft-deletes a FinancialTransaction vinculada para manter a integridade do
 *   histórico financeiro.
 */
class FieldLogObserver
{
    /**
     * Após criação: gera a transação financeira se solicitado.
     */
    public function created(FieldLog $fieldLog): void
    {
        if ($fieldLog->generates_transaction) {
            $this->createTransaction($fieldLog);
        }
    }

    /**
     * Após atualização: sincroniza a transação financeira conforme o novo estado.
     */
    public function updated(FieldLog $fieldLog): void
    {
        $existingTransaction = $fieldLog->financialTransaction;

        if ($fieldLog->generates_transaction) {
            if ($existingTransaction) {
                // Atualiza o valor se o custo mudou
                $existingTransaction->update([
                    'amount'           => $fieldLog->total_cost,
                    'description'      => $this->buildDescription($fieldLog),
                    'transaction_date' => $fieldLog->log_date,
                    'plot_id'          => $fieldLog->plot_id,
                    'category'         => $this->resolveCategory($fieldLog->activity_type),
                ]);
            } else {
                // generates_transaction foi ativado agora — cria a transação
                $this->createTransaction($fieldLog);
            }
        } elseif ($existingTransaction) {
            // generates_transaction foi desativado — remove a transação
            $existingTransaction->delete();
        }
    }

    /**
     * Antes de deletar: remove a transação financeira vinculada (soft-delete).
     */
    public function deleting(FieldLog $fieldLog): void
    {
        $fieldLog->financialTransaction?->delete();
    }

    /**
     * Cria a FinancialTransaction a partir dos dados do FieldLog.
     */
    private function createTransaction(FieldLog $fieldLog): FinancialTransaction
    {
        return FinancialTransaction::create([
            'tenant_id'        => $fieldLog->tenant_id,
            'field_log_id'     => $fieldLog->id,
            'plot_id'          => $fieldLog->plot_id,
            'type'             => 'expense',
            'category'         => $this->resolveCategory($fieldLog->activity_type),
            'amount'           => $fieldLog->total_cost,
            'description'      => $this->buildDescription($fieldLog),
            'transaction_date' => $fieldLog->log_date,
        ]);
    }

    /**
     * Mapeia o tipo de atividade do caderno para uma categoria financeira legível.
     * Usado na listagem de despesas do produtor.
     */
    private function resolveCategory(string $activityType): string
    {
        return match ($activityType) {
            'planting'     => 'Plantio',
            'spraying'     => 'Defensivos / Pulverização',
            'harvesting'   => 'Colheita',
            'fertilizing'  => 'Fertilizantes / Adubação',
            'maintenance'  => 'Manutenção de Máquinas',
            'irrigation'   => 'Irrigação',
            default        => 'Outros',
        };
    }

    /**
     * Monta a descrição da transação a partir do registro de campo.
     * Inclui talhão e insumo (quando houver) para rastreabilidade financeira.
     */
    private function buildDescription(FieldLog $fieldLog): string
    {
        $parts = [$fieldLog->description];

        if ($fieldLog->input_name) {
            $parts[] = "Insumo: {$fieldLog->input_name}";
        }

        return implode(' — ', $parts);
    }
}
