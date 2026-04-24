<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const isOpen  = ref(false);
const query   = ref('');
const results = ref([]);
const loading = ref(false);
const selectedIndex = ref(-1);
const inputRef = ref(null);

// ─── Abrir/fechar ────────────────────────────────────────────────────────
const open = () => {
    isOpen.value = true;
    nextTick(() => inputRef.value?.focus());
};
const close = () => {
    isOpen.value = false;
    query.value  = '';
    results.value = [];
    selectedIndex.value = -1;
};

// ─── Atalho de teclado: Ctrl+K ───────────────────────────────────────────
const onKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        isOpen.value ? close() : open();
    }
    if (e.key === 'Escape') close();
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

// ─── Busca com debounce ──────────────────────────────────────────────────
let debounceTimer = null;
watch(query, (val) => {
    clearTimeout(debounceTimer);
    selectedIndex.value = -1;
    if (val.trim().length < 2) { results.value = []; loading.value = false; return; }
    loading.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const { data } = await axios.get(route('search'), { params: { q: val } });
            results.value = data.results.filter(g => g.items.length > 0);
        } catch {
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 300);
});

// ─── Navegação por teclado ───────────────────────────────────────────────
const flatItems = () => results.value.flatMap(g => g.items);

const onSpotlightKeydown = (e) => {
    const items = flatItems();
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex.value = Math.min(selectedIndex.value + 1, items.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex.value = Math.max(selectedIndex.value - 1, -1);
    } else if (e.key === 'Enter' && selectedIndex.value >= 0) {
        e.preventDefault();
        window.location.href = items[selectedIndex.value].url;
    }
};

// ─── Ícones por tipo ──────────────────────────────────────────────────────
const typeIcon = (type) => ({
    asset:     '🚜',
    plot:      '🌾',
    field_log: '📋',
}[type] ?? '🔍');

// Índice global acumulado para highlight de teclado
let globalOffset = 0;
const isHighlighted = (groupIdx, itemIdx) => {
    let offset = 0;
    for (let i = 0; i < groupIdx; i++) offset += results.value[i]?.items.length ?? 0;
    return offset + itemIdx === selectedIndex.value;
};
</script>

<template>
    <!-- Botão trigger na navbar -->
    <button
        type="button"
        @click="open"
        class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-500 shadow-sm hover:border-slate-300 hover:text-slate-700 transition"
        title="Busca global (Ctrl+K)"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
        <span class="hidden sm:inline">Buscar</span>
        <kbd class="hidden sm:inline-flex items-center gap-0.5 rounded border border-slate-200 bg-slate-100 px-1.5 text-xs text-slate-400 font-mono">
            Ctrl K
        </kbd>
    </button>

    <!-- Overlay + Modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
            >
                <!-- Fundo escurecido -->
                <div
                    class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                    @click="close"
                />

                <!-- Painel de busca -->
                <div class="relative w-full max-w-xl rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 overflow-hidden">

                    <!-- Input -->
                    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                        </svg>
                        <input
                            ref="inputRef"
                            v-model="query"
                            @keydown="onSpotlightKeydown"
                            type="text"
                            placeholder="Buscar ativos, talhões, registros..."
                            class="flex-1 bg-transparent text-sm text-slate-900 placeholder-slate-400 outline-none"
                        />
                        <span v-if="loading" class="text-xs text-slate-400 animate-pulse">buscando...</span>
                        <kbd
                            class="hidden sm:inline-flex items-center rounded border border-slate-200 bg-slate-100 px-1.5 text-xs text-slate-400 font-mono cursor-pointer"
                            @click="close"
                        >Esc</kbd>
                    </div>

                    <!-- Resultados -->
                    <div class="max-h-96 overflow-y-auto py-2">

                        <!-- Estado inicial -->
                        <div v-if="query.trim().length < 2 && !loading"
                             class="px-4 py-8 text-center text-sm text-slate-400">
                            Digite pelo menos 2 caracteres para buscar.
                        </div>

                        <!-- Sem resultados -->
                        <div v-else-if="!loading && query.trim().length >= 2 && results.length === 0"
                             class="px-4 py-8 text-center text-sm text-slate-400">
                            Nenhum resultado para "<strong class="text-slate-600">{{ query }}</strong>".
                        </div>

                        <!-- Grupos de resultados -->
                        <template v-for="(group, gIdx) in results" :key="group.group">
                            <div class="px-4 py-1.5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    {{ group.group }}
                                </p>
                            </div>
                            <a
                                v-for="(item, iIdx) in group.items"
                                :key="item.id"
                                :href="item.url"
                                class="flex items-center gap-3 px-4 py-2.5 transition"
                                :class="isHighlighted(gIdx, iIdx)
                                    ? 'bg-green-50 text-green-900'
                                    : 'text-slate-700 hover:bg-slate-50'"
                            >
                                <span class="text-lg leading-none">{{ typeIcon(item.type) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ item.title }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ item.subtitle }}</p>
                                </div>
                                <svg class="h-4 w-4 flex-shrink-0 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <div v-if="gIdx < results.length - 1" class="my-1 border-t border-slate-50" />
                        </template>
                    </div>

                    <!-- Footer com dicas -->
                    <div class="border-t border-slate-100 px-4 py-2 text-xs text-slate-400 flex items-center gap-4">
                        <span><kbd class="font-mono">↑↓</kbd> navegar</span>
                        <span><kbd class="font-mono">Enter</kbd> abrir</span>
                        <span><kbd class="font-mono">Esc</kbd> fechar</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
