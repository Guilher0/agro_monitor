<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRequest;
use App\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $assets = Asset::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('serial_number', 'like', "%{$s}%"))
            ->when($request->type,   fn ($q, $v) => $q->where('type', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Asset $asset) => [
                'id'               => $asset->id,
                'name'             => $asset->name,
                'type'             => $asset->type,
                'serial_number'    => $asset->serial_number,
                'status'           => $asset->status,
                'total_hours'      => $asset->total_hours,
                'hourly_rate'      => $asset->hourly_rate,
                'needs_maintenance' => $asset->needs_maintenance,
                'last_maintenance_at' => $asset->last_maintenance_at?->format('d/m/Y'),
            ]);

        return Inertia::render('Assets/Index', [
            'assets'  => $assets,
            'filters' => $request->only(['search', 'type', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Assets/Form', [
            'asset' => null,
        ]);
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        Asset::create([
            ...$request->validated(),
            'tenant_id' => tenant('id'),
        ]);

        return to_route('assets.index')
            ->with('success', 'Ativo cadastrado com sucesso.');
    }

    public function edit(Asset $asset): Response
    {
        return Inertia::render('Assets/Form', [
            'asset' => $asset,
        ]);
    }

    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->update($request->validated());

        return to_route('assets.index')
            ->with('success', 'Ativo atualizado com sucesso.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return to_route('assets.index')
            ->with('success', 'Ativo removido.');
    }
}
