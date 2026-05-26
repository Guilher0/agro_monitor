<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CattleLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'animal_count' => ['required', 'integer', 'min:1'],
            'initial_avg_weight_kg' => ['required', 'numeric', 'min:1', 'max:9999.99'],
            'total_purchase_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'status' => ['required', 'in:active,sold'],
            'uf' => ['required', 'string', 'size:2'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome do lote',
            'animal_count' => 'quantidade de cabeças',
            'initial_avg_weight_kg' => 'peso médio inicial (kg)',
            'total_purchase_cost' => 'custo de compra',
            'status' => 'status',
            'uf' => 'UF (Estado)',
        ];
    }
}
