<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\FieldLog;
use App\Models\FinancialTransaction;
use App\Models\Plot;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        // ─────────────────────────────────────────────────────────────────────
        // KPI Cards — contagens rápidas
        // ─────────────────────────────────────────────────────────────────────
        $totalAssets  = Asset::count();
        $activePlots  = Plot::where('status', 'active')->count();
        $logsThisMonth = FieldLog::whereMonth('log_date', now()->month)
            ->whereYear('log_date', now()->year)
            ->count();

        // Ativos que precisam de manutenção — calculado via SQL para eficiência
        $maintenanceAlertCount = Asset::where('status', 'active')
            ->whereRaw('(total_hours - hours_at_last_maintenance) >= maintenance_alert_hours')
            ->count();

        // ─────────────────────────────────────────────────────────────────────
        // Resumo financeiro geral (todas as datas)
        // ─────────────────────────────────────────────────────────────────────
        $financial = FinancialTransaction::select(
            DB::raw("SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as total_income"),
            DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense"),
        )->first();

        // ─────────────────────────────────────────────────────────────────────
        // Lucro por Talhão — dados para o gráfico de barras ApexCharts
        // Fórmula do agent.md: receita - custo = lucro por talhão
        // ─────────────────────────────────────────────────────────────────────
        $profitByPlot = FinancialTransaction::query()
            ->join('plots', 'financial_transactions.plot_id', '=', 'plots.id')
            ->select(
                'plots.name',
                DB::raw("SUM(CASE WHEN financial_transactions.type = 'income'  THEN financial_transactions.amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN financial_transactions.type = 'expense' THEN financial_transactions.amount ELSE 0 END) as expense"),
            )
            ->whereNull('financial_transactions.deleted_at')
            ->groupBy('plots.id', 'plots.name')
            ->orderBy('plots.name')
            ->get()
            ->map(fn ($row) => [
                'name'    => $row->name,
                'receita' => round((float) $row->income,  2),
                'custo'   => round((float) $row->expense, 2),
                'lucro'   => round((float) $row->income - (float) $row->expense, 2),
            ]);

        // ─────────────────────────────────────────────────────────────────────
        // Horas de máquina por tipo — dados para o gráfico de rosca
        // ─────────────────────────────────────────────────────────────────────
        $typeLabels = [
            'tractor'   => 'Trator',
            'harvester' => 'Colheitadeira',
            'sprayer'   => 'Pulverizador',
            'implement' => 'Implemento',
            'other'     => 'Outro',
        ];

        $hoursByType = Asset::select('type', DB::raw('SUM(total_hours) as hours'))
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'label' => $typeLabels[$row->type] ?? $row->type,
                'hours' => round((float) $row->hours, 1),
            ]);

        // ─────────────────────────────────────────────────────────────────────
        // Tendência financeira — últimos 6 meses (gráfico de área)
        // ─────────────────────────────────────────────────────────────────────
        $monthlyTrend = FinancialTransaction::query()
            ->where('transaction_date', '>=', now()->subMonths(5)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as month"),
                DB::raw("DATE_FORMAT(transaction_date, '%b/%Y') as label"),
                DB::raw("SUM(CASE WHEN type = 'income'  THEN amount ELSE 0 END) as income"),
                DB::raw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense"),
            )
            ->whereNull('deleted_at')
            ->groupBy('month', 'label')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'label'   => $row->label,
                'receita' => round((float) $row->income,  2),
                'despesa' => round((float) $row->expense, 2),
            ]);

        // ─────────────────────────────────────────────────────────────────────
        // Alertas de manutenção — lista dos ativos que precisam de atenção
        // ─────────────────────────────────────────────────────────────────────
        $maintenanceAlerts = Asset::where('status', 'active')
            ->whereRaw('(total_hours - hours_at_last_maintenance) >= maintenance_alert_hours')
            ->orderByRaw('(total_hours - hours_at_last_maintenance) DESC')
            ->limit(5)
            ->get(['id', 'name', 'type', 'total_hours', 'hours_at_last_maintenance', 'maintenance_alert_hours'])
            ->map(fn ($a) => [
                'id'                  => $a->id,
                'name'                => $a->name,
                'type'                => $typeLabels[$a->type] ?? $a->type,
                'hours_overdue'       => round((float) $a->total_hours - (float) $a->hours_at_last_maintenance, 1),
                'maintenance_alert_hours' => $a->maintenance_alert_hours,
            ]);

        // ─────────────────────────────────────────────────────────────────────
        // Últimos registros do caderno — feed de atividades recentes
        // ─────────────────────────────────────────────────────────────────────
        $recentLogs = FieldLog::with(['plot:id,name', 'asset:id,name'])
            ->orderByDesc('log_date')
            ->limit(6)
            ->get()
            ->map(fn ($log) => [
                'id'            => $log->id,
                'log_date'      => $log->log_date->format('d/m/Y'),
                'activity_type' => $log->activity_type,
                'description'   => $log->description,
                'plot_name'     => $log->plot?->name,
                'asset_name'    => $log->asset?->name,
                'total_cost'    => (float) $log->total_cost,
            ]);

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total_assets'           => $totalAssets,
                'active_plots'           => $activePlots,
                'logs_this_month'        => $logsThisMonth,
                'maintenance_alert_count' => $maintenanceAlertCount,
                'total_income'           => round((float) ($financial->total_income  ?? 0), 2),
                'total_expense'          => round((float) ($financial->total_expense ?? 0), 2),
                'balance'                => round((float) (($financial->total_income ?? 0) - ($financial->total_expense ?? 0)), 2),
            ],
            'profitByPlot'      => $profitByPlot,
            'hoursByType'       => $hoursByType,
            'monthlyTrend'      => $monthlyTrend,
            'maintenanceAlerts' => $maintenanceAlerts,
            'recentLogs'        => $recentLogs,
        ]);
    }
}
