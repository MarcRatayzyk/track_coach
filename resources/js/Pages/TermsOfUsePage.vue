<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LegalPageLayout from '../Components/Legal/LegalPageLayout.vue';
import { useLegalPublisher } from '../composables/useLegalPublisher';

const { t } = useI18n();
const { vars } = useLegalPublisher();

const sections = computed(() => [
    { title: t('termsOfUse.sections.object.title'), body: t('termsOfUse.sections.object.body') },
    { title: t('termsOfUse.sections.access.title'), body: t('termsOfUse.sections.access.body') },
    { title: t('termsOfUse.sections.accounts.title'), body: t('termsOfUse.sections.accounts.body') },
    { title: t('termsOfUse.sections.roles.title'), body: t('termsOfUse.sections.roles.body') },
    { title: t('termsOfUse.sections.content.title'), body: t('termsOfUse.sections.content.body') },
    { title: t('termsOfUse.sections.prohibited.title'), body: t('termsOfUse.sections.prohibited.body'), list: true },
    { title: t('termsOfUse.sections.availability.title'), body: t('termsOfUse.sections.availability.body') },
    { title: t('termsOfUse.sections.liability.title'), body: t('termsOfUse.sections.liability.body') },
    { title: t('termsOfUse.sections.termination.title'), body: t('termsOfUse.sections.termination.body') },
    { title: t('termsOfUse.sections.law.title'), body: t('termsOfUse.sections.law.body') },
    { title: t('termsOfUse.sections.contact.title'), body: t('termsOfUse.sections.contact.body', vars.value) },
]);

const prohibitedItems = computed(() => [
    t('termsOfUse.sections.prohibited.items.illegal'),
    t('termsOfUse.sections.prohibited.items.abuse'),
    t('termsOfUse.sections.prohibited.items.reverse'),
    t('termsOfUse.sections.prohibited.items.scraping'),
]);
</script>

<template>
    <LegalPageLayout :page-title="t('termsOfUse.pageTitle')" :title="t('termsOfUse.title')">
        <p class="-mt-4 text-slate-400">
            {{ t('termsOfUse.intro') }}
            <Link href="/cgv" class="text-blue-400 hover:underline">{{ t('legalLinks.sales') }}</Link>
            {{ t('termsOfUse.introAfterSales') }}
            <Link href="/confidentialite" class="text-blue-400 hover:underline">{{ t('legalLinks.privacy') }}</Link>.
        </p>

        <section v-for="section in sections" :key="section.title">
            <h2 class="text-lg font-semibold text-white">{{ section.title }}</h2>
            <p v-if="!section.list" class="mt-2 whitespace-pre-line">{{ section.body }}</p>
            <template v-else>
                <p class="mt-2">{{ section.body }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li v-for="item in prohibitedItems" :key="item">{{ item }}</li>
                </ul>
            </template>
        </section>
    </LegalPageLayout>
</template>
