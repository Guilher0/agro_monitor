<?php

namespace Database\Factories;

use App\Models\Plot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plot>
 */
class PlotFactory extends Factory
{
    /** Culturas principais da agricultura brasileira */
    private array $cultures = [
        'Soja',
        'Milho',
        'Cana-de-açúcar',
        'Algodão',
        'Feijão',
        'Trigo',
        'Girassol',
        'Sorgo',
    ];

    private array $soilTypes = [
        'Latossolo Vermelho',
        'Latossolo Vermelho-Amarelo',
        'Argissolo Vermelho',
        'Nitossolo Vermelho',
        'Neossolo Quartzarênico',
    ];

    public function definition(): array
    {
        $culture = $this->faker->randomElement($this->cultures);
        $year    = date('Y');

        return [
            'name'                 => 'Talhão ' . $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'Norte', 'Sul', 'Leste', 'Oeste']),
            'area_hectares'        => $this->faker->randomFloat(2, 5, 250),
            'culture'              => $culture,
            'season'               => "{$year}/{$year}",
            'location_coordinates' => null,
            'soil_type'            => $this->faker->randomElement($this->soilTypes),
            'status'               => $this->faker->randomElement(['active', 'active', 'fallow', 'harvested']),
            'notes'                => null,
        ];
    }

    /** Talhão ativo com soja — estado mais comum no demo */
    public function soybean(): static
    {
        $year = date('Y');
        return $this->state(fn () => [
            'culture' => 'Soja',
            'season'  => "{$year}/{$year}",
            'status'  => 'active',
        ]);
    }

    /** Talhão colhido — para popular histórico financeiro */
    public function harvested(): static
    {
        $prevYear = date('Y') - 1;
        $year     = date('Y');
        return $this->state(fn () => [
            'status' => 'harvested',
            'season' => "{$prevYear}/{$year}",
        ]);
    }
}
