<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'area_hectares' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'culture' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:20'],
            'location_coordinates' => ['nullable', 'array'],
            'location_coordinates.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location_coordinates.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'soil_type' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:active,fallow,harvested'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'area_hectares' => 'área (ha)',
            'culture' => 'cultura',
            'season' => 'safra',
            'soil_type' => 'tipo de solo',
            'status' => 'status',
            'notes' => 'observações',
        ];
    }
}
