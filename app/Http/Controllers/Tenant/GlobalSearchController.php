<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\FieldLog;
use App\Models\Plot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Busca global em ativos, talhões e registros de campo.
     *
     * Retorna resultados agrupados por tipo, prontos para o Spotlight Vue.
     *
     * Rota: GET /search?q=termo
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $term = trim($request->q);
        $like = '%' . $term . '%';

        // ─── Ativos ──────────────────────────────────────────────────────────
        $assets = Asset::where('name', 'like', $like)
            ->orWhere('serial_number', 'like', $like)
            ->orWhere('notes', 'like', $like)
            ->limit(5)
            ->get(['id', 'name', 'type', 'status'])
            ->map(fn ($a) => [
                'type'     => 'asset',
                'id'       => $a->id,
                'title'    => $a->name,
                'subtitle' => ucfirst($a->type) . ' · ' . ($a->status === 'active' ? 'Ativo' : 'Inativo'),
                'url'      => route('assets.edit', $a->id),
            ]);

        // ─── Talhões ─────────────────────────────────────────────────────────
        $plots = Plot::where('name', 'like', $like)
            ->orWhere('current_culture', 'like', $like)
            ->orWhere('notes', 'like', $like)
            ->limit(5)
            ->get(['id', 'name', 'current_culture', 'status'])
            ->map(fn ($p) => [
                'type'     => 'plot',
                'id'       => $p->id,
                'title'    => $p->name,
                'subtitle' => ($p->current_culture ?? 'Sem cultura') . ' · ' . ucfirst($p->status),
                'url'      => route('plots.edit', $p->id),
            ]);

        // ─── Caderno de Campo ─────────────────────────────────────────────────
        $fieldLogs = FieldLog::with('plot:id,name')
            ->where('description', 'like', $like)
            ->orWhere('notes', 'like', $like)
            ->orWhere('input_name', 'like', $like)
            ->limit(5)
            ->get()
            ->map(fn ($log) => [
                'type'     => 'field_log',
                'id'       => $log->id,
                'title'    => $log->description ?? ('Registro ' . $log->log_date->format('d/m/Y')),
                'subtitle' => ($log->plot?->name ?? 'Sem talhão') . ' · ' . $log->log_date->format('d/m/Y'),
                'url'      => route('field-logs.edit', $log->id),
            ]);

        return response()->json([
            'results' => [
                ['group' => 'Ativos',           'items' => $assets],
                ['group' => 'Talhões',          'items' => $plots],
                ['group' => 'Caderno de Campo', 'items' => $fieldLogs],
            ],
        ]);
    }
}
