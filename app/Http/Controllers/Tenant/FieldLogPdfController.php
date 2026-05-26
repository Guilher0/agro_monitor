<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\FieldLog;
use App\Models\FinancialTransaction;
use App\Models\Plot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FieldLogPdfController extends Controller
{
    /**
     * Gera e faz download do PDF do Caderno de Campo com filtros opcionais.
     *
     * Query params aceitos:
     *   - plot_id: filtrar por talhão
     *   - date_from: data de início (Y-m-d)
     *   - date_to: data de fim (Y-m-d)
     *
     * Rota: GET /field-logs/export/pdf
     */
    public function __invoke(Request $request): Response
    {
        $request->validate([
            'plot_id' => ['nullable', 'integer', 'exists:plots,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = FieldLog::with(['plot:id,name', 'asset:id,name', 'user:id,name'])
            ->orderBy('log_date', 'asc');

        // Filtro por talhão
        $filterPlot = null;
        if ($request->filled('plot_id')) {
            $query->where('plot_id', $request->plot_id);
            $filterPlot = Plot::find($request->plot_id)?->name;
        }

        // Filtro por período
        if ($request->filled('date_from')) {
            $query->whereDate('log_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('log_date', '<=', $request->date_to);
        }

        $logs = $query->get();

        // Resumo financeiro dos registros filtrados
        $totalIncome = $logs->sum(fn ($log) => $log->generates_transaction ? 0 : 0); // reservado
        $totalExpense = $logs->sum(fn ($log) => (float) ($log->total_cost ?? 0));

        // Receitas do período a partir das transações vinculadas
        $transactionQuery = FinancialTransaction::query();
        if ($request->filled('plot_id')) {
            $transactionQuery->where('plot_id', $request->plot_id);
        }
        if ($request->filled('date_from')) {
            $transactionQuery->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $transactionQuery->whereDate('transaction_date', '<=', $request->date_to);
        }
        $totalIncome = $transactionQuery->where('type', 'income')->sum('amount');
        $totalExpense = $transactionQuery->where('type', 'expense')->sum('amount');

        $activityLabels = [
            'planting' => 'Plantio',
            'spraying' => 'Pulverização',
            'harvesting' => 'Colheita',
            'fertilizing' => 'Adubação',
            'maintenance' => 'Manutenção',
            'irrigation' => 'Irrigação',
            'soil_prep' => 'Preparo do Solo',
            'other' => 'Outro',
        ];

        $pdf = Pdf::loadView('pdf.field-logs', [
            'logs' => $logs,
            'filter_plot' => $filterPlot,
            'filter_from' => $request->date_from,
            'filter_to' => $request->date_to,
            'total_income' => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'activityLabels' => $activityLabels,
            'tenant_name' => tenant('id') ?? 'Fazenda',
        ])->setPaper('a4', 'landscape');

        $filename = 'caderno-campo-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
