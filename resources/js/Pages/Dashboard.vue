<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import { computed } from 'vue';

const props = defineProps({
    kpis: Object,
    profitByPlot: Array,
    hoursByType: Array,
    monthlyTrend: Array,
    maintenanceAlerts: Array,
    recentLogs: Array,
});

// ─── Labels de atividade ───────────────────────────────────────────────────
const activityLabels = {
    planting:     'Plantio',
    spraying:     'Pulverização',
    harvesting:   'Colheita',
    fertilizing:  'Adubação',
    maintenance:  'Manutenção',
    irrigation:   'Irrigação',
    soil_prep:    'Preparo do Solo',
    other:        'Outro',
};

// ─── Gráfico 1: Lucro por Talhão (barras empilhadas) ──────────────────────
const barChartOptions = computed(() => ({
    chart: { type: 'bar', stacked: false, toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#15803d', '#ef4444'],
    plotOptions: {
        bar: { borderRadius: 4, columnWidth: '55%' },
    },
    xaxis: {
        categories: props.profitByPlot.map(p => p.name),
        labels: { style: { colors: '#64748b', fontSize: '12px' } },
    },
    yaxis: {
        labels: {
            formatter: val => 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 0 }),
            style: { colors: '#64748b' },
        },
    },
    tooltip: {
        y: { formatter: val => 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) },
    },
    legend: { position: 'top' },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f1f5f9' },
}));

const barChartSeries = computed(() => [
    { name: 'Receita', data: props.profitByPlot.map(p => p.receita) },
    { name: 'Custo',   data: props.profitByPlot.map(p => p.custo) },
]);

// ─── Gráfico 2: Horas por tipo de ativo (rosca) ───────────────────────────
const donutChartOptions = computed(() => ({
    chart: { type: 'donut', fontFamily: 'inherit' },
    colors: ['#15803d', '#16a34a', '#22c55e', '#86efac', '#bbf7d0'],
    labels: props.hoursByType.map(h => h.label),
    tooltip: {
        y: { formatter: val => val.toFixed(1) + ' h' },
    },
    legend: { position: 'bottom', fontSize: '12px' },
    plotOptions: {
        pie: { donut: { size: '65%', labels: {
            show: true,
            total: {
                show: true,
                label: 'Total',
                formatter: w => {
                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                    return total.toFixed(0) + ' h';
                },
            },
        } } },
    },
    dataLabels: { enabled: false },
}));

const donutSeries = computed(() => props.hoursByType.map(h => h.hours));

// ─── Gráfico 3: Tendência financeira (área) ───────────────────────────────
const areaChartOptions = computed(() => ({
    chart: { type: 'area', toolbar: { show: false }, fontFamily: 'inherit' },
    colors: ['#15803d', '#ef4444'],
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 },
    },
    xaxis: {
        categories: props.monthlyTrend.map(m => m.label),
        labels: { style: { colors: '#64748b', fontSize: '12px' } },
    },
    yaxis: {
        labels: {
            formatter: val => 'R$ ' + (val / 1000).toFixed(0) + 'k',
            style: { colors: '#64748b' },
        },
    },
    tooltip: {
        y: { formatter: val => 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) },
    },
    legend: { position: 'top' },
    dataLabels: { enabled: false },
    grid: { borderColor: '#f1f5f9' },
}));

const areaChartSeries = computed(() => [
    { name: 'Receita', data: props.monthlyTrend.map(m => m.receita) },
    { name: 'Despesa', data: props.monthlyTrend.map(m => m.despesa) },
]);

// ─── Formatação ───────────────────────────────────────────────────────────
const formatCurrency = val =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val ?? 0);

