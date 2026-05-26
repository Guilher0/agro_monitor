<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\CattleLotRequest;
use App\Models\CattleLot;
use App\Models\CattleWeightLog;
use App\Services\LivestockPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller: CattleLotController
 *
 * Gerencia o ciclo de vida dos Lotes de Gado, controle de pesagens periódicas,
 * simulações econômicas com dados de mercado reais e integração financeira.
 */
class CattleLotController extends Controller
{
    /**
     * Lista todos os lotes de gado e resume os KPIs do rebanho de corte.
     */
    public function index(Request $request, LivestockPriceService $priceService): Response
    {
        // UF padrão baseada no request ou 'TO'
        $uf = strtoupper($request->input('uf', 'TO'));

        // Obtém cotação do dia para a UF ativa
        $quote = $priceService->getPriceForUf($uf);
        $pricePerArroba = (float) $quote['valor'];

        $lotsQuery = CattleLot::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('name');

        $lots = $lotsQuery->paginate(15)
            ->withQueryString()
            ->through(fn (CattleLot $lot) => [
                'id' => $lot->id,
                'name' => $lot->name,
                'animal_count' => $lot->animal_count,
                'initial_avg_weight_kg' => (float) $lot->initial_avg_weight_kg,
                'current_avg_weight_kg' => (float) $lot->current_avg_weight_kg,
                'total_purchase_cost' => (float) $lot->total_purchase_cost,
                'status' => $lot->status,
                'uf' => $lot->uf,
                'sold_amount' => $lot->sold_amount ? (float) $lot->sold_amount : null,
                'sold_at' => $lot->sold_at ? $lot->sold_at->format('Y-m-d') : null,
            ]);

        // Cálculo de KPIs globais dos lotes ATIVOS
        $kpiData = CattleLot::where('status', 'active')
            ->select(
                DB::raw('SUM(animal_count) as total_animals'),
                DB::raw('SUM(current_avg_weight_kg * animal_count) as total_weight_kg'),
                DB::raw('SUM(total_purchase_cost) as total_purchase_cost')
            )->first();

        $totalAnimals = (int) ($kpiData->total_animals ?? 0);
        $totalWeightKg = (float) ($kpiData->total_weight_kg ?? 0);
        $avgWeightKg = $totalAnimals > 0 ? $totalWeightKg / $totalAnimals : 0;
        $totalPurchaseCost = (float) ($kpiData->total_purchase_cost ?? 0);

        // Arrobas Estimadas Totais dos ativos: Peso Vivo * Rendimento 50% (dividido por 30)
        $totalArrobas = $totalWeightKg / 30;
        $estimatedMarketValue = $totalArrobas * $pricePerArroba;

        return Inertia::render('CattleLots/Index', [
            'lots' => $lots,
            'filters' => $request->only(['search', 'status', 'uf']),
            'currentQuote' => $quote,
            'kpis' => [
                'total_animals' => $totalAnimals,
                'avg_weight_kg' => round($avgWeightKg, 2),
                'total_purchase_cost' => round($totalPurchaseCost, 2),
                'estimated_market_value' => round($estimatedMarketValue, 2),
                'estimated_roi' => round($estimatedMarketValue - $totalPurchaseCost, 2),
            ],
        ]);
    }

    /**
     * Roda a tela de criação do lote.
     */
    public function create(): Response
    {
        return Inertia::render('CattleLots/Form', [
            'lot' => null,
        ]);
    }

    /**
     * Salva o novo lote de gado.
     */
    public function store(CattleLotRequest $request): RedirectResponse
    {
        CattleLot::create([
            ...$request->validated(),
            'current_avg_weight_kg' => $request->initial_avg_weight_kg,
            'tenant_id' => tenant('id'),
        ]);

        return to_route('cattle-lots.index')
            ->with('success', 'Lote de gado cadastrado com sucesso.');
    }

