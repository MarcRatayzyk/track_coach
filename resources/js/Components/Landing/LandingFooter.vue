<script setup>
import AppLogo from '../AppLogo.vue';

const nav = [
    { href: '#fonctionnalites', label: 'Fonctionnalités' },
    { href: '#demo', label: 'Démo' },
    { href: '#pricing', label: 'Tarifs' },
    { href: '/demo', label: 'Démo' },
    { href: '/register', label: 'Inscription' },
    { href: '/login', label: 'Connexion' },
    { href: '/confidentialite', label: 'Confidentialité' },
];
</script>

<template>
    <footer class="relative z-10 border-t border-white/[0.06] px-5 py-12 sm:px-8 lg:px-10">
        <div class="mx-auto flex w-full max-w-[1280px] flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <AppLogo
                    mark-class="h-9 w-9"
                    wordmark-class="text-base font-bold tracking-tight text-white"
                />
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-slate-500">
                    La plateforme de coaching pour les coachs de powerlifting.
                </p>
            </div>

            <nav class="flex flex-wrap gap-x-6 gap-y-2" aria-label="Pied de page">
                <a
                    v-for="link in nav"
                    :key="link.href + link.label"
                    :href="link.href"
                    class="text-sm text-slate-400 transition hover:text-white"
                >
                    {{ link.label }}
                </a>
            </nav>

            <div class="flex items-center gap-3">
                <a
                    href="mailto:contact@powerroster.fr"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-slate-400 transition hover:border-blue-400/30 hover:text-blue-300"
                    aria-label="Email"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </a>
            </div>
        </div>

        <div class="mx-auto mt-10 flex w-full max-w-[1280px] flex-col gap-2 border-t border-white/[0.05] pt-6 text-xs text-slate-600 sm:flex-row sm:justify-between">
            <p>© {{ new Date().getFullYear() }} Power Roster. Tous droits réservés.</p>
            <p>Mentions légales · Coaching powerlifting</p>
        </div>
    </footer>
</template>
