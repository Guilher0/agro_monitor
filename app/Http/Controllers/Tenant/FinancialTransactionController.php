<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\FinancialTransactionRequest;
use App\Models\FinancialTransaction;
use App\Models\Plot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FinancialTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $transactions = FinancialTransaction::query()
            ->with('plot:id,name')
            ->when($request->type,      fn ($q, $v) => $q->where('type', $v))
            ->when($request->plot_id,   fn ($q, $v) => $q->where('plot_id', $v))
            ->when($request->category,  fn ($q, $v) => $q->where('category', 'like', "%{$v}%"))
            ->when($request->date_from, fn ($q, $v) => $q->where('transaction_date', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->where('transaction_date', '<=', $v))
            ->orderByDesc('transaction_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FinancialTransaction $t) => [
                'id'               => $t->id,
                'transaction_date' => $t->transaction_date->format('d/m/Y'),
                'type'             => $t->type,
                'category'         => $t->category,
                'description'      => $t->description,
                'amount'           => $t->amount,
                'plot'             => $t->plot?->only('id', 'name'),
                'field_log_id'     => $t->field_log_id,
            ]);

        // Totais para os cards de resumo
        $summary = FinancialTransaction::query()
            ->when($request->date_from, fn ($q, $v) => $q->where('transaction_date', '>=', $v))
            ->when($request->date_to,   fn ($q, $v) => $q->where('transaction_date', '<=', $v))
            ->select(
                DB::raw("SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as total_income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense"),
            )
            ->first();

        $plots = Plot::orderBy('name')->get(['id', 'name']);

        return Inertia::render('FinancialTransactions/Index', [
            'transactions' => $transactions,
            'summary'      => [
                'total_income'  => (float) ($summary->total_income  ?? 0),
                'total_expense' => (float) ($summary->total_expense ?? 0),
                'balance'       => (float) (($summary->total_income ?? 0) - ($summary->total_expense ?? 0)),
            ],
            'plots'   => $plots,
            'filters' => $request->only(['type', 'plot_id', 'category', 'date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('FinancialTransactions/Form', [
            'transaction' => null,
            'plots'       => Plot::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(FinancialTransactionRequest $request): RedirectResponse
    {
        FinancialTransaction::create([
            ...$request->validated(),
            'tenant_id' => tenant('id'),
        ]);

        return to_route('financial-transactions.index')
            ->with('success', 'Lançamento financeiro registrado.');
    }

    public function edit(FinancialTransaction $financialTransaction): RedirectResponse|Response
    {
        // Transações geradas automaticamente pelo Observer não devem ser editadas aqui
        if ($financialTransaction->field_log_id) {
            return to_route('financial-transactions.index')
                ->with('info', 'Este lançamento é gerado automaticamente. Edite o registro do caderno de campo correspondente.');
        }

        return Inertia::render('FinancialTransactions/Form', [
            'transaction' => $financialTransaction->load('plot:id,name'),
            'plots'       => Plot::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(FinancialTransactionRequest $request, FinancialTransaction $financialTransaction): RedirectResponse
    {
        if ($financialTransaction->field_log_id) {
            return to_route('financial-transactions.index')
                ->with('info', 'Este lançamento é gerado automaticamente pelo caderno de campo.');
        }

        $financialTransaction->update($request->validated());

        return to_route('financial-transactions.index')
            ->with('success', 'Lançamento atualizado.');
    }

    public function destroy(FinancialTransaction $financialTransaction): RedirectResponse
    {
        if ($financialTransaction->field_log_id) {
            return to_route('financial-transactions.index')
                ->with('info', 'Para remover este lançamento, exclua o registro do caderno de campo.');
        }

        $financialTransaction->delete();

        return to_route('financial-transactions.index')
            ->with('success', 'Lançamento removido.');
    }
}
