<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plot_id'          => ['nullable', 'integer', 'exists:plots,id'],
            'type'             => ['required', 'in:income,expense'],
            'category'         => ['required', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'description'      => ['required', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'plot_id'          => 'talhão',
            'type'             => 'tipo',
            'category'         => 'categoria',
            'amount'           => 'valor',
            'description'      => 'descrição',
            'transaction_date' => 'data',
        ];
    }
}
