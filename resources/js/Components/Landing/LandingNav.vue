<script setup>
import { motion } from 'motion-v';
import AppLogo from '../AppLogo.vue';
import { track } from '../../utils/analytics';

function trackCta(ctaId) {
    track('cta_clicked', { cta_id: ctaId });
}

const links = [
    { href: '#fonctionnalites', label: 'Fonctionnalités' },
    { href: '#demo', label: 'Démo' },
    { href: '#pricing', label: 'Tarifs' },
    { href: '#faq', label: 'FAQ' },
];
</script>

<template>
    <motion.header
        :initial="{ opacity: 0, y: -12 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ duration: 0.55, ease: [0.22, 1, 0.36, 1] }"
        class="fixed inset-x-0 top-0 z-50 border-b border-white/[0.06] bg-[#050B1E]/70 backdrop-blur-2xl"
    >
        <div
            class="mx-auto flex w-full max-w-[1280px] items-center justify-between gap-4 px-5 py-3.5 sm:px-8 lg:px-10"
        >
            <a href="/" class="flex min-w-0 items-center gap-2.5" aria-label="Power Roster — Accueil">
                <AppLogo
                    mark-class="h-9 w-9 sm:h-10 sm:w-10"
                    wordmark-class="truncate text-[15px] font-bold tracking-tight text-white sm:text-lg"
                />
            </a>

            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                <a
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-lg px-3.5 py-2 text-sm font-medium text-slate-400 transition hover:bg-white/[0.04] hover:text-white"
                >
                    {{ link.label }}
                </a>
            </nav>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <a
                    href="/login"
                    class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-300 transition hover:text-white sm:px-4 sm:text-sm"
                    @click="trackCta('nav_login')"
                >
                    Se connecter
                </a>
                <a
                    href="/register"
                    class="lp-btn-primary px-3.5 py-2 text-xs sm:px-5 sm:py-2.5 sm:text-sm"
                    @click="trackCta('nav_register')"
                >
                    Créer un compte
                </a>
            </div>
        </div>
    </motion.header>
</template>
