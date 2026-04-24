<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    transactions: Object,
    summary:      Object,
    plots:        Array,
    filters:      Object,
});

const type      = ref(props.filters.type      ?? '');
const plotId    = ref(props.filters.plot_id   ?? '');
const dateFrom  = ref(props.filters.date_from ?? '');
const dateTo    = ref(props.filters.date_to   ?? '');

let debounce = null;
watch([type, plotId, dateFrom, dateTo], () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('financial-transactions.index'), {
            type:      type.value    || undefined,
            plot_id:   plotId.value  || undefined,
            date_from: dateFrom.value || undefined,
            date_to:   dateTo.value   || undefined,
        }, { preserveState: true, replace: true });
    }, 350);
});

function confirmDelete(t) {
    if (confirm('Remover este lançamento financeiro?')) {
        router.delete(route('financial-transactions.destroy', t.id));
    }
}

const formatBRL = (value) =>
    Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
</script>

<template>
    <Head title="Financeiro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Financeiro</h2>
                <Link
                    :href="route('financial-transactions.create')"
                    class="rounded-md bg-green-800 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition"
                >
                    + Novo Lançamento
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <FlashMessage />

                <!-- Cards de resumo -->
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-white p-4 shadow">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Receitas</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">{{ formatBRL(summary.total_income) }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Despesas</p>
                        <p class="mt-1 text-2xl font-bold text-red-600">{{ formatBRL(summary.total_expense) }}</p>
                    </div>
                    <div class="rounded-lg p-4 shadow" :class="summary.balance >= 0 ? 'bg-green-50' : 'bg-red-50'">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Saldo</p>
                        <p class="mt-1 text-2xl font-bold" :class="summary.balance >= 0 ? 'text-green-800' : 'text-red-700'">
                            {{ formatBRL(summary.balance) }}
                        </p>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="mb-4 flex flex-wrap gap-3">
                    <select v-model="type" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Receitas e Despesas</option>
                        <option value="income">Receitas</option>
                        <option value="expense">Despesas</option>
                    </select>
                    <select v-model="plotId" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos os talhões</option>
                        <option v-for="plot in plots" :key="plot.id" :value="plot.id">{{ plot.name }}</option>
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
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Tipo</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Categoria</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Descrição</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wide text-xs">Talhão</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wide text-xs">Valor</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 uppercase tracking-wide text-xs">Origem</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="transactions.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                                    Nenhum lançamento encontrado.
                                </td>
                            </tr>
                            <tr
                                v-for="t in transactions.data"
                                :key="t.id"
                                class="hover:bg-slate-50 transition"
                            >
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ t.transaction_date }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex rounded-full px-2 py-0.5 text-xs font-semibold', t.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700']">
                                        {{ t.type === 'income' ? 'Receita' : 'Despesa' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ t.category }}</td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate">{{ t.description }}</td>
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ t.plot?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-semibold" :class="t.type === 'income' ? 'text-green-700' : 'text-red-600'">
                                    {{ formatBRL(t.amount) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link
                                        v-if="t.field_log_id"
                                        :href="route('field-logs.edit', t.field_log_id)"
                                        class="text-xs text-blue-600 hover:text-blue-800"
                                        title="Ver registro do caderno"
                                    >
                                        Caderno
                                    </Link>
                                    <span v-else class="text-xs text-gray-400">Manual</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <template v-if="!t.field_log_id">
                                        <Link :href="route('financial-transactions.edit', t.id)" class="mr-3 text-green-700 hover:text-green-900 font-medium">Editar</Link>
                                        <button @click="confirmDelete(t)" class="text-red-500 hover:text-red-700 font-medium">Remover</button>
                                    </template>
                                    <span v-else class="text-xs text-gray-400">Automático</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div v-if="transactions.links" class="mt-4 flex justify-end gap-1">
                    <template v-for="link in transactions.links" :key="link.label">
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
