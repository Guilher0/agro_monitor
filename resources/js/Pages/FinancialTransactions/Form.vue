<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    transaction: Object,
    plots:       Array,
});

const isEditing = !!props.transaction;

const form = useForm({
    plot_id:          props.transaction?.plot?.id      ?? '',
    type:             props.transaction?.type          ?? 'expense',
    category:         props.transaction?.category      ?? '',
    amount:           props.transaction?.amount        ?? '',
    description:      props.transaction?.description   ?? '',
    transaction_date: props.transaction?.transaction_date ?? new Date().toISOString().slice(0, 10),
});

const expenseCategories = [
    'Defensivos / Pulverização',
    'Fertilizantes / Adubação',
    'Plantio',
    'Colheita',
    'Manutenção de Máquinas',
    'Irrigação',
    'Mão de Obra',
    'Arrendamento',
    'Combustível',
    'Outros',
];

const incomeCategories = [
    'Venda de Grãos',
    'Venda de Gado',
    'Arrendamento Recebido',
    'Subsídio / Incentivo',
    'Outros',
];

function submit() {
    if (isEditing) {
        form.put(route('financial-transactions.update', props.transaction.id));
    } else {
        form.post(route('financial-transactions.store'));
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Editar Lançamento' : 'Novo Lançamento'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('financial-transactions.index')" class="text-gray-400 hover:text-gray-600">
                    ← Financeiro
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ isEditing ? 'Editar Lançamento' : 'Novo Lançamento Manual' }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">
                    Este formulário é para lançamentos manuais (vendas de safra, arrendamentos, etc.).
                    Despesas operacionais são geradas automaticamente pelo Caderno de Campo.
                </div>

                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">

                    <!-- Tipo + Valor -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="type" value="Tipo *" />
                            <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="income">Receita</option>
                                <option value="expense">Despesa</option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="amount" value="Valor (R$) *" />
                            <TextInput id="amount" type="number" step="0.01" min="0.01" v-model="form.amount" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.amount" class="mt-1" />
                        </div>
                    </div>

                    <!-- Categoria + Data -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="category" value="Categoria *" />
                            <select id="category" v-model="form.category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Selecione a categoria</option>
                                <optgroup label="Receitas">
                                    <option v-for="cat in incomeCategories" :key="cat" :value="cat">{{ cat }}</option>
                                </optgroup>
                                <optgroup label="Despesas">
                                    <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat }}</option>
                                </optgroup>
                            </select>
                            <InputError :message="form.errors.category" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="transaction_date" value="Data *" />
                            <TextInput id="transaction_date" type="date" v-model="form.transaction_date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.transaction_date" class="mt-1" />
                        </div>
                    </div>

                    <!-- Talhão -->
                    <div>
                        <InputLabel for="plot_id" value="Talhão (opcional)" />
                        <select id="plot_id" v-model="form.plot_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="">Geral (sem talhão específico)</option>
                            <option v-for="plot in plots" :key="plot.id" :value="plot.id">{{ plot.name }}</option>
                        </select>
                        <InputError :message="form.errors.plot_id" class="mt-1" />
                    </div>

                    <!-- Descrição -->
                    <div>
                        <InputLabel for="description" value="Descrição *" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                        <Link :href="route('financial-transactions.index')" class="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="bg-green-800 hover:bg-green-700">
                            {{ isEditing ? 'Salvar Alterações' : 'Registrar Lançamento' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
