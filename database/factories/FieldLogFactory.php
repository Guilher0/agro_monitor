<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\FieldLog;
use App\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldLog>
 *
 * ATENÇÃO: Esta factory gera registros para o banco de tenant.
 * Certifique-se de estar dentro de um contexto de tenancy ao usá-la.
 * O FieldLogObserver criará FinancialTransactions automaticamente para
 * os registros com generates_transaction = true.
 */
class FieldLogFactory extends Factory
{
    public function definition(): array
    {
        $activityType = $this->faker->randomElement([
            'planting', 'spraying', 'harvesting', 'fertilizing', 'maintenance', 'irrigation',
        ]);

        $hasMachine   = $this->faker->boolean(75); // 75% das atividades usam máquina
        $hasInput     = in_array($activityType, ['spraying', 'fertilizing', 'planting']);
        $machineHours = $hasMachine ? $this->faker->randomFloat(1, 0.5, 12) : null;

        $inputName      = null;
        $inputQuantity  = null;
        $inputUnitPrice = null;

        if ($hasInput) {
            [$inputName, $inputUnitPrice] = match ($activityType) {
                'spraying'    => [$this->faker->randomElement(['Roundup', 'Glifosato', 'Primóleo', 'Karate']), $this->faker->randomFloat(2, 18, 120)],
                'fertilizing' => [$this->faker->randomElement(['NPK 20-05-20', 'Ureia', 'MAP', 'KCl']), $this->faker->randomFloat(2, 1.5, 8)],
                'planting'    => [$this->faker->randomElement(['Soja Brasmax Bônus', 'Milho P3250', 'Feijão Carioca']), $this->faker->randomFloat(2, 60, 350)],
                default       => ['Insumo', $this->faker->randomFloat(2, 10, 100)],
            };
            $inputQuantity = $this->faker->randomFloat(3, 1, 200);
        }

        return [
            'plot_id'              => Plot::factory(),
            'asset_id'             => $hasMachine ? Asset::factory() : null,
            'user_id'              => 1, // Substituído no seeder pelo ID real
            'activity_type'        => $activityType,
            'description'          => $this->buildDescription($activityType),
            'log_date'             => $this->faker->dateTimeBetween('-6 months', 'now'),
            'machine_hours'        => $machineHours,
            'input_name'           => $inputName,
            'input_quantity'       => $inputQuantity,
            'input_unit_price'     => $inputUnitPrice,
            'total_cost'           => 0, // Recalculado pelo FieldLogService
            'generates_transaction' => $this->faker->boolean(80), // 80% geram despesa
        ];
    }

    private function buildDescription(string $activityType): string
    {
        return match ($activityType) {
            'planting'    => $this->faker->randomElement(['Plantio de soja', 'Plantio de milho', 'Semeadura']),
            'spraying'    => $this->faker->randomElement(['Aplicação de herbicida', 'Aplicação de fungicida', 'Pulverização foliar']),
            'harvesting'  => $this->faker->randomElement(['Colheita de soja', 'Colheita de milho', 'Colheita de feijão']),
            'fertilizing' => $this->faker->randomElement(['Adubação de cobertura', 'Adubação de base', 'Aplicação de calcário']),
            'maintenance' => $this->faker->randomElement(['Troca de óleo', 'Revisão preventiva', 'Substituição de filtros']),
            'irrigation'  => $this->faker->randomElement(['Irrigação por pivô', 'Irrigação por gotejamento']),
            default       => 'Atividade agrícola',
        };
    }
}
