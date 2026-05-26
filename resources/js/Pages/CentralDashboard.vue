<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    tenantCount: Number,
});
</script>

<template>
    <Head title="Painel Central — AgroMonitor" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-slate-800">
                Painel do Administrador Central
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Banner de Boas-Vindas -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-2xl border border-slate-100 p-8 mb-8 bg-gradient-to-r from-green-800 to-emerald-950 text-white relative">
                    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-white/10 via-transparent to-transparent"></div>
                    
                    <div class="relative z-10 max-w-3xl">
                        <span class="px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/10 text-xs font-semibold tracking-wider uppercase text-emerald-200">
                            Ambiente Global Landlord
                        </span>
                        <h1 class="mt-4 text-4xl font-extrabold tracking-tight">
                            Bem-vindo ao AgroMonitor!
                        </h1>
                        <p class="mt-4 text-lg text-emerald-100/90 leading-relaxed">
                            Você está logado como **Administrador Central da Plataforma**. 
                            A partir deste painel central, você gerencia os planos, configurações e bancos de dados isolados de todos os inquilinos (fazendas).
                        </p>
                    </div>
                </div>

                <!-- Painel Informativo sobre Multi-Tenancy -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Card 1: Quantidade de Tenants -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col justify-between">
                        <div>
                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-wider">Métrica da Plataforma</span>
                            <h3 class="mt-2 text-lg font-bold text-slate-800">Fazendas Ativas</h3>
                            <p class="mt-4 text-5xl font-black text-green-700">
                                {{ tenantCount }}
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                empresas e fazendas ativas utilizando bancos de dados isolados.
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: Instruções de Teste de Funcionalidades Agrícolas -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 md:col-span-2 flex flex-col justify-between">
                        <div>
                            <span class="text-amber-500 text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Instrução de Uso do Sistema Multi-Tenant
                            </span>
                            <h3 class="mt-2 text-lg font-bold text-slate-800">Como testar as telas agrícolas (Talhões, Ativos, etc.)?</h3>
                            <p class="mt-3 text-sm text-slate-600 leading-relaxed">
                                No modelo de arquitetura multi-banco (**multi-tenancy**), as telas operacionais da fazenda (Talhões, Ativos, Caderno de Campo e Financeiro) pertencem e residem **exclusivamente dentro do subdomínio da fazenda correspondente**.
                            </p>
                            <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                                Para testar e interagir com o fluxo completo do produtor rural (incluindo os belos gráficos ApexCharts e relatórios em PDF), você deve acessar a fazenda de demonstração via subdomínio:
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="text-xs text-slate-500">
                                <p><strong>Tenant Demo:</strong> owner@fazenda-demo.test</p>
                                <p><strong>Senha:</strong> password</p>
                            </div>
                            <a
                                href="http://fazenda-demo.localhost:8095/dashboard"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-800 transition-colors"
                            >
                                Entrar na Fazenda Demo
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
