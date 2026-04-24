<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    assets:  Object,
    filters: Object,
});

const search = ref(props.filters.search ?? '');
const type   = ref(props.filters.type ?? '');
const status = ref(props.filters.status ?? '');

let debounce = null;
watch([search, type, status], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('assets.index'), {
            search: search.value || undefined,
            type:   type.value   || undefined,
            status: status.value || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

const typeLabels = {
    tractor:   'Trator',
    harvester: 'Colheitadeira',
    sprayer:   'Pulverizador',
    implement: 'Implemento',
    other:     'Outro',
};

const statusLabels = {
    active:      'Ativo',
    maintenance: 'Manutenção',
    inactive:    'Inativo',
};

const statusClasses = {
    active:      'bg-green-100 text-green-800',
    maintenance: 'bg-amber-100 text-amber-800',
    inactive:    'bg-gray-100 text-gray-600',
};

function confirmDelete(asset) {
    if (confirm(`Remover "${asset.name}"?`)) {
        router.delete(route('assets.destroy', asset.id));
    }
}
</script>

<template>
    <Head title="Ativos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Ativos Agrícolas</h2>
                <Link
                    :href="route('assets.create')"
                    class="rounded-md bg-green-800 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                >
                    + Novo Ativo
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
                        placeholder="Buscar por nome ou série..."
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 w-64"
                    />
                    <select v-model="type" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos os tipos</option>
                        <option v-for="(label, val) in typeLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                    <select v-model="status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos os status</option>
                        <option v-for="(label, val) in statusLabels" :key="val" :value="val">{{ label }}</option>
                    </select>
                </div>

                <!-- Tabela -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Nome</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Tipo</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wide text-xs">Horas Totais</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wide text-xs">R$/hora</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Manutenção</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="assets.data.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                                    Nenhum ativo encontrado.
                                </td>
                            </tr>
                            <tr
                                v-for="asset in assets.data"
                                :key="asset.id"
                                class="hover:bg-slate-50 transition"
                                :class="{ 'bg-amber-50': asset.needs_maintenance }"
                            >
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ asset.name }}
                                    <span v-if="asset.needs_maintenance" class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                                        ⚠ Manutenção
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ typeLabels[asset.type] ?? asset.type }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ Number(asset.total_hours).toLocaleString('pt-BR') }} h</td>
                                <td class="px-4 py-3 text-right text-gray-700">
                                    {{ Number(asset.hourly_rate).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', statusClasses[asset.status]]">
                                        {{ statusLabels[asset.status] ?? asset.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ asset.last_maintenance_at ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('assets.edit', asset.id)" class="mr-3 text-green-700 hover:text-green-900 font-medium">Editar</Link>
                                    <a :href="route('assets.qrcode', asset.id)" target="_blank"
                                       class="mr-3 text-slate-500 hover:text-slate-700 font-medium text-xs">
                                        QR Code
                                    </a>
                                    <button @click="confirmDelete(asset)" class="text-red-500 hover:text-red-700 font-medium">Remover</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="assets.links" class="mt-4 flex justify-end gap-1">
                    <template v-for="link in assets.links" :key="link.label">
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
