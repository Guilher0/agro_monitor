<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    plots:   Object,
    filters: Object,
});

const search  = ref(props.filters.search  ?? '');
const status  = ref(props.filters.status  ?? '');
const culture = ref(props.filters.culture ?? '');

let debounce = null;
watch([search, status, culture], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('plots.index'), {
            search:  search.value  || undefined,
            status:  status.value  || undefined,
            culture: culture.value || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

const statusLabels = {
    active:    'Ativo',
    fallow:    'Pousio',
    harvested: 'Colhido',
};

const statusClasses = {
    active:    'bg-green-100 text-green-800',
    fallow:    'bg-gray-100 text-gray-600',
    harvested: 'bg-blue-100 text-blue-700',
};

function confirmDelete(plot) {
    if (confirm(`Remover talhão "${plot.name}"?`)) {
        router.delete(route('plots.destroy', plot.id));
    }
}
</script>

<template>
    <Head title="Talhões" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Talhões</h2>
                <Link
                    :href="route('plots.create')"
                    class="rounded-md bg-green-800 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                >
                    + Novo Talhão
                </Link>
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
                        placeholder="Buscar por nome ou cultura..."
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 w-64"
                    />
                    <select v-model="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos os status</option>
                        <option v-for="(label, val) in statusLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                    <input
                        v-model="culture"
                        type="text"
                        placeholder="Filtrar por cultura..."
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 w-48"
                    />
                </div>

                <!-- Tabela -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Talhão</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wide text-xs">Área (ha)</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Cultura</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Safra</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Solo</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wide text-xs">Registros</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="plots.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    Nenhum talhão encontrado.
                                </td>
                            </tr>
                            <tr
                                v-for="plot in plots.data"
                                :key="plot.id"
                                class="hover:bg-slate-50 transition"
                            >
                                <td class="px-4 py-3 font-medium text-gray-900">{{ plot.name }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ Number(plot.area_hectares).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ plot.culture ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plot.season ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ plot.soil_type ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <Link
                                        :href="route('field-logs.index', { plot_id: plot.id })"
                                        class="text-green-700 hover:text-green-900 font-medium"
                                    >
                                        {{ plot.field_logs_count }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', statusClasses[plot.status]]">
                                        {{ statusLabels[plot.status] ?? plot.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <Link :href="route('plots.edit', plot.id)" class="mr-3 text-green-700 hover:text-green-900 font-medium">Editar</Link>
                                    <button @click="confirmDelete(plot)" class="text-red-500 hover:text-red-700 font-medium">Remover</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="plots.links" class="mt-4 flex justify-end gap-1">
                    <template v-for="link in plots.links" :key="link.label">
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
