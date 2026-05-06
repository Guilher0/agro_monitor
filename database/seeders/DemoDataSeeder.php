<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\FieldLog;
use App\Models\FinancialTransaction;
use App\Models\Plot;
use App\Services\FieldLogService;
use Illuminate\Database\Seeder;

/**
 * DemoDataSeeder
 *
 * Popula o banco de dados do tenant de demonstração com dados realistas
 * para apresentações do sistema AgroMonitor.
 *
 * Dados inseridos:
 * - 4 ativos agrícolas (2 tratores, 1 colheitadeira, 1 pulverizador)
 * - 5 talhões com culturas variadas
 * - ~15 registros de caderno de campo com custos calculados
 * - FinancialTransactions geradas automaticamente via FieldLogObserver
 *
 * IMPORTANTE: Este seeder deve ser executado dentro de um contexto de tenancy:
 *   tenancy()->initialize($tenant);
 *   $this->call(DemoDataSeeder::class);
 */
class DemoDataSeeder extends Seeder
{
    public function __construct(private readonly FieldLogService $fieldLogService) {}

    public function run(): void
    {
        $tenantId = (string) tenant('id');

        // ───────────────────────────────────────────
        // ATIVOS — Parque de máquinas da Fazenda Demo
        // ───────────────────────────────────────────
        $tractor = Asset::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'John Deere 5090E',
            'type'                      => 'tractor',
            'serial_number'             => 'JD5090E-2019-001',
            'purchase_date'             => '2019-03-15',
            'hourly_rate'               => 185.00,
            'total_hours'               => 1240.5,
            'hours_at_last_maintenance' => 1000.0,
            'last_maintenance_at'       => now()->subMonths(2),
            'maintenance_alert_hours'   => 250,
            'status'                    => 'active',
            'notes'                     => 'Trator principal da propriedade. Revisão anual realizada na dealer John Deere.',
        ]);

        $tractor2 = Asset::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'Massey Ferguson 7718',
            'type'                      => 'tractor',
            'serial_number'             => 'MF7718-2021-007',
            'purchase_date'             => '2021-07-22',
            'hourly_rate'               => 210.00,
            'total_hours'               => 680.0,
            'hours_at_last_maintenance' => 680.0,
            'last_maintenance_at'       => now()->subMonth(),
            'maintenance_alert_hours'   => 250,
            'status'                    => 'active',
            'notes'                     => 'Trator secundário, usado principalmente no talhão Sul.',
        ]);

        $harvester = Asset::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'New Holland CR9.90',
            'type'                      => 'harvester',
            'serial_number'             => 'NH-CR990-2020-003',
            'purchase_date'             => '2020-10-10',
            'hourly_rate'               => 520.00,
            'total_hours'               => 2150.0,
            'hours_at_last_maintenance' => 1900.0,
            'last_maintenance_at'       => now()->subMonths(3),
            'maintenance_alert_hours'   => 250,
            'status'                    => 'active',
            'notes'                     => 'Colheitadeira de alto desempenho. Plataforma de milho substituída em jan/2024.',
        ]);

        $sprayer = Asset::create([
            'tenant_id'                 => $tenantId,
            'name'                      => 'Jacto Uniport 3030',
            'type'                      => 'sprayer',
            'serial_number'             => 'JACTO-3030-2022-012',
            'purchase_date'             => '2022-02-01',
            'hourly_rate'               => 320.00,
            'total_hours'               => 420.0,
            'hours_at_last_maintenance' => 420.0,
            'last_maintenance_at'       => now()->subWeeks(3),
            'maintenance_alert_hours'   => 200,
            'status'                    => 'active',
            'notes'                     => 'Pulverizador autopropelido. Bicos TeeJet trocados em mai/2024.',
        ]);

        // ───────────────────────────────────────────
        // TALHÕES — Subdivisões da fazenda
        // ───────────────────────────────────────────
        $plotA = Plot::create([
            'tenant_id'           => $tenantId,
            'name'                 => 'Talhão A — Soja',
            'area_hectares'        => 48.50,
            'culture'              => 'Soja',
            'season'               => date('Y') . '/' . date('Y'),
            'location_coordinates' => ['lat' => -12.9711, 'lng' => -48.0322, 'zoom' => 14],
            'soil_type'            => 'Latossolo Vermelho',
            'status'               => 'active',
            'notes'                => 'Principal talhão de soja. Solo preparado com calcário na safra anterior.',
        ]);

        $plotB = Plot::create([
            'tenant_id'           => $tenantId,
            'name'                 => 'Talhão B — Milho',
            'area_hectares'        => 32.75,
            'culture'              => 'Milho',
            'season'               => date('Y') . '/' . date('Y'),
            'location_coordinates' => ['lat' => -12.9750, 'lng' => -48.0290, 'zoom' => 14],
            'soil_type'            => 'Latossolo Vermelho-Amarelo',
            'status'               => 'active',
            'notes'                => 'Milho verão. Plantio direto sobre palhada de soja.',
        ]);

        $plotC = Plot::create([
            'tenant_id'           => $tenantId,
            'name'                 => 'Talhão C — Soja (Colhido)',
            'area_hectares'        => 55.20,
            'culture'              => 'Soja',
            'season'               => (date('Y') - 1) . '/' . date('Y'),
            'location_coordinates' => null,
            'soil_type'            => 'Argissolo Vermelho',
            'status'               => 'harvested',
            'notes'                => 'Safra anterior concluída. Produtividade: 58 sc/ha.',
        ]);

        $plotD = Plot::create([
            'tenant_id'           => $tenantId,
            'name'                 => 'Talhão D — Pousio',
            'area_hectares'        => 18.00,
            'culture'              => null,
            'season'               => date('Y') . '/' . date('Y'),
            'location_coordinates' => null,
            'soil_type'            => 'Neossolo Quartzarênico',
            'status'               => 'fallow',
            'notes'                => 'Área em descanso. Aguardando análise de solo para decisão de cultura.',
        ]);

        $plotE = Plot::create([
            'tenant_id'           => $tenantId,
            'name'                 => 'Talhão E — Feijão',
            'area_hectares'        => 22.30,
            'culture'              => 'Feijão',
            'season'               => date('Y') . '/' . date('Y'),
            'location_coordinates' => null,
            'soil_type'            => 'Latossolo Vermelho',
            'status'               => 'active',
            'notes'                => 'Feijão das águas. Segunda safra anual.',
        ]);

        // ───────────────────────────────────────────────────────────────────────
        // CADERNO DE CAMPO — Registros de atividades com custo calculado via
        // FieldLogService (machine_hours × hourly_rate + input_qty × unit_price)
        // O FieldLogObserver cria as FinancialTransactions automaticamente.
        // ───────────────────────────────────────────────────────────────────────
        $userId = 1; // Owner do tenant de demo

        $fieldLogs = [
            // Talhão A — Soja: atividades da safra atual
            [
                'plot_id'              => $plotA->id,
                'asset_id'             => $tractor->id,
                'user_id'              => $userId,
                'activity_type'        => 'planting',
                'description'          => 'Plantio de soja — cultivar Brasmax Bônus IPRO',
                'log_date'             => now()->subMonths(4)->format('Y-m-d'),
                'machine_hours'        => 6.5,
                'input_name'           => 'Brasmax Bônus IPRO',
                'input_quantity'       => 50,
                'input_unit_price'     => 285.00,
                'generates_transaction' => true,
            ],
            [
                'plot_id'              => $plotA->id,
                'asset_id'             => $sprayer->id,
                'user_id'              => $userId,
                'activity_type'        => 'spraying',
                'description'          => 'Aplicação de herbicida pré-emergente',
                'log_date'             => now()->subMonths(3)->subWeeks(2)->format('Y-m-d'),
                'machine_hours'        => 3.0,
                'input_name'           => 'Glifosato 480 CS',
                'input_quantity'       => 25,
                'input_unit_price'     => 22.50,
                'generates_transaction' => true,
            ],
            [
                'plot_id'              => $plotA->id,
                'asset_id'             => $sprayer->id,
                'user_id'              => $userId,
                'activity_type'        => 'fertilizing',
                'description'          => 'Adubação de cobertura com ureia',
                'log_date'             => now()->subMonths(2)->format('Y-m-d'),
                'machine_hours'        => 2.5,
                'input_name'           => 'Ureia 45% N',
                'input_quantity'       => 120,
                'input_unit_price'     => 3.80,
                'generates_transaction' => true,
            ],
            // Talhão B — Milho
            [
                'plot_id'              => $plotB->id,
                'asset_id'             => $tractor2->id,
                'user_id'              => $userId,
                'activity_type'        => 'planting',
                'description'          => 'Plantio de milho — híbrido P3250 YHR',
                'log_date'             => now()->subMonths(3)->format('Y-m-d'),
                'machine_hours'        => 5.0,
                'input_name'           => 'Milho P3250 YHR',
                'input_quantity'       => 14,
                'input_unit_price'     => 420.00,
                'generates_transaction' => true,
            ],
            [
                'plot_id'              => $plotB->id,
                'asset_id'             => $sprayer->id,
                'user_id'              => $userId,
                'activity_type'        => 'spraying',
                'description'          => 'Aplicação de fungicida na V6',
                'log_date'             => now()->subMonths(2)->subWeeks(1)->format('Y-m-d'),
                'machine_hours'        => 2.0,
                'input_name'           => 'Priori Xtra',
                'input_quantity'       => 12,
                'input_unit_price'     => 95.00,
                'generates_transaction' => true,
            ],
            // Talhão C — Safra anterior (histórico)
            [
                'plot_id'              => $plotC->id,
                'asset_id'             => $harvester->id,
                'user_id'              => $userId,
                'activity_type'        => 'harvesting',
                'description'          => 'Colheita de soja — safra anterior',
                'log_date'             => now()->subMonths(5)->format('Y-m-d'),
                'machine_hours'        => 14.0,
                'input_name'           => null,
                'input_quantity'       => null,
                'input_unit_price'     => null,
                'generates_transaction' => true,
            ],
            [
                'plot_id'              => $plotC->id,
                'asset_id'             => $tractor->id,
                'user_id'              => $userId,
                'activity_type'        => 'fertilizing',
                'description'          => 'Calcário — preparo para próxima safra',
                'log_date'             => now()->subMonths(4)->subWeeks(2)->format('Y-m-d'),
                'machine_hours'        => 4.0,
                'input_name'           => 'Calcário Calcítico',
                'input_quantity'       => 3000,
                'input_unit_price'     => 0.28,
                'generates_transaction' => true,
            ],
            // Talhão E — Feijão
            [
                'plot_id'              => $plotE->id,
                'asset_id'             => $tractor->id,
                'user_id'              => $userId,
                'activity_type'        => 'planting',
                'description'          => 'Plantio de feijão carioca',
                'log_date'             => now()->subMonths(2)->format('Y-m-d'),
                'machine_hours'        => 3.5,
                'input_name'           => 'Feijão Carioca BRS Estilo',
                'input_quantity'       => 35,
                'input_unit_price'     => 18.00,
                'generates_transaction' => true,
            ],
            // Manutenção — sem talhão específico, usa o talhão A como referência
            [
                'plot_id'              => $plotA->id,
                'asset_id'             => $tractor->id,
                'user_id'              => $userId,
                'activity_type'        => 'maintenance',
                'description'          => 'Troca de óleo e filtros — 250h',
                'log_date'             => now()->subMonth()->format('Y-m-d'),
                'machine_hours'        => 1.0,
                'input_name'           => 'Óleo Sintetizado 15W40 + Filtros',
                'input_quantity'       => 1,
                'input_unit_price'     => 380.00,
                'generates_transaction' => true,
            ],
        ];

        foreach ($fieldLogs as $data) {
            // FieldLogService calcula o total_cost e atualiza horas do ativo
            $data['tenant_id'] = $tenantId;
            $this->fieldLogService->create($data);
        }

        // ─────────────────────────────────────────────────────────────────────
        // RECEITAS — Venda da safra anterior (não vinculadas a field_logs)
        // Demonstra o saldo positivo no dashboard financeiro
        // ─────────────────────────────────────────────────────────────────────
        FinancialTransaction::create([
            'tenant_id'        => $tenantId,
            'field_log_id'     => null,
            'plot_id'          => $plotC->id,
            'type'             => 'income',
            'category'         => 'Venda de Grãos',
            'amount'           => 187_440.00, // 55.2 ha × 58 sc/ha × R$58,50/sc
            'description'      => 'Venda de soja — safra ' . (date('Y') - 1) . '/' . date('Y') . ' — Talhão C',
            'transaction_date' => now()->subMonths(4)->format('Y-m-d'),
        ]);

        FinancialTransaction::create([
            'tenant_id'        => $tenantId,
            'field_log_id'     => null,
            'plot_id'          => $plotA->id,
            'type'             => 'income',
            'category'         => 'Subsídio / Incentivo',
            'amount'           => 12_000.00,
            'description'      => 'Crédito rural PRONAF — parcela investimento',
            'transaction_date' => now()->subMonths(6)->format('Y-m-d'),
        ]);
    }
}
