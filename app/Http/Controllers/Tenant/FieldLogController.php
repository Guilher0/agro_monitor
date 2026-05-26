<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\FieldLogRequest;
use App\Models\Asset;
use App\Models\FieldLog;
use App\Models\Plot;
use App\Services\FieldLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class FieldLogController extends Controller
{
    public function __construct(private readonly FieldLogService $service) {}

    public function index(Request $request): Response
    {
        $fieldLogs = FieldLog::query()
            ->with(['plot:id,name', 'asset:id,name'])
            ->when($request->search, fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->when($request->plot_id, fn ($q, $v) => $q->where('plot_id', $v))
            ->when($request->activity_type, fn ($q, $v) => $q->where('activity_type', $v))
            ->when($request->date_from, fn ($q, $v) => $q->where('log_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->where('log_date', '<=', $v))
            ->orderByDesc('log_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (FieldLog $log) => [
                'id' => $log->id,
                'log_date' => $log->log_date->format('d/m/Y'),
                'activity_type' => $log->activity_type,
                'description' => $log->description,
                'plot' => $log->plot?->only('id', 'name'),
                'asset' => $log->asset?->only('id', 'name'),
                'machine_hours' => $log->machine_hours,
                'total_cost' => $log->total_cost,
                'generates_transaction' => $log->generates_transaction,
            ]);

        $plots = Plot::orderBy('name')->get(['id', 'name']);

        return Inertia::render('FieldLogs/Index', [
            'fieldLogs' => $fieldLogs,
            'plots' => $plots,
            'filters' => $request->only(['search', 'plot_id', 'activity_type', 'date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('FieldLogs/Form', [
            'fieldLog' => null,
            'plots' => Plot::orderBy('name')->get(['id', 'name', 'culture']),
            'assets' => Asset::where('status', 'active')->orderBy('name')->get(['id', 'name', 'type', 'hourly_rate']),
        ]);
    }

    public function store(FieldLogRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), [
            'tenant_id' => tenant('id'),
            'user_id' => Auth::id(),
        ]);

        $this->service->create($data);

        return to_route('field-logs.index')
            ->with('success', 'Registro adicionado ao caderno de campo.');
    }

    public function edit(FieldLog $fieldLog): Response
    {
        return Inertia::render('FieldLogs/Form', [
            'fieldLog' => $fieldLog->load(['plot:id,name', 'asset:id,name,hourly_rate']),
            'plots' => Plot::orderBy('name')->get(['id', 'name', 'culture']),
            'assets' => Asset::where('status', 'active')->orderBy('name')->get(['id', 'name', 'type', 'hourly_rate']),
        ]);
    }

    public function update(FieldLogRequest $request, FieldLog $fieldLog): RedirectResponse
    {
        $this->service->update($fieldLog, $request->validated());

        return to_route('field-logs.index')
            ->with('success', 'Registro atualizado.');
    }

    public function destroy(FieldLog $fieldLog): RedirectResponse
    {
        $fieldLog->delete();

        return to_route('field-logs.index')
            ->with('success', 'Registro removido.');
    }
}
