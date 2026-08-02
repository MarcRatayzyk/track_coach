<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { motion } from 'motion-v';
import AppLogo from '../AppLogo.vue';
import LanguageSwitcher from '../LanguageSwitcher.vue';
import { track } from '../../utils/analytics';

const { t } = useI18n();

function trackCta(ctaId) {
    track('cta_clicked', { cta_id: ctaId });
}

const links = computed(() => [
    { href: '#fonctionnalites', label: t('landing.nav.features') },
    { href: '#suivi', label: t('landing.nav.tracking') },
    { href: '#modularite', label: t('landing.nav.modularity') },
    { href: '#demo', label: t('landing.nav.demo') },
    { href: '#pricing', label: t('landing.nav.pricing') },
    { href: '#faq', label: t('landing.nav.faq') },
]);
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
            <a href="/" class="flex min-w-0 items-center gap-2.5" :aria-label="t('landing.nav.homeAria')">
                <AppLogo
                    mark-class="h-14 w-14 sm:h-16 sm:w-16"
                    wordmark-class="truncate text-lg font-bold tracking-tight text-white sm:text-2xl"
                />
            </a>

            <nav class="hidden items-center gap-1 lg:flex" :aria-label="t('landing.nav.mainNavAria')">
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
                <LanguageSwitcher variant="landing" />
                <a
                    href="/login"
                    class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-300 transition hover:text-white sm:px-4 sm:text-sm"
                    @click="trackCta('nav_login')"
                >
                    {{ t('landing.nav.logIn') }}
                </a>
                <a
                    href="#pricing"
                    class="lp-btn-primary px-3.5 py-2 text-xs leading-none sm:px-5 sm:py-2.5 sm:text-sm"
                    @click="trackCta('nav_commencer')"
                >
                    {{ t('landing.nav.start') }}
                </a>
            </div>
        </div>
    </motion.header>
</template>
