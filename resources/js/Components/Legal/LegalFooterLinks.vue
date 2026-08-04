<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const links = [
    { href: '/mentions-legales', labelKey: 'legalLinks.notice' },
    { href: '/cgu', labelKey: 'legalLinks.terms' },
    { href: '/cgv', labelKey: 'legalLinks.sales' },
    { href: '/confidentialite', labelKey: 'legalLinks.privacy' },
];
</script>

<template>
    <nav class="flex flex-wrap gap-x-4 gap-y-1" :aria-label="t('legalLinks.aria')">
        <Link
            v-for="link in links"
            :key="link.href"
            :href="link.href"
            class="text-slate-400 transition hover:text-white"
        >
            {{ t(link.labelKey) }}
        </Link>
    </nav>
</template>
