<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:255'],
            'type'                      => ['required', 'in:tractor,harvester,sprayer,implement,other'],
            'serial_number'             => ['nullable', 'string', 'max:100'],
            'purchase_date'             => ['nullable', 'date'],
            'hourly_rate'               => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'total_hours'               => ['nullable', 'numeric', 'min:0'],
            'hours_at_last_maintenance' => ['nullable', 'numeric', 'min:0'],
            'last_maintenance_at'       => ['nullable', 'date'],
            'maintenance_alert_hours'   => ['required', 'integer', 'min:1', 'max:9999'],
            'status'                    => ['required', 'in:active,maintenance,inactive'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                      => 'nome',
            'type'                      => 'tipo',
            'serial_number'             => 'número de série',
            'purchase_date'             => 'data de compra',
            'hourly_rate'               => 'valor por hora',
            'total_hours'               => 'total de horas',
            'hours_at_last_maintenance' => 'horas na última manutenção',
            'last_maintenance_at'       => 'data da última manutenção',
            'maintenance_alert_hours'   => 'alerta de manutenção (horas)',
            'status'                    => 'status',
            'notes'                     => 'observações',
        ];
    }
}