    /**
     * Exibe a página detalhada do lote, com simulador de viabilidade, gráficos e logs de peso.
     */
    public function show(CattleLot $cattleLot, LivestockPriceService $priceService): Response
    {
        // Carrega logs de pesagem ordenados e transações vinculadas
        $weightLogs = $cattleLot->weightLogs()
            ->orderBy('weight_date')
            ->orderBy('id')
            ->get(['id', 'weight_date', 'avg_weight_kg', 'notes'])
            ->map(fn ($log) => [
                'id' => $log->id,
                'weight_date' => $log->weight_date->format('Y-m-d'),
                'avg_weight_kg' => (float) $log->avg_weight_kg,
                'notes' => $log->notes,
            ]);

        // Transações financeiras vinculadas (despesas de manejo)
        $financialTransactions = $cattleLot->financialTransactions()
            ->orderByDesc('transaction_date')
            ->get(['id', 'type', 'category', 'amount', 'description', 'transaction_date'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'category' => $t->category,
                'amount' => (float) $t->amount,
                'description' => $t->description,
                'transaction_date' => $t->transaction_date->format('Y-m-d'),
            ]);

        // Custo de manejo acumulado (soma das despesas vinculadas)
        $managementCost = (float) $cattleLot->financialTransactions()
            ->where('type', 'expense')
            ->sum('amount');

        // Busca a cotação regional para a UF configurada no lote
        $quote = $priceService->getPriceForUf($cattleLot->uf);

        return Inertia::render('CattleLots/Show', [
            'lot' => [
                'id' => $cattleLot->id,
                'name' => $cattleLot->name,
                'animal_count' => (int) $cattleLot->animal_count,
                'initial_avg_weight_kg' => (float) $cattleLot->initial_avg_weight_kg,
                'current_avg_weight_kg' => (float) $cattleLot->current_avg_weight_kg,
                'total_purchase_cost' => (float) $cattleLot->total_purchase_cost,
                'status' => $cattleLot->status,
                'uf' => $cattleLot->uf,
                'sold_amount' => $cattleLot->sold_amount ? (float) $cattleLot->sold_amount : null,
                'sold_at' => $cattleLot->sold_at ? $cattleLot->sold_at->format('Y-m-d') : null,
            ],
            'weightLogs' => $weightLogs,
            'financialTransactions' => $financialTransactions,
            'managementCost' => $managementCost,
            'currentQuote' => $quote,
        ]);
    }

    /**
     * Abre formulário de edição do lote.
     */
    public function edit(CattleLot $cattleLot): Response
    {
        return Inertia::render('CattleLots/Form', [
            'lot' => [
                'id' => $cattleLot->id,
                'name' => $cattleLot->name,
                'animal_count' => $cattleLot->animal_count,
                'initial_avg_weight_kg' => (float) $cattleLot->initial_avg_weight_kg,
                'total_purchase_cost' => (float) $cattleLot->total_purchase_cost,
                'status' => $cattleLot->status,
                'uf' => $cattleLot->uf,
            ],
        ]);
    }

    /**
     * Atualiza o lote de gado.
     */
    public function update(CattleLotRequest $request, CattleLot $cattleLot): RedirectResponse
    {
        $cattleLot->update($request->validated());

        // Se o lote for recém atualizado, garante sincronia do peso
        $cattleLot->updateCurrentWeight();

        return to_route('cattle-lots.index')
            ->with('success', 'Lote de gado atualizado com sucesso.');
    }

    /**
     * Exclui (soft-delete) o lote de gado.
     */
    public function destroy(CattleLot $cattleLot): RedirectResponse
    {
        $cattleLot->delete();

        return to_route('cattle-lots.index')
            ->with('success', 'Lote de gado removido com sucesso.');
    }

    /**
     * Adiciona um registro de pesagem ao lote.
     */
    public function storeWeightLog(Request $request, CattleLot $cattleLot): RedirectResponse
    {
        $validated = $request->validate([
            'weight_date' => ['required', 'date'],
            'avg_weight_kg' => ['required', 'numeric', 'min:1', 'max:9999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cattleLot->weightLogs()->create([
            ...$validated,
            'tenant_id' => tenant('id'),
        ]);

        return back()->with('success', 'Pesagem registrada com sucesso.');
    }

    /**
     * Remove um registro de pesagem específico.
     */
    public function destroyWeightLog(CattleLot $cattleLot, CattleWeightLog $weightLog): RedirectResponse
    {
        // Garante que o log pertence de fato a este lote
        if ($weightLog->cattle_lot_id === $cattleLot->id) {
            $weightLog->delete();
        }

        return back()->with('success', 'Registro de pesagem removido.');
    }

    /**
     * Registra o fechamento e a venda real do lote de gado.
     */
    public function sell(Request $request, CattleLot $cattleLot): RedirectResponse
    {
        $validated = $request->validate([
            'sold_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'sold_at' => ['required', 'date'],
        ]);

        // Ao atualizar esses campos, o CattleLotObserver lidará com a criação da receita financeira
        $cattleLot->update([
            'status' => 'sold',
            'sold_amount' => $validated['sold_amount'],
            'sold_at' => $validated['sold_at'],
        ]);

        return back()->with('success', 'Lote vendido e receita financeira lançada com sucesso.');
    }
}
