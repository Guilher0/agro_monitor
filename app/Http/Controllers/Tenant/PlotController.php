<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlotRequest;
use App\Models\Plot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlotController extends Controller
{
    public function index(Request $request): Response
    {
        $plots = Plot::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('culture', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->culture, fn ($q, $v) => $q->where('culture', $v))
            ->withCount('fieldLogs')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Plot $plot) => [
                'id' => $plot->id,
                'name' => $plot->name,
                'area_hectares' => $plot->area_hectares,
                'culture' => $plot->culture,
                'season' => $plot->season,
                'soil_type' => $plot->soil_type,
                'status' => $plot->status,
                'field_logs_count' => $plot->field_logs_count,
            ]);

        return Inertia::render('Plots/Index', [
            'plots' => $plots,
            'filters' => $request->only(['search', 'status', 'culture']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Plots/Form', [
            'plot' => null,
        ]);
    }

    public function store(PlotRequest $request): RedirectResponse
    {
        Plot::create([
            ...$request->validated(),
            'tenant_id' => tenant('id'),
        ]);

        return to_route('plots.index')
            ->with('success', 'Talhão cadastrado com sucesso.');
    }

    public function edit(Plot $plot): Response
    {
        return Inertia::render('Plots/Form', [
            'plot' => $plot,
        ]);
    }

    public function update(PlotRequest $request, Plot $plot): RedirectResponse
    {
        $plot->update($request->validated());

        return to_route('plots.index')
            ->with('success', 'Talhão atualizado com sucesso.');
    }

    public function destroy(Plot $plot): RedirectResponse
    {
        $plot->delete();

        return to_route('plots.index')
            ->with('success', 'Talhão removido.');
    }
}
