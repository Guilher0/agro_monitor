<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    asset: Object, // null = criação, objeto = edição
});

const isEditing = !!props.asset;

const form = useForm({
    name:                      props.asset?.name                      ?? '',
    type:                      props.asset?.type                      ?? 'tractor',
    serial_number:             props.asset?.serial_number             ?? '',
    purchase_date:             props.asset?.purchase_date             ?? '',
    hourly_rate:               props.asset?.hourly_rate               ?? '',
    total_hours:               props.asset?.total_hours               ?? '',
    hours_at_last_maintenance: props.asset?.hours_at_last_maintenance ?? '',
    last_maintenance_at:       props.asset?.last_maintenance_at       ?? '',
    maintenance_alert_hours:   props.asset?.maintenance_alert_hours   ?? 250,
    status:                    props.asset?.status                    ?? 'active',
    notes:                     props.asset?.notes                     ?? '',
});

function submit() {
    if (isEditing) {
        form.put(route('assets.update', props.asset.id));
    } else {
        form.post(route('assets.store'));
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Editar Ativo' : 'Novo Ativo'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('assets.index')" class="text-gray-400 hover:text-gray-600">
                    ← Ativos
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ isEditing ? 'Editar Ativo' : 'Novo Ativo' }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">

                    <!-- Nome + Tipo -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Nome *" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="type" value="Tipo *" />
                            <select id="type" v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="tractor">Trator</option>
                                <option value="harvester">Colheitadeira</option>
                                <option value="sprayer">Pulverizador</option>
                                <option value="implement">Implemento</option>
                                <option value="other">Outro</option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-1" />
                        </div>
                    </div>

                    <!-- Série + Data de compra -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="serial_number" value="Número de Série" />
                            <TextInput id="serial_number" v-model="form.serial_number" class="mt-1 block w-full" />
                            <InputError :message="form.errors.serial_number" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="purchase_date" value="Data de Compra" />
                            <TextInput id="purchase_date" type="date" v-model="form.purchase_date" class="mt-1 block w-full" />
                            <InputError :message="form.errors.purchase_date" class="mt-1" />
                        </div>
                    </div>

                    <!-- R$/hora + Total de horas -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="hourly_rate" value="Valor por Hora (R$) *" />
                            <TextInput id="hourly_rate" type="number" step="0.01" min="0" v-model="form.hourly_rate" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.hourly_rate" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="total_hours" value="Total de Horas Trabalhadas" />
                            <TextInput id="total_hours" type="number" step="0.1" min="0" v-model="form.total_hours" class="mt-1 block w-full" />
                            <InputError :message="form.errors.total_hours" class="mt-1" />
                        </div>
                    </div>

                    <!-- Horas na última manutenção + Data -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="hours_at_last_maintenance" value="Horas na Última Manutenção" />
                            <TextInput id="hours_at_last_maintenance" type="number" step="0.1" min="0" v-model="form.hours_at_last_maintenance" class="mt-1 block w-full" />
                            <InputError :message="form.errors.hours_at_last_maintenance" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="last_maintenance_at" value="Data da Última Manutenção" />
                            <TextInput id="last_maintenance_at" type="date" v-model="form.last_maintenance_at" class="mt-1 block w-full" />
                            <InputError :message="form.errors.last_maintenance_at" class="mt-1" />
                        </div>
                    </div>

                    <!-- Alerta de manutenção + Status -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="maintenance_alert_hours" value="Alerta de Manutenção (horas) *" />
                            <TextInput id="maintenance_alert_hours" type="number" min="1" v-model="form.maintenance_alert_hours" class="mt-1 block w-full" required />
                            <p class="mt-1 text-xs text-gray-500">Alerta quando (total − última manutenção) ≥ este valor.</p>
                            <InputError :message="form.errors.maintenance_alert_hours" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="status" value="Status *" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="active">Ativo</option>
                                <option value="maintenance">Em Manutenção</option>
                                <option value="inactive">Inativo</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>
                    </div>

                    <!-- Observações -->
                    <div>
                        <InputLabel for="notes" value="Observações" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                        <Link :href="route('assets.index')" class="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="bg-green-800 hover:bg-green-700">
                            {{ isEditing ? 'Salvar Alterações' : 'Cadastrar Ativo' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
