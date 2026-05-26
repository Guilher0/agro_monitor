<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    lots: Object,
    filters: Object,
    currentQuote: Object,
    kpis: Object,
});

// Filtros reativos para a tabela de lotes
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const activeUf = ref(props.filters.uf ?? 'TO');

// Debounce para os filtros da listagem
let debounce = null;
watch([search, status, activeUf], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('cattle-lots.index'), {
            search: search.value || undefined,
            status: status.value || undefined,
            uf: activeUf.value || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

// Modal de Venda
const showSellModal = ref(false);
const selectedLot = ref(null);
const sellForm = ref({
    sold_amount: 0,
    sold_at: new Date().toISOString().split('T')[0],
});

// Abre o modal de venda e pré-calcula o valor estimado de mercado com base na cotação ativa
function openSellModal(lot) {
    selectedLot.value = lot;
    // Fórmula: (peso atual * cabeças) / 30 * valor da arroba ativa
    const estimatedValue = ((lot.current_avg_weight_kg * lot.animal_count) / 30) * props.currentQuote.valor;
    sellForm.value.sold_amount = Math.round(estimatedValue * 100) / 100;
    sellForm.value.sold_at = new Date().toISOString().split('T')[0];
    showSellModal.value = true;
}

function closeSellModal() {
    showSellModal.value = false;
    selectedLot.value = null;
}

function submitSell() {
    if (!selectedLot.value) return;
    router.post(route('cattle-lots.sell', selectedLot.value.id), {
        sold_amount: sellForm.value.sold_amount,
        sold_at: sellForm.value.sold_at,
    }, {
        onSuccess: () => {
            closeSellModal();
        }
    });
}

// Remoção de lote
function confirmDelete(lot) {
    if (confirm(`Remover lote "${lot.name}"? Isso também removerá o histórico de pesagens.`)) {
        router.delete(route('cattle-lots.destroy', lot.id));
    }
}

// ─── LÓGICA DA CALCULADORA RÁPIDA INDIVIDUAL (SIMULADOR DE BOLSO) ───
const calcWeight = ref(450); // kg
const calcYield = ref(50); // %
const calcPurchase = ref('');
const calcManagement = ref('');

// Arrobas geradas pelo animal: peso vivo * (yield / 100) / 15kg
const calcArrobas = computed(() => {
    return (calcWeight.value * (calcYield.value / 100)) / 15;
});

// Valor de mercado do animal
const calcMarketValue = computed(() => {
    return calcArrobas.value * props.currentQuote.valor;
});

// ROI Líquido
const calcNetProfit = computed(() => {
    const cost = Number(calcPurchase.value || 0) + Number(calcManagement.value || 0);
    return calcMarketValue.value - cost;
});

// Lista de UFs homologadas para cotação
const allowedUfs = [
    { value: 'TO', name: 'Tocantins' },
    { value: 'SP', name: 'São Paulo' },
    { value: 'MG', name: 'Minas Gerais' },
    { value: 'MS', name: 'Mato Grosso do Sul' },
    { value: 'MT', name: 'Mato Grosso' },
    { value: 'GO', name: 'Goiás' },
    { value: 'PR', name: 'Paraná' },
    { value: 'PA', name: 'Pará' },
    { value: 'RO', name: 'Rondônia' },
    { value: 'BA', name: 'Bahia' },
];

const statusLabels = {
    active: 'Ativo',
    sold: 'Vendido',
};

const statusClasses = {
    active: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
    sold: 'bg-slate-100 text-slate-700 border border-slate-200',
};

const formatCurrency = val =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val ?? 0);
</script>

<template>
    <Head title="Pecuária - Lotes de Gado" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 leading-tight">Pecuária</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Gestão inteligente de rebanho de corte e cotação em tempo real</p>
                </div>
                <Link
                    :href="route('cattle-lots.create')"
                    class="rounded-lg bg-green-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 hover:shadow transition-all flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Novo Lote
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <FlashMessage />

                <!-- ─── painel de cotação e kpis ─── -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Card de Cotação Cepea/Scot -->
                    <div class="bg-gradient-to-br from-green-800 to-green-950 text-white rounded-2xl p-6 shadow-sm flex flex-col justify-between relative overflow-hidden">
                        <!-- BG Details -->
                        <div class="absolute right-0 bottom-0 opacity-10 pointer-events-none transform translate-y-4 translate-x-4">
                            <svg class="w-48 h-48" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17,8C8,8 4,16 4,16C4,16 8,10 17,10C18,10 19,11 19,12V15L23,11L19,7V9C18,9 17.5,8.5 17,8Z" />
                            </svg>
                        </div>
                        
                        <div class="flex items-center justify-between z-10">
                            <div>
                                <span class="bg-green-700/60 border border-green-600/30 text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider text-green-100">
                                    Cotação ESALQ/Scot
                                </span>
                                <h3 class="text-sm font-medium text-green-200 mt-2">Arroba do Boi Gordo</h3>
                            </div>
                            <!-- Seletor de UF Dinâmico -->
                            <select 
                                v-model="activeUf"
                                class="bg-green-700/80 border border-green-600/30 text-white text-xs font-bold rounded-lg px-2.5 py-1.5 focus:ring-green-500 focus:border-green-500 cursor-pointer"
                            >
                                <option v-for="ufOption in allowedUfs" :key="ufOption.value" :value="ufOption.value">
                                    {{ ufOption.value }}
                                </option>
                            </select>
                        </div>

                        <div class="my-6 z-10">
                            <p class="text-4xl font-extrabold tracking-tight">
                                {{ formatCurrency(currentQuote.valor) }}
                                <span class="text-lg font-normal text-green-300">/ @</span>
                            </p>
                            <p class="text-xs text-green-300 mt-1 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Fonte: {{ currentQuote.fonte }} ({{ currentQuote.data_cotacao }})
                            </p>
                        </div>
                    </div>

                    <!-- KPIs Rápidos dos Lotes Ativos -->
                    <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex flex-col justify-between">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Animais Ativos</p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">{{ kpis.total_animals }}</p>
                            <p class="text-[10px] text-slate-400 mt-1">cabeças no pasto</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex flex-col justify-between">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Peso Médio</p>
                            <p class="mt-2 text-2xl font-bold text-slate-800">{{ kpis.avg_weight_kg }} <span class="text-xs font-normal text-slate-500">kg</span></p>
                            <p class="text-[10px] text-slate-400 mt-1">~{{ Math.round(kpis.avg_weight_kg / 30 * 10) / 10 }} @ carcaça</p>
                        </div>
                        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5 flex flex-col justify-between">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Valor Estimado</p>
                            <p class="mt-2 text-xl font-bold text-slate-800">{{ formatCurrency(kpis.estimated_market_value) }}</p>
                            <p class="text-[10px] text-emerald-600 font-semibold mt-1">Cotação {{ activeUf }}</p>
                        </div>
                        <div class="rounded-xl shadow-sm border p-5 flex flex-col justify-between"
                             :class="kpis.estimated_roi >= 0 ? 'bg-emerald-50/50 border-emerald-100' : 'bg-rose-50/50 border-rose-100'">
                            <p class="text-xs font-semibold uppercase tracking-wider"
                               :class="kpis.estimated_roi >= 0 ? 'text-emerald-700' : 'text-rose-700'">Retorno Estimado</p>
                            <p class="mt-2 text-xl font-bold"
                               :class="kpis.estimated_roi >= 0 ? 'text-emerald-700' : 'text-rose-600'">
                                {{ formatCurrency(kpis.estimated_roi) }}
                            </p>
                            <p class="text-[10px]" :class="kpis.estimated_roi >= 0 ? 'text-emerald-600' : 'text-rose-500'">
                                vs. custo de aquisição
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ─── LISTAGEM + CALCULADORA DE BOLSO ─── -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    
                    <!-- Tabela de Lotes (2 Colunas no Grid) -->
                    <div class="lg:col-span-2 space-y-4">
                        
                        <!-- Barra de Busca/Filtros -->
                        <div class="bg-white rounded-xl border border-slate-100 p-4 shadow-sm flex flex-wrap gap-3">
                            <div class="relative flex-1 min-w-[240px]">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input
                                    v-model="search"
                                    type="search"
                                    placeholder="Buscar lote por nome..."
                                    class="rounded-lg border-slate-200 pl-9 text-sm w-full focus:border-green-500 focus:ring-green-500 shadow-sm"
                                />
                            </div>
                            <select v-model="status" class="rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                                <option value="">Todos os status</option>
                                <option v-for="(label, val) in statusLabels" :key="val" :value="val">{{ label }}</option>
                            </select>
                        </div>

                        <!-- Tabela de Lotes -->
                        <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500 tracking-wider">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Lote</th>
                                        <th class="px-4 py-3 text-center">Cabeças</th>
                                        <th class="px-4 py-3 text-right">Peso Médio</th>
                                        <th class="px-4 py-3 text-right">Aquisição</th>
                                        <th class="px-4 py-3 text-center">Estado</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-600">
                                    <tr v-if="lots.data.length === 0">
                                        <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                            Nenhum lote de gado cadastrado ou encontrado.
                                        </td>
                                    </tr>
                                    <tr v-for="lot in lots.data" :key="lot.id" class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3.5">
                                            <Link :href="route('cattle-lots.show', lot.id)" class="font-semibold text-slate-800 hover:text-green-800 transition">
                                                {{ lot.name }}
                                            </Link>
                                        </td>
                                        <td class="px-4 py-3.5 text-center font-medium">{{ lot.animal_count }}</td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                            <p class="font-medium text-slate-800">{{ lot.current_avg_weight_kg }} kg</p>
                                            <p class="text-[10px] text-slate-400">~{{ Math.round(lot.current_avg_weight_kg / 30 * 10) / 10 }} @</p>
                                        </td>
                                        <td class="px-4 py-3.5 text-right font-medium text-slate-700">
                                            {{ formatCurrency(lot.total_purchase_cost) }}
                                        </td>
                                        <td class="px-4 py-3.5 text-center">
                                            <span class="inline-block bg-slate-100 font-bold text-xs text-slate-700 px-2 py-0.5 rounded">
                                                {{ lot.uf }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5">
                                            <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', statusClasses[lot.status]]">
                                                {{ statusLabels[lot.status] ?? lot.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3.5 text-right whitespace-nowrap text-xs font-semibold">
                                            <Link :href="route('cattle-lots.show', lot.id)" class="mr-3 text-green-700 hover:text-green-900 transition">Detalhes</Link>
                                            <button 
                                                v-if="lot.status === 'active'" 
                                                @click="openSellModal(lot)" 
                                                class="mr-3 text-emerald-600 hover:text-emerald-800 transition"
                                            >
                                                Vender Lote
                                            </button>
                                            <button @click="confirmDelete(lot)" class="text-red-500 hover:text-red-700 transition">Excluir</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div v-if="lots.links" class="flex justify-end gap-1">
                            <template v-for="link in lots.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    class="rounded-lg px-3 py-1.5 text-sm font-medium border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition"
                                    :class="{ 'bg-green-800 border-green-800 text-white hover:bg-green-700': link.active }"
                                ><span v-html="link.label" /></Link>
                                <span v-else class="rounded-lg px-3 py-1.5 text-sm text-slate-300 border border-slate-100 bg-white" v-html="link.label" />
                            </template>
                        </div>
                    </div>

                    <!-- Widget: Calculadora de Bolso / Simulador Rápido (1 Coluna) -->
                    <div class="bg-white border border-slate-100 rounded-2xl shadow-sm p-6 space-y-5">
                        <div>
                            <div class="flex items-center gap-2">
                                <div class="bg-green-100 text-green-800 p-1.5 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-slate-800">Simulador de Bolso (1 Boi)</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Faça uma simulação instantânea de peso e lucro sem salvar no banco.</p>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Inputs -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Peso Vivo (kg)</label>
                                <input
                                    v-model.number="calcWeight"
                                    type="number"
                                    min="1"
                                    class="mt-1 block w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500"
                                />
                            </div>

                            <div>
                                <div class="flex justify-between items-center">
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Rendimento de Carcaça</label>
                                    <span class="text-sm font-bold text-green-700">{{ calcYield }}%</span>
                                </div>
                                <input
                                    v-model.number="calcYield"
                                    type="range"
                                    min="50"
                                    max="56"
                                    step="0.5"
                                    class="w-full accent-green-800 h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer mt-2"
                                />
                                <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                                    <span>50% (Padrão)</span>
                                    <span>53% (Bom)</span>
                                    <span>56% (Extra)</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-500 uppercase">Custo Compra (R$)</label>
                                    <input
                                        v-model="calcPurchase"
                                        type="number"
                                        placeholder="Ex: 2200"
                                        class="mt-1 block w-full rounded-lg border-slate-200 text-xs shadow-sm focus:border-green-500 focus:ring-green-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-500 uppercase">Manejo/Ração (R$)</label>
                                    <input
                                        v-model="calcManagement"
                                        type="number"
                                        placeholder="Ex: 300"
                                        class="mt-1 block w-full rounded-lg border-slate-200 text-xs shadow-sm focus:border-green-500 focus:ring-green-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <hr class="border-slate-100" />

                        <!-- Outputs -->
                        <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-500">Rendimento Resultante:</span>
                                <span class="font-semibold text-slate-700">{{ calcArrobas.toFixed(2) }} @</span>
                            </div>
                            
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-500">Cotação Usada ({{ activeUf }}):</span>
                                <span class="font-bold text-slate-700">{{ formatCurrency(currentQuote.valor) }}</span>
                            </div>

                            <div class="flex justify-between items-center border-t border-slate-200/50 pt-2">
                                <span class="text-xs font-semibold text-slate-500">Valor de Mercado:</span>
                                <span class="text-sm font-extrabold text-slate-800">{{ formatCurrency(calcMarketValue) }}</span>
                            </div>

                            <div class="flex justify-between items-center border-t border-slate-200/50 pt-2">
                                <span class="text-xs font-semibold text-slate-500">Resultado Líquido:</span>
                                <span 
                                    class="text-sm font-black px-2 py-0.5 rounded-md"
                                    :class="calcNetProfit >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                >
                                    {{ calcNetProfit >= 0 ? '+' : '' }}{{ formatCurrency(calcNetProfit) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── MODAL DE CONFIRMAÇÃO DE VENDA DO LOTE ─── -->
        <div v-if="showSellModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm px-4">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 space-y-4 border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-800">Fechar Lote & Registrar Venda</h3>
                    <button @click="closeSellModal" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 space-y-1.5 text-xs text-emerald-800">
                    <p class="font-bold">Lote Selecionado: {{ selectedLot?.name }}</p>
                    <p>Cabeças: <span class="font-semibold">{{ selectedLot?.animal_count }}</span> | Peso Médio: <span class="font-semibold">{{ selectedLot?.current_avg_weight_kg }} kg</span></p>
                    <p>Custo de Aquisição: <span class="font-semibold">{{ formatCurrency(selectedLot?.total_purchase_cost) }}</span></p>
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
                        <p class="text-[10px] text-slate-400 mt-1">Pré-preenchido com o valor de mercado estimado da arroba ({{ activeUf }}) de hoje.</p>
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
                            @click="closeSellModal"
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
                            Confirmar & Lançar Receita
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
