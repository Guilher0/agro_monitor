<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import { ref, computed } from 'vue';

const props = defineProps({
    lot: Object,
    weightLogs: Array,
    financialTransactions: Array,
    managementCost: Number,
    currentQuote: Object,
});

// Aba ativa
const activeTab = ref('simulator');

// ─── LÓGICA DO SIMULADOR INTEGRADO ───
const yieldRate = ref(50); // % de rendimento de carcaça, ajustável pelo slider

// Total de @ do lote = (peso atual * heads / 30) ajustado de acordo com a taxa do slider
// No Brasil, a arroba física de carcaça é 15kg. Então, Arrobas = (peso vivo * rendimento%) / 15.
const estimatedLotArrobas = computed(() => {
    const totalLiveWeight = props.lot.current_avg_weight_kg * props.lot.animal_count;
    const carcassWeight = totalLiveWeight * (yieldRate.value / 100);
    return carcassWeight / 15;
});

const estimatedMarketValue = computed(() => {
    return estimatedLotArrobas.value * props.currentQuote.valor;
});

const totalCosts = computed(() => {
    return props.lot.total_purchase_cost + props.managementCost;
});

const estimatedNetProfit = computed(() => {
    if (props.lot.status === 'sold') {
        return props.lot.sold_amount - totalCosts.value;
    }
    return estimatedMarketValue.value - totalCosts.value;
});

// ─── GRÁFICO APEXCHARTS DE EVOLUÇÃO DE PESO ───
// Se não houver pesagens registradas, mostramos apenas o peso inicial
const chartData = computed(() => {
    const points = [];
    
    // Ponto inicial
    points.push({
        date: 'Inicial',
        weight: props.lot.initial_avg_weight_kg
    });

    // Pesagens subsequentes
    props.weightLogs.forEach(log => {
        points.push({
            date: log.weight_date,
            weight: log.avg_weight_kg
        });
    });

    return points;
});

