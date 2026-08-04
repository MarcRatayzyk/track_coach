<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LegalPageLayout from '../Components/Legal/LegalPageLayout.vue';
import { useLegalPublisher } from '../composables/useLegalPublisher';

const { t } = useI18n();
const { vars } = useLegalPublisher();

const sections = computed(() => [
    { title: t('termsOfSale.sections.publisher.title'), body: t('termsOfSale.sections.publisher.body', vars.value) },
    { title: t('termsOfSale.sections.object.title'), body: t('termsOfSale.sections.object.body') },
    { title: t('termsOfSale.sections.plans.title'), body: t('termsOfSale.sections.plans.body'), list: true },
    { title: t('termsOfSale.sections.trial.title'), body: t('termsOfSale.sections.trial.body') },
    { title: t('termsOfSale.sections.order.title'), body: t('termsOfSale.sections.order.body') },
    { title: t('termsOfSale.sections.payment.title'), body: t('termsOfSale.sections.payment.body') },
    { title: t('termsOfSale.sections.renewal.title'), body: t('termsOfSale.sections.renewal.body') },
    { title: t('termsOfSale.sections.withdrawal.title'), body: t('termsOfSale.sections.withdrawal.body') },
    { title: t('termsOfSale.sections.cancellation.title'), body: t('termsOfSale.sections.cancellation.body') },
    { title: t('termsOfSale.sections.liability.title'), body: t('termsOfSale.sections.liability.body') },
    { title: t('termsOfSale.sections.law.title'), body: t('termsOfSale.sections.law.body') },
    { title: t('termsOfSale.sections.contact.title'), body: t('termsOfSale.sections.contact.body', vars.value) },
]);

const planItems = computed(() => [
    t('termsOfSale.sections.plans.items.starter'),
    t('termsOfSale.sections.plans.items.growth'),
    t('termsOfSale.sections.plans.items.scale'),
]);
</script>

<template>
    <LegalPageLayout :page-title="t('termsOfSale.pageTitle')" :title="t('termsOfSale.title')">
        <p class="-mt-4 text-slate-400">
            {{ t('termsOfSale.intro') }}
            <Link href="/cgu" class="text-blue-400 hover:underline">{{ t('legalLinks.terms') }}</Link>.
        </p>

        <section v-for="section in sections" :key="section.title">
            <h2 class="text-lg font-semibold text-white">{{ section.title }}</h2>
            <p v-if="!section.list" class="mt-2 whitespace-pre-line">{{ section.body }}</p>
            <template v-else>
                <p class="mt-2">{{ section.body }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li v-for="item in planItems" :key="item">{{ item }}</li>
                </ul>
            </template>
        </section>
    </LegalPageLayout>
</template>
