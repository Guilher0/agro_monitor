<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const flash = computed(() => usePage().props.flash ?? {});
</script>

<template>
    <div v-if="flash.success || flash.info || flash.error" class="mb-4">
        <div
            v-if="flash.success"
            class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800"
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash.info"
            class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800"
        >
            {{ flash.info }}
        </div>
        <div
            v-if="flash.error"
            class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800"
        >
            {{ flash.error }}
        </div>
    </div>
</template>
