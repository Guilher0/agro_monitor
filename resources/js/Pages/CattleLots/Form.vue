<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    lot: Object,
});

const isEditing = !!props.lot;

const form = useForm({
    name: props.lot?.name ?? '',
    animal_count: props.lot?.animal_count ?? '',
    initial_avg_weight_kg: props.lot?.initial_avg_weight_kg ?? '',
    total_purchase_cost: props.lot?.total_purchase_cost ?? '',
    status: props.lot?.status ?? 'active',
    uf: props.lot?.uf ?? 'TO',
});

function submit() {
    if (isEditing) {
        form.put(route('cattle-lots.update', props.lot.id));
    } else {
        form.post(route('cattle-lots.store'));
    }
}

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
</script>

<template>
    <Head :title="isEditing ? 'Editar Lote de Gado' : 'Novo Lote de Gado'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('cattle-lots.index')" class="text-slate-400 hover:text-slate-600 font-semibold transition">
                    ← Pecuária
                </Link>
                <span class="text-slate-300">/</span>
                <h2 class="text-xl font-bold text-slate-800 leading-tight">
                    {{ isEditing ? 'Editar Lote' : 'Novo Lote de Gado' }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
                    
                    <div class="border-b border-slate-100 pb-4">
                        <h3 class="text-base font-bold text-slate-800">Dados do Lote</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Preencha as informações básicas para iniciar o controle do rebanho</p>
                    </div>

                    <!-- Nome do Lote -->
                    <div>
                        <InputLabel for="name" value="Identificação / Nome do Lote *" />
                        <TextInput 
                            id="name" 
                            v-model="form.name" 
                            placeholder="Ex: Lote 22 - Recria Pasto Alto"
                            class="mt-1 block w-full" 
                            required 
                            autofocus 
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <!-- Quantidade + Peso de Entrada -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="animal_count" value="Quantidade de Cabeças *" />
                            <TextInput 
                                id="animal_count" 
                                type="number" 
                                min="1"
                                v-model.number="form.animal_count" 
                                placeholder="Ex: 50"
                                class="mt-1 block w-full" 
                                required 
                            />
                            <InputError :message="form.errors.animal_count" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="initial_avg_weight_kg" value="Peso Médio de Entrada (kg) *" />
                            <TextInput 
                                id="initial_avg_weight_kg" 
                                type="number" 
                                step="0.01" 
                                min="1"
                                v-model.number="form.initial_avg_weight_kg" 
                                placeholder="Ex: 360"
                                class="mt-1 block w-full" 
                                required 
                            />
                            <InputError :message="form.errors.initial_avg_weight_kg" class="mt-1" />
                        </div>
                    </div>

                    <!-- Custo Total de Compra + UF Preferencial -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="total_purchase_cost" value="Custo Total de Aquisição (R$) *" />
                            <TextInput 
                                id="total_purchase_cost" 
                                type="number" 
                                step="0.01" 
                                min="0"
                                v-model.number="form.total_purchase_cost" 
                                placeholder="Ex: 110000"
                                class="mt-1 block w-full" 
                                required 
                            />
                            <InputError :message="form.errors.total_purchase_cost" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="uf" value="Estado de Cotação (UF) *" />
                            <select 
                                id="uf" 
                                v-model="form.uf" 
                                class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                            >
                                <option v-for="ufOption in allowedUfs" :key="ufOption.value" :value="ufOption.value">
                                    {{ ufOption.name }} ({{ ufOption.value }})
                                </option>
                            </select>
                            <InputError :message="form.errors.uf" class="mt-1" />
                        </div>
                    </div>

                    <!-- Status (Visível apenas se editando) -->
                    <div v-if="isEditing">
                        <InputLabel for="status" value="Status do Lote *" />
                        <select 
                            id="status" 
                            v-model="form.status" 
                            class="mt-1 block w-full rounded-lg border-slate-200 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                        >
                            <option value="active">Ativo (No Pasto)</option>
                            <option value="sold">Vendido (Encerrado)</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-1" />
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                        <Link :href="route('cattle-lots.index')" class="text-sm font-semibold text-slate-600 hover:text-slate-800 transition">
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="bg-green-800 hover:bg-green-700 rounded-lg">
                            {{ isEditing ? 'Salvar Alterações' : 'Cadastrar Lote' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
