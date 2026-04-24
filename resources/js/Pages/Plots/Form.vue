<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    plot: Object,
});

const isEditing = !!props.plot;

const form = useForm({
    name:          props.plot?.name          ?? '',
    area_hectares: props.plot?.area_hectares ?? '',
    culture:       props.plot?.culture       ?? '',
    season:        props.plot?.season        ?? '',
    soil_type:     props.plot?.soil_type     ?? '',
    status:        props.plot?.status        ?? 'active',
    notes:         props.plot?.notes         ?? '',
});

function submit() {
    if (isEditing) {
        form.put(route('plots.update', props.plot.id));
    } else {
        form.post(route('plots.store'));
    }
}
</script>

<template>
    <Head :title="isEditing ? 'Editar Talhão' : 'Novo Talhão'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('plots.index')" class="text-gray-400 hover:text-gray-600">
                    ← Talhões
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ isEditing ? 'Editar Talhão' : 'Novo Talhão' }}
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">

                    <!-- Nome + Área -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="name" value="Nome do Talhão *" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required autofocus />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="area_hectares" value="Área (hectares) *" />
                            <TextInput id="area_hectares" type="number" step="0.01" min="0.01" v-model="form.area_hectares" class="mt-1 block w-full" required />
                            <InputError :message="form.errors.area_hectares" class="mt-1" />
                        </div>
                    </div>

                    <!-- Cultura + Safra -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="culture" value="Cultura" />
                            <TextInput id="culture" v-model="form.culture" placeholder="Ex: Soja, Milho, Feijão" class="mt-1 block w-full" />
                            <InputError :message="form.errors.culture" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="season" value="Safra" />
                            <TextInput id="season" v-model="form.season" placeholder="Ex: 2024/2025" class="mt-1 block w-full" />
                            <InputError :message="form.errors.season" class="mt-1" />
                        </div>
                    </div>

                    <!-- Solo + Status -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel for="soil_type" value="Tipo de Solo" />
                            <TextInput id="soil_type" v-model="form.soil_type" placeholder="Ex: Latossolo Vermelho" class="mt-1 block w-full" />
                            <InputError :message="form.errors.soil_type" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="status" value="Status *" />
                            <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="active">Ativo</option>
                                <option value="fallow">Pousio</option>
                                <option value="harvested">Colhido</option>
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
                        <Link :href="route('plots.index')" class="text-sm text-gray-600 hover:text-gray-800">
                            Cancelar
                        </Link>
                        <PrimaryButton :disabled="form.processing" class="bg-green-800 hover:bg-green-700">
                            {{ isEditing ? 'Salvar Alterações' : 'Cadastrar Talhão' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