const activityBadgeClass = type => {
    const map = {
        planting:    'bg-green-100 text-green-800',
        spraying:    'bg-blue-100 text-blue-800',
        harvesting:  'bg-yellow-100 text-yellow-800',
        fertilizing: 'bg-orange-100 text-orange-800',
        maintenance: 'bg-red-100 text-red-800',
        irrigation:  'bg-cyan-100 text-cyan-800',
        soil_prep:   'bg-stone-100 text-stone-800',
    };
    return map[type] ?? 'bg-slate-100 text-slate-700';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-slate-800">Dashboard</h2>
        </template>

        <div class="py-8 space-y-8">

            <!-- ─── KPI Cards ─────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-4 sm:px-6 lg:px-8">

                <!-- Receitas -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Receita Total</p>
                    <p class="mt-1 text-2xl font-bold text-green-700">{{ formatCurrency(kpis.total_income) }}</p>
                    <p class="mt-1 text-xs text-slate-400">todas as transações</p>
                </div>

                <!-- Despesas -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Despesa Total</p>
                    <p class="mt-1 text-2xl font-bold text-red-600">{{ formatCurrency(kpis.total_expense) }}</p>
                    <p class="mt-1 text-xs text-slate-400">todas as transações</p>
                </div>

                <!-- Saldo -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Saldo</p>
                    <p
                        class="mt-1 text-2xl font-bold"
                        :class="kpis.balance >= 0 ? 'text-green-700' : 'text-red-600'"
                    >{{ formatCurrency(kpis.balance) }}</p>
                    <p class="mt-1 text-xs text-slate-400">receita − despesa</p>
                </div>

                <!-- Alertas de Manutenção -->
                <div
                    class="rounded-xl shadow-sm border p-5"
                    :class="kpis.maintenance_alert_count > 0
                        ? 'bg-amber-50 border-amber-200'
                        : 'bg-white border-slate-100'"
                >
                    <p class="text-xs font-medium uppercase tracking-wide"
                       :class="kpis.maintenance_alert_count > 0 ? 'text-amber-700' : 'text-slate-500'">
                        Manutenção Pendente
                    </p>
                    <p class="mt-1 text-2xl font-bold"
                       :class="kpis.maintenance_alert_count > 0 ? 'text-amber-600' : 'text-slate-700'">
                        {{ kpis.maintenance_alert_count }}
                        <span class="text-base font-normal">ativo(s)</span>
                    </p>
                    <p class="mt-1 text-xs"
                       :class="kpis.maintenance_alert_count > 0 ? 'text-amber-600' : 'text-slate-400'">
                        de {{ kpis.total_assets }} ativos
                    </p>
                </div>

                <!-- Talhões ativos -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Talhões Ativos</p>
                    <p class="mt-1 text-2xl font-bold text-green-800">{{ kpis.active_plots }}</p>
                    <p class="mt-1 text-xs text-slate-400">em cultivo</p>
                </div>

                <!-- Ativos totais -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ativos Cadastrados</p>
                    <p class="mt-1 text-2xl font-bold text-green-800">{{ kpis.total_assets }}</p>
                    <p class="mt-1 text-xs text-slate-400">máquinas e equipamentos</p>
                </div>

                <!-- Registros do mês -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Registros Este Mês</p>
                    <p class="mt-1 text-2xl font-bold text-green-800">{{ kpis.logs_this_month }}</p>
                    <p class="mt-1 text-xs text-slate-400">entradas no caderno</p>
                </div>

                <!-- Atalho rápido -->
                <div class="bg-green-800 rounded-xl shadow-sm p-5 flex flex-col justify-between">
                    <p class="text-xs font-medium text-green-200 uppercase tracking-wide">Ação Rápida</p>
                    <Link
                        :href="route('field-logs.create')"
                        class="mt-3 inline-block text-center text-sm font-semibold text-green-800 bg-white rounded-lg px-4 py-2 hover:bg-green-50 transition"
                    >
                        + Novo Registro
                    </Link>
                </div>
            </div>

            <!-- ─── Gráficos ───────────────────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 px-4 sm:px-6 lg:px-8">

                <!-- Gráfico: Receita vs Custo por Talhão -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Receita vs Custo por Talhão</h3>
                    <template v-if="profitByPlot.length > 0">
                        <VueApexCharts
                            type="bar"
                            height="280"
                            :options="barChartOptions"
                            :series="barChartSeries"
                        />
                    </template>
                    <div v-else class="h-64 flex items-center justify-center text-slate-400 text-sm">
                        Nenhum dado financeiro por talhão ainda.
                    </div>
                </div>

                <!-- Gráfico: Tendência Financeira (6 meses) -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Tendência Financeira (6 meses)</h3>
                    <template v-if="monthlyTrend.length > 0">
                        <VueApexCharts
                            type="area"
                            height="280"
                            :options="areaChartOptions"
                            :series="areaChartSeries"
                        />
                    </template>
                    <div v-else class="h-64 flex items-center justify-center text-slate-400 text-sm">
                        Ainda sem dados dos últimos 6 meses.
                    </div>
                </div>

                <!-- Gráfico: Horas de Máquina por Tipo -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">Horas de Máquina por Tipo</h3>
                    <template v-if="hoursByType.length > 0 && donutSeries.some(v => v > 0)">
                        <VueApexCharts
                            type="donut"
                            height="280"
                            :options="donutChartOptions"
                            :series="donutSeries"
                        />
                    </template>
                    <div v-else class="h-64 flex items-center justify-center text-slate-400 text-sm">
                        Nenhuma hora registrada nos ativos.
                    </div>
                </div>

                <!-- Alertas de Manutenção -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-4">
                        Alertas de Manutenção
                        <span v-if="maintenanceAlerts.length > 0"
                              class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            {{ maintenanceAlerts.length }}
                        </span>
                    </h3>
                    <template v-if="maintenanceAlerts.length > 0">
                        <ul class="divide-y divide-slate-100">
                            <li
                                v-for="alert in maintenanceAlerts"
                                :key="alert.id"
                                class="py-3 flex items-center justify-between"
                            >
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ alert.name }}</p>
                                    <p class="text-xs text-slate-500">{{ alert.type }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        {{ alert.hours_overdue }}h acumuladas
                                    </span>
                                    <p class="text-xs text-slate-400 mt-0.5">limite: {{ alert.maintenance_alert_hours }}h</p>
                                </div>
                            </li>
                        </ul>
                        <Link
                            :href="route('assets.index')"
                            class="mt-4 inline-block text-xs font-medium text-green-700 hover:text-green-900"
                        >
                            Ver todos os ativos →
                        </Link>
                    </template>
                    <div v-else class="h-48 flex flex-col items-center justify-center text-slate-400 text-sm gap-2">
                        <svg class="w-8 h-8 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Todos os ativos dentro do limite de manutenção.
                    </div>
                </div>
            </div>

            <!-- ─── Feed de Atividades Recentes ───────────────────────────── -->
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-700">Últimos Registros do Caderno</h3>
                        <Link :href="route('field-logs.index')"
                              class="text-xs font-medium text-green-700 hover:text-green-900">
                            Ver todos →
                        </Link>
                    </div>

                    <template v-if="recentLogs.length > 0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-slate-500 uppercase border-b border-slate-100">
                                        <th class="pb-2 text-left font-medium">Data</th>
                                        <th class="pb-2 text-left font-medium">Atividade</th>
                                        <th class="pb-2 text-left font-medium">Talhão</th>
                                        <th class="pb-2 text-left font-medium">Ativo</th>
                                        <th class="pb-2 text-right font-medium">Custo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50 transition">
                                        <td class="py-2.5 text-slate-600">{{ log.log_date }}</td>
                                        <td class="py-2.5">
                                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', activityBadgeClass(log.activity_type)]">
                                                {{ activityLabels[log.activity_type] ?? log.activity_type }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-slate-700">{{ log.plot_name ?? '—' }}</td>
                                        <td class="py-2.5 text-slate-500 text-xs">{{ log.asset_name ?? '—' }}</td>
                                        <td class="py-2.5 text-right font-medium text-slate-700">
                                            {{ formatCurrency(log.total_cost) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <div v-else class="py-8 text-center text-slate-400 text-sm">
                        Nenhum registro no caderno de campo ainda.
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

