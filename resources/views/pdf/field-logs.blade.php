<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caderno de Campo — {{ $tenant_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #fff;
        }

        /* ─── Cabeçalho ─────────────────────────────── */
        .header {
            background: #14532d;
            color: #fff;
            padding: 18px 24px;
            border-bottom: 3px solid #15803d;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header .subtitle {
            font-size: 10px;
            color: #86efac;
            margin-top: 3px;
        }
        .header .meta {
            float: right;
            text-align: right;
            font-size: 9px;
            color: #bbf7d0;
        }
        .clearfix::after { content: ''; display: block; clear: both; }

        /* ─── Filtros aplicados ──────────────────────── */
        .filters-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin: 16px 24px;
            font-size: 9px;
            color: #475569;
        }
        .filters-bar strong { color: #1e293b; }

        /* ─── Resumo financeiro ──────────────────────── */
        .summary {
            margin: 0 24px 16px;
            display: table;
            width: calc(100% - 48px);
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .summary-card {
            display: table-cell;
            width: 25%;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
        }
        .summary-card .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }
        .summary-card .value {
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }
        .summary-card.income .value  { color: #15803d; }
        .summary-card.expense .value { color: #dc2626; }
        .summary-card.balance .value { color: #1e293b; }
        .summary-card.count .value   { color: #14532d; }

        /* ─── Tabela principal ───────────────────────── */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #14532d;
            margin: 0 24px 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #15803d;
        }

        table.main-table {
            width: calc(100% - 48px);
            margin: 0 24px;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.main-table thead tr {
            background: #14532d;
            color: #fff;
        }
        table.main-table thead th {
            padding: 7px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table.main-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        table.main-table tbody tr:hover {
            background: #f0fdf4;
        }
        table.main-table tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 99px;
            font-size: 8px;
            font-weight: 600;
        }
        .badge-planting    { background: #dcfce7; color: #166534; }
        .badge-spraying    { background: #dbeafe; color: #1e40af; }
        .badge-harvesting  { background: #fef9c3; color: #854d0e; }
        .badge-fertilizing { background: #ffedd5; color: #9a3412; }
        .badge-maintenance { background: #fee2e2; color: #991b1b; }
        .badge-irrigation  { background: #cffafe; color: #155e75; }
        .badge-soil_prep   { background: #f1f5f9; color: #475569; }
        .badge-other       { background: #f1f5f9; color: #475569; }

        .text-right { text-align: right; }
        .text-mono  { font-family: 'DejaVu Sans Mono', monospace; }

        /* ─── Rodapé ─────────────────────────────────── */
        .footer {
            margin-top: 24px;
            padding: 12px 24px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Cabeçalho -->
    <div class="header clearfix">
        <div class="meta">
            Emitido em: {{ now()->format('d/m/Y H:i') }}<br>
            {{ $tenant_name }}
        </div>
        <h1>AgroMonitor — Caderno de Campo</h1>
        <div class="subtitle">Relatório de Registros de Atividades</div>
    </div>

    <!-- Filtros aplicados -->
    <div class="filters-bar">
        @if($filter_plot)
            <strong>Talhão:</strong> {{ $filter_plot }} &nbsp;|&nbsp;
        @endif
        @if($filter_from || $filter_to)
            <strong>Período:</strong>
            {{ $filter_from ? \Carbon\Carbon::parse($filter_from)->format('d/m/Y') : '–' }}
            até
            {{ $filter_to   ? \Carbon\Carbon::parse($filter_to)->format('d/m/Y')   : '–' }}
            &nbsp;|&nbsp;
        @endif
        <strong>Total de registros:</strong> {{ count($logs) }}
    </div>

    <!-- Cards de resumo -->
    <table class="summary">
        <tr>
            <td class="summary-card count">
                <div class="label">Registros</div>
                <div class="value">{{ count($logs) }}</div>
            </td>
            <td class="summary-card income">
                <div class="label">Receitas no período</div>
                <div class="value">R$ {{ number_format($total_income, 2, ',', '.') }}</div>
            </td>
            <td class="summary-card expense">
                <div class="label">Custos no período</div>
                <div class="value">R$ {{ number_format($total_expense, 2, ',', '.') }}</div>
            </td>
            <td class="summary-card balance">
                <div class="label">Saldo no período</div>
                <div class="value" style="color: {{ ($total_income - $total_expense) >= 0 ? '#15803d' : '#dc2626' }}">
                    R$ {{ number_format($total_income - $total_expense, 2, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Título da seção -->
    <div class="section-title">Detalhamento dos Registros</div>

    <!-- Tabela de logs -->
    <table class="main-table">
        <thead>
            <tr>
                <th style="width:8%">Data</th>
                <th style="width:14%">Atividade</th>
                <th style="width:13%">Talhão</th>
                <th style="width:13%">Ativo</th>
                <th style="width:12%">Operador</th>
                <th style="width:16%">Descrição</th>
                <th style="width:9%" class="text-right">H. Máquina</th>
                <th style="width:15%" class="text-right">Custo Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ \Carbon\Carbon::parse($log->log_date)->format('d/m/Y') }}</td>
                <td>
                    <span class="badge badge-{{ $log->activity_type }}">
                        {{ $activityLabels[$log->activity_type] ?? $log->activity_type }}
                    </span>
                </td>
                <td>{{ $log->plot?->name ?? '—' }}</td>
                <td>{{ $log->asset?->name ?? '—' }}</td>
                <td>{{ $log->user?->name ?? '—' }}</td>
                <td>{{ $log->description ?? '—' }}</td>
                <td class="text-right text-mono">
                    {{ $log->machine_hours ? number_format($log->machine_hours, 1, ',', '.') . ' h' : '—' }}
                </td>
                <td class="text-right text-mono">
                    R$ {{ number_format($log->total_cost ?? 0, 2, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center; padding: 20px; color: #94a3b8;">
                    Nenhum registro encontrado para os filtros selecionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Rodapé -->
    <div class="footer">
        AgroMonitor &mdash; Sistema de Gestão de Ativos e Caderno de Campo Digital &nbsp;|&nbsp;
        Gerado automaticamente em {{ now()->format('d/m/Y \à\s H:i') }}
    </div>

</body>
</html>
