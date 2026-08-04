<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLogo from '../AppLogo.vue';
import LegalFooterLinks from './LegalFooterLinks.vue';

const props = defineProps({
    pageTitle: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
});

const { t } = useI18n();
const year = computed(() => new Date().getFullYear());
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-200">
        <Head :title="pageTitle" />

        <header class="border-b border-slate-800/80">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
                <Link href="/" class="flex items-center gap-2">
                    <AppLogo mark-class="h-8 w-8" wordmark-class="text-sm font-bold text-white" />
                </Link>
                <Link href="/" class="text-sm font-medium text-slate-400 transition hover:text-white">
                    {{ t('legalCommon.backHome') }}
                </Link>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <h1 class="text-3xl font-bold tracking-tight text-white">{{ title }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ t('legalCommon.lastUpdated', { year }) }}</p>

            <div class="mt-8 space-y-8 text-sm leading-relaxed text-slate-300">
                <slot />
            </div>
        </main>

        <footer class="border-t border-slate-800/80">
            <div class="mx-auto flex max-w-4xl flex-col gap-4 px-4 py-6 text-sm text-slate-500 sm:px-6">
                <LegalFooterLinks />
                <p>{{ t('legalCommon.footer') }}</p>
            </div>
        </footer>
    </div>
</template>
