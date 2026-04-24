<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\FieldLog;
use Illuminate\Support\Facades\DB;

/**
 * FieldLogService
 *
 * Encapsula as regras de negócio do Caderno de Campo:
 *
 * 1. Cálculo de custo operacional antes de persistir:
 *    total_cost = (machine_hours × asset.hourly_rate) + (input_quantity × input_unit_price)
 *
 * 2. Atualização do acumulador de horas do ativo após salvar:
 *    asset.total_hours += machine_hours
 *
 * 3. Orquestra a criação dentro de uma transação de banco de dados para garantir
 *    consistência mesmo que o Observer falhe ao criar a FinancialTransaction.
 */
class FieldLogService
{
    /**
     * Cria um novo registro no Caderno de Campo.
     *
     * Calcula o custo total, persiste o FieldLog e atualiza o acumulador de
     * horas do ativo — tudo dentro de uma transação atômica.
     *
     * @param  array<string, mixed>  $data  Dados validados do formulário.
     * @return FieldLog O registro criado com total_cost já calculado.
     */
    public function create(array $data): FieldLog
    {
        return DB::transaction(function () use ($data) {
            $data['total_cost'] = $this->calculateCost($data);

            $fieldLog = FieldLog::create($data);

            // Atualiza o acumulador de horas do ativo após o registro de uso
            if ($fieldLog->asset_id && isset($data['machine_hours'])) {
                $this->incrementAssetHours($fieldLog->asset_id, (float) $data['machine_hours']);
            }

            return $fieldLog;
        });
    }

    /**
     * Atualiza um registro existente do Caderno de Campo.
     *
     * Recalcula o custo e ajusta o delta de horas do ativo (diferença entre
     * o valor antigo e o novo para não duplicar o acumulador).
     *
     * @param  FieldLog              $fieldLog  Registro a ser atualizado.
     * @param  array<string, mixed>  $data      Dados validados do formulário.
     * @return FieldLog O registro atualizado.
     */
    public function update(FieldLog $fieldLog, array $data): FieldLog
    {
        return DB::transaction(function () use ($fieldLog, $data) {
            $oldMachineHours  = (float) ($fieldLog->machine_hours ?? 0);
            $oldAssetId       = $fieldLog->asset_id;

            $data['total_cost'] = $this->calculateCost($data);

            $fieldLog->update($data);

            // Reverte as horas do ativo anterior se trocou de ativo
            if ($oldAssetId && $oldAssetId !== ($data['asset_id'] ?? null)) {
                $this->incrementAssetHours($oldAssetId, -$oldMachineHours);
            }

            // Aplica o delta de horas no ativo atual
            if ($fieldLog->asset_id && isset($data['machine_hours'])) {
                $newMachineHours = (float) $data['machine_hours'];
                $delta = ($oldAssetId === $fieldLog->asset_id)
                    ? $newMachineHours - $oldMachineHours
                    : $newMachineHours;

                if ($delta !== 0.0) {
                    $this->incrementAssetHours($fieldLog->asset_id, $delta);
                }
            }

            return $fieldLog->fresh();
        });
    }

    /**
     * Calcula o custo total de uma atividade agrícola.
     *
     * Fórmula do agent.md:
     *   Custo = (Horas de Máquina × Valor/Hora) + (Quantidade de Insumo × Preço Unitário)
     *
     * @param  array<string, mixed>  $data
     * @return float
     */
    public function calculateCost(array $data): float
    {
        $machineCost = 0.0;
        $inputCost   = 0.0;

        // Custo da máquina: busca o hourly_rate do ativo para não depender do front-end
        if (!empty($data['asset_id']) && !empty($data['machine_hours'])) {
            $asset       = Asset::find($data['asset_id']);
            $machineCost = (float) ($asset?->hourly_rate ?? 0) * (float) $data['machine_hours'];
        }

        // Custo do insumo (herbicida, fertilizante, semente, etc.)
        if (!empty($data['input_quantity']) && !empty($data['input_unit_price'])) {
            $inputCost = (float) $data['input_quantity'] * (float) $data['input_unit_price'];
        }

        return round($machineCost + $inputCost, 2);
    }

    /**
     * Incrementa (ou decrementa, se negativo) o acumulador de horas de um ativo.
     * Usa incremento atômico para evitar race conditions em ambiente multi-usuário.
     */
    private function incrementAssetHours(int $assetId, float $hours): void
    {
        Asset::where('id', $assetId)->increment('total_hours', $hours);
    }
}
