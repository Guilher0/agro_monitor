<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <!-- Lado Esquerdo: Imagem/Design (Escondido em telas pequenas) -->
        <div class="relative hidden w-1/2 lg:block">
            <img
                src="/images/login_artwork.png"
                alt="AgroMonitor Art"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-tr from-emerald-950/90 via-emerald-900/60 to-transparent"></div>
            
            <div class="absolute bottom-16 left-16 right-16 text-white z-10">
                <div class="flex items-center gap-3 mb-6">
                    <ApplicationLogo class="h-10 w-10 fill-current text-emerald-400" />
                    <span class="text-2xl font-bold tracking-wider uppercase text-emerald-50">AgroMonitor</span>
                </div>
                <h2 class="text-4xl font-extrabold leading-tight mb-4 text-emerald-50">
                    A inteligência operacional e financeira da sua fazenda.
                </h2>
                <p class="text-lg text-emerald-200/90 max-w-md">
                    Monitore talhões, controle a manutenção da sua frota e registre o caderno de campo de forma integrada e simples.
                </p>
            </div>
            
            <!-- Detalhe moderno de vidro (Glassmorphism) -->
            <div class="absolute top-8 left-8 px-4 py-2 rounded-full bg-white/10 backdrop-blur-md border border-white/10 text-xs font-semibold text-emerald-200 tracking-wider uppercase z-10">
                SaaS Agro Monitoramento
            </div>
        </div>

        <!-- Lado Direito: Formulário de Login/Registro -->
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-24">
            <div class="mx-auto w-full max-w-md">
                <!-- Logo no Mobile (Escondido no Desktop) -->
                <div class="flex flex-col items-center mb-8 lg:hidden">
                    <Link href="/">
                        <ApplicationLogo class="h-16 w-16 fill-current text-emerald-600" />
                    </Link>
                    <h1 class="mt-4 text-2xl font-bold text-gray-900">AgroMonitor</h1>
                </div>

                <div class="w-full overflow-hidden bg-white p-8 shadow-xl rounded-2xl border border-gray-100">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
