<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FieldLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plot_id' => ['required', 'integer', 'exists:plots,id'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'activity_type' => ['required', 'in:planting,spraying,harvesting,fertilizing,maintenance,irrigation,other'],
            'description' => ['required', 'string', 'max:1000'],
            'log_date' => ['required', 'date', 'before_or_equal:today'],
            'machine_hours' => ['nullable', 'numeric', 'min:0', 'max:999.9'],
            'input_name' => ['nullable', 'string', 'max:255'],
            'input_quantity' => ['nullable', 'numeric', 'min:0'],
            'input_unit_price' => ['nullable', 'numeric', 'min:0'],
            'generates_transaction' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'plot_id' => 'talhão',
            'asset_id' => 'ativo / máquina',
            'activity_type' => 'tipo de atividade',
            'description' => 'descrição',
            'log_date' => 'data',
            'machine_hours' => 'horas de máquina',
            'input_name' => 'insumo',
            'input_quantity' => 'quantidade de insumo',
            'input_unit_price' => 'preço unitário do insumo',
            'generates_transaction' => 'gerar transação financeira',
        ];
    }
}
