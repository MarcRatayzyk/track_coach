<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="mx-auto flex min-h-[70vh] w-full max-w-lg flex-col items-center justify-center px-4 py-12 text-center">
        <Head :title="t('auth.subscriptionBlocked.pageTitle')" />

        <div class="rounded-2xl border border-slate-700/80 bg-slate-800/40 p-8">
            <p class="text-sm font-semibold uppercase tracking-wider text-amber-400">{{ t('auth.subscriptionBlocked.eyebrow') }}</p>
            <h1 class="mt-3 text-2xl font-bold text-white">{{ t('auth.subscriptionBlocked.title') }}</h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-400">
                {{ t('auth.subscriptionBlocked.body') }}
            </p>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="mt-6 inline-flex rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800"
            >
                {{ t('auth.subscriptionBlocked.logOut') }}
            </Link>
        </div>
    </div>
</template>
