<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * Equipamentos agrícolas reais usados no campo brasileiro.
     * Nomes realistas para demonstrações em feiras do agronegócio.
     */
    private array $tractorNames = [
        'John Deere 5090E',
        'New Holland T7.315',
        'Massey Ferguson 7718',
        'Case IH Puma 185',
        'Valtra BH 185i',
    ];

    private array $harvesterNames = [
        'John Deere S670',
        'Case IH Axial-Flow 7250',
        'New Holland CR9.90',
        'Claas Lexion 8700',
    ];

    private array $sprayerNames = [
        'Jacto Uniport 3030',
        'Stara Hércules 10000',
        'Amazone UX 11200',
        'Case IH Patriot 3340',
    ];

    public function definition(): array
    {
        $type = $this->faker->randomElement(['tractor', 'harvester', 'sprayer', 'implement', 'other']);

        $name = match ($type) {
            'tractor'   => $this->faker->randomElement($this->tractorNames),
            'harvester' => $this->faker->randomElement($this->harvesterNames),
            'sprayer'   => $this->faker->randomElement($this->sprayerNames),
            default     => $this->faker->randomElement(['Grade Aradora', 'Plantadeira', 'Subsolador', 'Distribuidor']),
        };

        // hourly_rate varia por tipo de equipamento — reflete valores reais do mercado
        $hourlyRate = match ($type) {
            'tractor'   => $this->faker->randomFloat(2, 120, 280),
            'harvester' => $this->faker->randomFloat(2, 350, 700),
            'sprayer'   => $this->faker->randomFloat(2, 200, 450),
            default     => $this->faker->randomFloat(2, 40, 120),
        };

        $totalHours            = $this->faker->randomFloat(1, 50, 3500);
        $hoursAtLastMaintenance = $this->faker->randomFloat(1, 0, $totalHours);

        return [
            'name'                      => $name,
            'type'                      => $type,
            'serial_number'             => strtoupper($this->faker->bothify('??###-????')),
            'purchase_date'             => $this->faker->dateTimeBetween('-8 years', '-1 year'),
            'hourly_rate'               => $hourlyRate,
            'total_hours'               => $totalHours,
            'hours_at_last_maintenance' => $hoursAtLastMaintenance,
            'last_maintenance_at'       => $this->faker->dateTimeBetween('-6 months', 'now'),
            'maintenance_alert_hours'   => $this->faker->randomElement([100, 150, 200, 250, 500]),
            'status'                    => $this->faker->randomElement(['active', 'active', 'active', 'maintenance']),
            'notes'                     => null,
        ];
    }

    /** Força o estado "precisa de manutenção" para testes do alerta amber. */
    public function needsMaintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_hours'               => 1500,
            'hours_at_last_maintenance' => 1000,
            'maintenance_alert_hours'   => 250,
            'status'                    => 'active',
        ]);
    }
}
