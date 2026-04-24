<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    fieldLog: Object,
    plots:    Array,
    assets:   Array,
});

const isEditing = !!props.fieldLog;

const form = useForm({
    plot_id:               props.fieldLog?.plot_id               ?? '',
    asset_id:              props.fieldLog?.asset_id              ?? '',
    activity_type:         props.fieldLog?.activity_type         ?? 'planting',
    description:           props.fieldLog?.description           ?? '',
    log_date:              props.fieldLog?.log_date              ?? new Date().toISOString().slice(0, 10),
    machine_hours:         props.fieldLog?.machine_hours         ?? '',
    input_name:            props.fieldLog?.input_name            ?? '',
    input_quantity:        props.fieldLog?.input_quantity        ?? '',
    input_unit_price:      props.fieldLog?.input_unit_price      ?? '',
    generates_transaction: props.fieldLog?.generates_transaction ?? true,
});

// Calcula custo estimado em tempo real para o usuário ver antes de salvar
const selectedAsset = computed(() =>
    props.assets.find(a => a.id == form.asset_id) ?? null
);

const estimatedCost = computed(() => {
    const machineCost = (Number(form.machine_hours) || 0) * (Number(selectedAsset.value?.hourly_rate) || 0);
    const inputCost   = (Number(form.input_quantity) || 0) * (Number(form.input_unit_price) || 0);
    return machineCost + inputCost;
});

// Quando troca de ativo, limpa as horas se não há máquina
watch(() => form.asset_id, (val) => {
    if (!val) form.machine_hours = '';
});

function submit() {
    if (isEditing) {
        form.put(route('field-logs.update', props.fieldLog.id));
    } else {
        form.post(route('field-logs.store'));
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Editar Registro' : 'Novo Registro'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('field-logs.index')" class="text-gray-400 hover:text-gray-600">
                    ← Caderno de Campo
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ isEditing ? 'Editar Registro' : 'Novo Registro' }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">

                    <!-- Talhão + Data -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="plot_id" value="Talhão *" />
                            <select id="plot_id" v-model="form.plot_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Selecione o talhão</option>
                                <option v-for="plot in plots" :key="plot.id" :value="plot.id">
                                    {{ plot.name }}{{ plot.culture ? ` — ${plot.culture}` : '' }}
                                </option>
                            </select>
                            <InputError :message="form.errors.plot_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="log_date" value="Data da Atividade *" />
                            <TextInput id="log_date" type="date" v-model="form.log_date" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.log_date" class="mt-1" />
                        </div>
                    </div>

                    <!-- Tipo de atividade -->
                    <div>
                        <InputLabel for="activity_type" value="Tipo de Atividade *" />
                        <select id="activity_type" v-model="form.activity_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="planting">Plantio</option>
                            <option value="spraying">Pulverização</option>
                            <option value="harvesting">Colheita</option>
                            <option value="fertilizing">Adubação / Fertilização</option>
                            <option value="maintenance">Manutenção</option>
                            <option value="irrigation">Irrigação</option>
                            <option value="other">Outro</option>
                        </select>
                        <InputError :message="form.errors.activity_type" class="mt-1" />
                    </div>

                    <!-- Descrição -->
                    <div>
                        <InputLabel for="description" value="Descrição *" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                            placeholder="Descreva a atividade realizada..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Máquina + Horas -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="asset_id" value="Ativo / Máquina" />
                            <select id="asset_id" v-model="form.asset_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Sem máquina (atividade manual)</option>
                                <option v-for="asset in assets" :key="asset.id" :value="asset.id">
                                    {{ asset.name }} — R$ {{ Number(asset.hourly_rate).toLocaleString('pt-BR') }}/h
                                </option>
                            </select>
                            <InputError :message="form.errors.asset_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="machine_hours" value="Horas de Máquina" />
                            <TextInput
                                id="machine_hours"
                                type="number"
                                step="0.1"
                                min="0"
                                v-model="form.machine_hours"
                                :disabled="!form.asset_id"
                                class="mt-1 block w-full disabled:bg-gray-50 disabled:text-gray-400"
                            />
                            <InputError :message="form.errors.machine_hours" class="mt-1" />
                        </div>
                    </div>

                    <!-- Insumo -->
                    <fieldset class="rounded-md border border-gray-200 p-4">
                        <legend class="px-2 text-sm font-medium text-gray-600">Insumo (opcional)</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-3">
                                <InputLabel for="input_name" value="Nome do Insumo" />
                                <TextInput id="input_name" v-model="form.input_name" placeholder="Ex: Glifosato, Ureia, Soja" class="mt-1 block w-full" />
                                <InputError :message="form.errors.input_name" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="input_quantity" value="Quantidade" />
                                <TextInput id="input_quantity" type="number" step="0.001" min="0" v-model="form.input_quantity" class="mt-1 block w-full" />
                                <InputError :message="form.errors.input_quantity" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="input_unit_price" value="Preço Unitário (R$)" />
                                <TextInput id="input_unit_price" type="number" step="0.01" min="0" v-model="form.input_unit_price" class="mt-1 block w-full" />
                                <InputError :message="form.errors.input_unit_price" class="mt-1" />
                            </div>
                            <div class="flex items-end">
                                <p class="text-sm text-gray-500">
                                    Subtotal insumo:<br>
                                    <span class="font-medium text-gray-800">
                                        {{ ((Number(form.input_quantity) || 0) * (Number(form.input_unit_price) || 0)).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Preview de custo + Gera transação -->
                    <div class="flex items-center justify-between rounded-md bg-green-50 border border-green-200 px-4 py-3">
                        <div>
                            <p class="text-xs text-green-700 uppercase font-semibold tracking-wide">Custo Total Estimado</p>
                            <p class="text-2xl font-bold text-green-900">
                                {{ estimatedCost.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }}
                            </p>
                            <p class="text-xs text-green-600">Máquina + Insumo. Valor definitivo calculado pelo servidor.</p>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" v-model="form.generates_transaction" class="rounded border-gray-300 text-green-700 focus:ring-green-500" />
                            <span class="text-sm text-green-800 font-medium">Registrar como despesa financeira</span>
                        </label>
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                        <Link :href="route('field-logs.index')" class="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="bg-green-800 hover:bg-green-700">
                            {{ isEditing ? 'Salvar Alterações' : 'Registrar Atividade' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
