<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    fieldLogs: Object,
    plots:     Array,
    filters:   Object,
});

const search        = ref(props.filters.search        ?? '');
const plotId        = ref(props.filters.plot_id       ?? '');
const activityType  = ref(props.filters.activity_type ?? '');
const dateFrom      = ref(props.filters.date_from     ?? '');
const dateTo        = ref(props.filters.date_to       ?? '');

let debounce = null;
watch([search, plotId, activityType, dateFrom, dateTo], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('field-logs.index'), {
            search:        search.value        || undefined,
            plot_id:       plotId.value        || undefined,
            activity_type: activityType.value  || undefined,
            date_from:     dateFrom.value      || undefined,
            date_to:       dateTo.value        || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

const activityLabels = {
    planting:    'Plantio',
    spraying:    'Pulverização',
    harvesting:  'Colheita',
    fertilizing: 'Adubação',
    maintenance: 'Manutenção',
    irrigation:  'Irrigação',
    other:       'Outro',
};

// Monta a query string para o PDF respeitando os filtros ativos
const buildPdfQuery = () => {
    const params = new URLSearchParams();
    if (plotId.value)       params.set('plot_id',   plotId.value);
    if (dateFrom.value)     params.set('date_from', dateFrom.value);
    if (dateTo.value)       params.set('date_to',   dateTo.value);
    const qs = params.toString();
    return qs ? '?' + qs : '';
};

const activityClasses = {
    planting:    'bg-green-100 text-green-800',
    spraying:    'bg-blue-100 text-blue-800',
    harvesting:  'bg-amber-100 text-amber-800',
    fertilizing: 'bg-purple-100 text-purple-700',
    maintenance: 'bg-orange-100 text-orange-700',
    irrigation:  'bg-cyan-100 text-cyan-700',
    other:       'bg-gray-100 text-gray-600',
};

function confirmDelete(log) {
    if (confirm('Remover este registro do caderno de campo? A transação financeira vinculada também será removida.')) {
        router.delete(route('field-logs.destroy', log.id));
    }
}
</script>

<template>
    <Head title="Caderno de Campo" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Caderno de Campo</h2>
                <div class="flex items-center gap-3">
                    <a
                        :href="route('field-logs.export.pdf') + buildPdfQuery()"
                        target="_blank"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                    >
                        ↓ Exportar PDF
                    </a>
                    <Link
                        :href="route('field-logs.create')"
                        class="rounded-md bg-green-800 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                    >
                        + Novo Registro
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <FlashMessage />

                <!-- Filtros -->
                <div class="mb-4 flex flex-wrap gap-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Buscar descrição..."
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 w-52"
                    />
                    <select v-model="plotId" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos os talhões</option>
                        <option v-for="plot in plots" :key="plot.id" :value="plot.id">{{ plot.name }}</option>
                    </select>
                    <select v-model="activityType" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todas as atividades</option>
                        <option v-for="(label, val) in activityLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                    <input v-model="dateFrom" type="date" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                    <input v-model="dateTo"   type="date" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500" />
                </div>

                <!-- Tabela -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Data</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Talhão</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Atividade</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Descrição</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Máquina</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wide text-xs">Horas</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wide text-xs">Custo Total</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wide text-xs">Financeiro</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="fieldLogs.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                            <tr
                                v-for="log in fieldLogs.data"
                                :key="log.id"
                                class="hover:bg-slate-50 transition"
                            >
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ log.log_date }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ log.plot?.name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', activityClasses[log.activity_type]]">
                                        {{ activityLabels[log.activity_type] ?? log.activity_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ log.description }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ log.asset?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    {{ log.machine_hours ? `${Number(log.machine_hours).toLocaleString('pt-BR')} h` : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800">
                                    {{ Number(log.total_cost).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="log.generates_transaction" class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">✓</span>
                                    <span v-else class="text-gray-300 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <Link :href="route('field-logs.edit', log.id)" class="mr-3 text-green-700 hover:text-green-900 font-medium">Editar</Link>
                                    <button @click="confirmDelete(log)" class="text-red-500 hover:text-red-700 font-medium">Remover</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="fieldLogs.links" class="mt-4 flex justify-end gap-1">
                    <template v-for="link in fieldLogs.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded px-3 py-1 text-sm"
                            :class="link.active ? 'bg-green-800 text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'"
                        ><span v-html="link.label" /></Link>
                        <span v-else class="rounded border border-gray-100 px-3 py-1 text-sm text-gray-300"><span v-html="link.label" /></span>
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
