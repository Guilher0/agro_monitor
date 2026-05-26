<?php

namespace Tests\Feature;

use App\Models\CattleLot;
use App\Models\CattleWeightLog;
use App\Models\FinancialTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CattleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Executa as migrations do tenant na base de dados SQLite em memória do teste
        $this->artisan('migrate', [
            '--path' => 'database/migrations/tenant',
            '--database' => 'sqlite',
        ]);
    }

    /**
     * Testa se um lote de gado pode ser criado com sucesso.
     */
    public function test_can_create_cattle_lot(): void
    {
        $lot = CattleLot::create([
            'tenant_id' => 'fazenda-teste',
            'name' => 'Lote Recria pasto 1',
            'animal_count' => 45,
            'initial_avg_weight_kg' => 360.50,
            'current_avg_weight_kg' => 360.50,
            'total_purchase_cost' => 120000.00,
            'status' => 'active',
            'uf' => 'TO',
        ]);

        $this->assertDatabaseHas('cattle_lots', [
            'name' => 'Lote Recria pasto 1',
            'animal_count' => 45,
            'initial_avg_weight_kg' => 360.50,
            'current_avg_weight_kg' => 360.50,
        ]);
    }

    /**
     * Testa se o registro de pesagem atualiza automaticamente o peso médio do lote pai.
     */
    public function test_weighing_log_syncs_weight_automatically(): void
    {
        $lot = CattleLot::create([
            'tenant_id' => 'fazenda-teste',
            'name' => 'Lote Teste Peso',
            'animal_count' => 10,
            'initial_avg_weight_kg' => 300.00,
            'current_avg_weight_kg' => 300.00,
            'total_purchase_cost' => 25000.00,
            'status' => 'active',
            'uf' => 'TO',
        ]);

        // Adiciona uma primeira pesagem mais antiga
        $log1 = CattleWeightLog::create([
            'tenant_id' => 'fazenda-teste',
            'cattle_lot_id' => $lot->id,
            'weight_date' => now()->subDays(10)->format('Y-m-d'),
            'avg_weight_kg' => 310.00,
        ]);

        $lot->refresh();
        $this->assertEquals(310.00, (float) $lot->current_avg_weight_kg);

        // Adiciona uma segunda pesagem mais recente e pesada
        $log2 = CattleWeightLog::create([
            'tenant_id' => 'fazenda-teste',
            'cattle_lot_id' => $lot->id,
            'weight_date' => now()->format('Y-m-d'),
            'avg_weight_kg' => 325.50,
        ]);

        $lot->refresh();
        $this->assertEquals(325.50, (float) $lot->current_avg_weight_kg);

        // Deleta a pesagem mais recente: deve reverter para a anterior
        $log2->delete();
        $lot->refresh();
        $this->assertEquals(310.00, (float) $lot->current_avg_weight_kg);
    }

    /**
     * Testa se marcar o lote como vendido dispara a transação financeira de receita via Observer.
     */
    public function test_selling_lot_triggers_financial_transaction_observer(): void
    {
        $lot = CattleLot::create([
            'tenant_id' => 'fazenda-teste',
            'name' => 'Lote Comercial Venda',
            'animal_count' => 20,
            'initial_avg_weight_kg' => 400.00,
            'current_avg_weight_kg' => 450.00,
            'total_purchase_cost' => 60000.00,
            'status' => 'active',
            'uf' => 'TO',
        ]);

        // Transações financeiras devem estar vazias para este lote
        $this->assertEquals(0, FinancialTransaction::where('cattle_lot_id', $lot->id)->count());

        // Marca como vendido
        $lot->update([
            'status' => 'sold',
            'sold_amount' => 95000.00,
            'sold_at' => now()->format('Y-m-d'),
        ]);

        // Deve ter criado automaticamente a receita no financeiro
        $this->assertDatabaseHas('financial_transactions', [
            'cattle_lot_id' => $lot->id,
            'type' => 'income',
            'category' => 'Venda de Gado',
            'amount' => 95000.00,
        ]);

        // Se reverter para ativo, deve remover a transação
        $lot->update(['status' => 'active']);
        $this->assertEquals(0, FinancialTransaction::where('cattle_lot_id', $lot->id)->where('type', 'income')->count());
    }
}