const chartOptions = computed(() => ({
    chart: { type: 'line', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#15803d'],
    stroke: { curve: 'smooth', width: 3 },
    markers: { size: 5, strokeColors: '#15803d', strokeWidth: 2, fillColors: '#fff' },
    xaxis: {
        categories: chartData.value.map(p => p.date),
        labels: { style: { colors: '#64748b', fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            formatter: val => val.toFixed(1) + ' kg',
            style: { colors: '#64748b' },
        },
    },
    tooltip: {
        y: { formatter: val => val.toFixed(2) + ' kg' }
    },
    grid: { borderColor: '#f1f5f9' },
    dataLabels: { enabled: false }
}));

const chartSeries = computed(() => [
    { name: 'Peso Médio (kg)', data: chartData.value.map(p => p.weight) }
]);

// ─── FORMULÁRIOS RAPIDOS ───
const weightForm = useForm({
    weight_date: new Date().toISOString().split('T')[0],
    avg_weight_kg: '',
    notes: '',
});

function submitWeight() {
    weightForm.post(route('cattle-lots.weight-logs.store', props.lot.id), {
        onSuccess: () => {
            weightForm.reset('avg_weight_kg', 'notes');
        }
    });
}

function removeWeightLog(logId) {
    if (confirm('Deseja realmente remover este registro de pesagem?')) {
        router.delete(route('cattle-lots.weight-logs.destroy', [props.lot.id, logId]));
    }
}

// Lançamento de despesa rápida
const financeForm = useForm({
    type: 'expense',
    category: 'Manejo de Gado',
    amount: '',
    description: '',
    transaction_date: new Date().toISOString().split('T')[0],
    cattle_lot_id: props.lot.id,
});

function submitExpense() {
    financeForm.post(route('financial-transactions.store'), {
        onSuccess: () => {
            financeForm.reset('amount', 'description');
            // Recarrega os dados da página
            router.reload({ only: ['financialTransactions', 'managementCost'] });
        }
    });
}

// Modal de Venda
const showSellModal = ref(false);
const sellForm = ref({
    sold_amount: Math.round(estimatedMarketValue.value * 100) / 100,
    sold_at: new Date().toISOString().split('T')[0],
});

function submitSell() {
    router.post(route('cattle-lots.sell', props.lot.id), {
        sold_amount: sellForm.value.sold_amount,
        sold_at: sellForm.value.sold_at,
    }, {
        onSuccess: () => {
            showSellModal.value = false;
        }
    });
}

const formatCurrency = val =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val ?? 0);
</script>

<template>
    <Head :title="`Lote ${lot.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('cattle-lots.index')" class="text-slate-400 hover:text-slate-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </Link>
                        <h2 class="text-xl font-bold text-slate-800 leading-tight">Lote: {{ lot.name }}</h2>
                        <span 
                            class="text-xs font-bold px-2 py-0.5 rounded-full uppercase"
                            :class="lot.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'"
                        >
                            {{ lot.status === 'active' ? 'Ativo' : 'Vendido' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Cadastrado em {{ lot.uf }} | {{ lot.animal_count }} cabeças
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="route('cattle-lots.edit', lot.id)"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition"
                    >
                        Editar Lote
                    </Link>
                    <button
                        v-if="lot.status === 'active'"
                        @click="showSellModal = true"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition"
                    >
                        Registrar Venda
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <FlashMessage />

                <!-- ─── CARD PRINCIPAL DE KPIS DO LOTE ─── -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <p class="text-xs font-semibold text-slate-400 uppercase">Quantidade</p>
                        <p class="mt-2 text-2xl font-bold text-slate-800">{{ lot.animal_count }} <span class="text-xs font-normal text-slate-500">cabeças</span></p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <p class="text-xs font-semibold text-slate-400 uppercase">Peso Médio Atual</p>
                        <p class="mt-2 text-2xl font-bold text-slate-800">{{ lot.current_avg_weight_kg }} <span class="text-xs font-normal text-slate-500">kg</span></p>
                        <p class="text-[10px] text-slate-400 mt-1">peso inicial: {{ lot.initial_avg_weight_kg }} kg</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <p class="text-xs font-semibold text-slate-400 uppercase">Aquisição do Lote</p>
                        <p class="mt-2 text-xl font-bold text-slate-800">{{ formatCurrency(lot.total_purchase_cost) }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">custo unitário: {{ formatCurrency(lot.total_purchase_cost / lot.animal_count) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                        <p class="text-xs font-semibold text-slate-400 uppercase">Custo de Manejo</p>
                        <p class="mt-2 text-xl font-bold text-amber-600">{{ formatCurrency(managementCost) }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">insumos e manejo vinculados</p>
                    </div>
                </div>

                <!-- ─── TABS DE NAVEGAÇÃO INTERNA ─── -->
                <div class="border-b border-slate-200">
                    <nav class="-mb-px flex space-x-6 text-sm font-semibold">
                        <button
                            @click="activeTab = 'simulator'"
                            :class="[activeTab === 'simulator' ? 'border-green-800 text-green-800' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'pb-4 border-b-2 transition-all']"
                        >
                            Simulador Econômico
                        </button>
                        <button
                            @click="activeTab = 'weighings'"
                            :class="[activeTab === 'weighings' ? 'border-green-800 text-green-800' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'pb-4 border-b-2 transition-all']"
                        >
                            Pesagens & Ganho de Peso
                        </button>
                        <button
                            @click="activeTab = 'finance'"
                            :class="[activeTab === 'finance' ? 'border-green-800 text-green-800' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'pb-4 border-b-2 transition-all']"
                        >
                            Financeiro do Lote
                        </button>
                    </nav>
                </div>

                <!-- ─── CONTEÚDO DAS ABAS ─── -->
                
                <!-- ABA 1: SIMULADOR DE RENDIMENTO E VIABILIDADE -->
                <div v-if="activeTab === 'simulator'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Simulador de Viabilidade Comercial</h3>
                                <p class="text-xs text-slate-400">Ajuste o rendimento físico de carcaça e estime o ganho líquido real do lote</p>
                            </div>
                            <span class="bg-slate-100 text-slate-700 text-xs font-bold px-2 py-1 rounded">
                                Cotação {{ lot.uf }}: {{ formatCurrency(currentQuote.valor) }}
                            </span>
                        </div>

                        <!-- Carcass yield input slider -->
                        <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-semibold text-slate-700">Rendimento de Carcaça (%)</span>
                                <span class="text-lg font-black text-green-800">{{ yieldRate }}%</span>
                            </div>
                            
                            <input
                                v-model.number="yieldRate"
                                type="range"
                                min="50"
                                max="56"
                                step="0.5"
                                class="w-full accent-green-800 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer"
                                :disabled="lot.status === 'sold'"
                            />
                            
                            <div class="flex justify-between text-[10px] text-slate-400">
                                <span>50.0% (Padrão Novilho)</span>
                                <span>53.0% (Média Boi Inteiro)</span>
                                <span>56.0% (Alto Desempenho)</span>
                            </div>
                        </div>

                        <!-- Fórmulas demonstradas de forma premium -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="border border-slate-100 rounded-xl p-4 space-y-2">
                                <p class="font-bold text-slate-700 uppercase tracking-wide text-[10px]">Cálculo da Arroba Física</p>
                                <p class="text-slate-500 leading-relaxed">
                                    A arroba comercial de carcaça equivale a 15kg de carne. 
                                    A fórmula brasileira para conversão de peso vivo (kg) para arrobas (@) é:
                                </p>
                                <div class="bg-slate-50 rounded p-2.5 font-mono text-center text-slate-700 border border-slate-200/50">
                                    @ = (Peso Vivo × Rendimento%) ÷ 15
                                </div>
                            </div>

                            <div class="border border-slate-100 rounded-xl p-4 space-y-2">
                                <p class="font-bold text-slate-700 uppercase tracking-wide text-[10px]">Resultado Líquido do Lote</p>
                                <p class="text-slate-500 leading-relaxed">
                                    O cálculo desconta o valor original de aquisição e todas as despesas acumuladas lançadas como custo de manejo:
                                </p>
                                <div class="bg-slate-50 rounded p-2.5 font-mono text-center text-slate-700 border border-slate-200/50">
                                    Lucro = Valor de Venda − (Aquisição + Manejo)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lado Direito: Resultados Financeiros Reativos -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-5 flex flex-col justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Resultado da Simulação</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Estreita relação entre peso e mercado</p>
                        </div>

                        <div class="space-y-4">
                            <div class="bg-slate-50 rounded-xl p-4 space-y-3.5 text-xs text-slate-600">
                                <div class="flex justify-between">
                                    <span>Total Peso Vivo Lote:</span>
                                    <span class="font-semibold text-slate-800">{{ (lot.current_avg_weight_kg * lot.animal_count).toLocaleString('pt-BR') }} kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Total Arrobas Estimadas:</span>
                                    <span class="font-bold text-slate-800">{{ estimatedLotArrobas.toFixed(2) }} @</span>
                                </div>
                                <div class="flex justify-between border-t border-slate-200/50 pt-2 text-slate-500">
                                    <span>Preço Unitário Estimado:</span>
                                    <span>{{ formatCurrency(estimatedMarketValue / lot.animal_count) }} / boi</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-semibold text-slate-500">Valor Estimado de Mercado:</span>
                                    <span class="text-lg font-black text-slate-800">
                                        {{ lot.status === 'sold' ? formatCurrency(lot.sold_amount) : formatCurrency(estimatedMarketValue) }}
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <div>
                                        <span class="text-xs font-semibold text-slate-500 block">Resultado Comercial:</span>
                                        <span class="text-[10px] text-slate-400">lucro real deduzindo custos</span>
                                    </div>
                                    <span 
                                        class="text-base font-black px-3 py-1 rounded-lg"
                                        :class="estimatedNetProfit >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                    >
                                        {{ estimatedNetProfit >= 0 ? '+' : '' }}{{ formatCurrency(estimatedNetProfit) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100">
                            <div class="bg-amber-50 rounded-xl p-3 border border-amber-100 text-[10px] text-amber-700 flex gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Os valores de cotação são aproximados. O valor real de mercado varia com fatores como frete e comissão.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 2: PESAGENS & CURVA DE PESO -->
                <div v-if="activeTab === 'weighings'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Gráfico e Histórico -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Histórico de Pesagens (Evolução)</h3>
                            <p class="text-xs text-slate-400">Curva de ganho de peso corporal do lote ao longo do tempo</p>
                        </div>

                        <!-- Gráfico ApexCharts -->
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/50">
                            <VueApexCharts
                                type="line"
                                height="280"
                                :options="chartOptions"
                                :series="chartSeries"
                            />
                        </div>

                        <!-- Tabela de Pesagens -->
                        <div class="overflow-hidden rounded-xl border border-slate-100">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 font-bold uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Data da Pesagem</th>
                                        <th class="px-4 py-3 text-right">Peso Médio (kg)</th>
                                        <th class="px-4 py-3 text-left">Observações</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-600">
                                    <tr>
                                        <td class="px-4 py-2.5 text-slate-400 italic">Entrada / Cadastro</td>
                                        <td class="px-4 py-2.5 text-right font-medium text-slate-800">{{ lot.initial_avg_weight_kg }} kg</td>
                                        <td class="px-4 py-2.5 text-slate-400">Peso médio inicial de aquisição</td>
                                        <td class="px-4 py-2.5"></td>
                                    </tr>
                                    <tr v-for="log in weightLogs" :key="log.id" class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-2.5 font-medium">{{ new Date(log.weight_date + 'T00:00:00').toLocaleDateString('pt-BR') }}</td>
                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-800">{{ log.avg_weight_kg }} kg</td>
                                        <td class="px-4 py-2.5 text-slate-500">{{ log.notes ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-right">
                                            <button 
                                                @click="removeWeightLog(log.id)"
                                                class="text-red-500 hover:text-red-700 font-semibold"
                                            >
                                                Excluir
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Registrar Nova Pesagem (Lado Direito) -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Registrar Pesagem</h3>
                            <p class="text-xs text-slate-400">Adicione pesagens periódicas para calibrar o simulador</p>
                        </div>

                        <form @submit.prevent="submitWeight" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Data da Pesagem</label>
                                <input
                                    v-model="weightForm.weight_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Peso Médio (kg)</label>
                                <input
                                    v-model.number="weightForm.avg_weight_kg"
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    required
                                    placeholder="Ex: 480"
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Observações (opcional)</label>
                                <textarea
                                    v-model="weightForm.notes"
                                    rows="2"
                                    placeholder="Ex: Pesagem pós-vacinação"
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="weightForm.processing"
                                class="w-full bg-green-800 text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-green-700 transition"
                            >
                                Salvar Pesagem
                            </button>
                        </form>
                    </div>
                </div>

                <!-- ABA 3: FINANCEIRO DO LOTE (CUSTOS DE MANEJO) -->
                <div v-if="activeTab === 'finance'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Extrato Financeiro -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Extrato Financeiro do Lote</h3>
                            <p class="text-xs text-slate-400">Lista de todas as receitas e despesas vinculadas diretamente a este lote</p>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-slate-100">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 font-bold uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Data</th>
                                        <th class="px-4 py-3 text-left">Categoria</th>
                                        <th class="px-4 py-3 text-left">Descrição</th>
                                        <th class="px-4 py-3 text-right">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-600">
                                    <!-- Custo de aquisição inicial -->
                                    <tr class="bg-amber-50/30">
                                        <td class="px-4 py-2.5 text-slate-500">—</td>
                                        <td class="px-4 py-2.5 text-slate-700 font-semibold">Aquisição Inicial</td>
                                        <td class="px-4 py-2.5 text-slate-500">Investimento de compra do lote de gado</td>
                                        <td class="px-4 py-2.5 text-right font-bold text-rose-600">- {{ formatCurrency(lot.total_purchase_cost) }}</td>
                                    </tr>

                                    <!-- Demais transações -->
                                    <tr v-if="financialTransactions.length === 0">
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                            Nenhuma despesa ou receita adicional lançada para este lote.
                                        </td>
                                    </tr>
                                    <tr v-for="t in financialTransactions" :key="t.id" class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-2.5">{{ new Date(t.transaction_date + 'T00:00:00').toLocaleDateString('pt-BR') }}</td>
                                        <td class="px-4 py-2.5 font-medium text-slate-700">{{ t.category }}</td>
                                        <td class="px-4 py-2.5 text-slate-500">{{ t.description }}</td>
                                        <td 
                                            class="px-4 py-2.5 text-right font-bold whitespace-nowrap"
                                            :class="t.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                                        >
                                            {{ t.type === 'income' ? '+' : '-' }} {{ formatCurrency(t.amount) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Lançamento Rápido de Custos (Lado Direito) -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Lançar Despesa do Lote</h3>
                            <p class="text-xs text-slate-400">Registre custos adicionais (ração, vacina, sal) associados a este lote</p>
                        </div>

                        <form @submit.prevent="submitExpense" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Data do Custo</label>
                                <input
                                    v-model="financeForm.transaction_date"
                                    type="date"
                                    required
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Categoria</label>
                                <select
                                    v-model="financeForm.category"
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                >
                                    <option value="Manejo de Gado">Manejo de Gado</option>
                                    <option value="Alimentação / Ração">Alimentação / Ração</option>
                                    <option value="Vacinas / Medicamentos">Vacinas / Medicamentos</option>
                                    <option value="Frete / Transporte">Frete / Transporte</option>
                                    <option value="Comissões / Taxas">Comissões / Taxas</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Valor Despendido (R$)</label>
                                <input
                                    v-model.number="financeForm.amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    required
                                    placeholder="Ex: 450.00"
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Descrição</label>
                                <input
                                    v-model="financeForm.description"
                                    type="text"
                                    required
                                    placeholder="Ex: 5 sacos de ração inicial"
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="financeForm.processing"
                                class="w-full bg-green-800 text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-green-700 transition"
                            >
                                Registrar Despesa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── MODAL DE CONFIRMAÇÃO DE VENDA DO LOTE ─── -->
        <div v-if="showSellModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4 border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-800">Fechar Lote & Registrar Venda</h3>
                    <button @click="showSellModal = false" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 space-y-1.5 text-xs text-emerald-800">
                    <p class="font-bold">Lote: {{ lot.name }}</p>
                    <p>Cabeças: <span class="font-semibold">{{ lot.animal_count }}</span> | Peso Médio: <span class="font-semibold">{{ lot.current_avg_weight_kg }} kg</span></p>
                    <p>Custo Acumulado: <span class="font-semibold">{{ formatCurrency(totalCosts) }}</span></p>
                </div>

                <form @submit.prevent="submitSell" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Valor Real da Venda (R$)</label>
                        <input
                            v-model.number="sellForm.sold_amount"
                            type="number"
                            step="0.01"
                            required
                            min="0.01"
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                        />
                        <p class="text-[10px] text-slate-400 mt-1">Sugerido com base no simulador ativo (rendimento atual: {{ yieldRate }}%).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Data da Venda</label>
                        <input
                            v-model="sellForm.sold_at"
                            type="date"
                            required
                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="showSellModal = false"
                            class="px-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-600 font-semibold hover:bg-slate-50 transition"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-green-800 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Confirmar & Vender
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
