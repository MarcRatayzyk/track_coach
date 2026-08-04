<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import LegalPageLayout from '../Components/Legal/LegalPageLayout.vue';
import { useLegalPublisher } from '../composables/useLegalPublisher';

const { t } = useI18n();
const { vars } = useLegalPublisher();

const sections = computed(() => [
    { title: t('legalNotice.sections.publisher.title'), body: t('legalNotice.sections.publisher.body', vars.value) },
    { title: t('legalNotice.sections.director.title'), body: t('legalNotice.sections.director.body', vars.value) },
    { title: t('legalNotice.sections.hosting.title'), body: t('legalNotice.sections.hosting.body', vars.value) },
    { title: t('legalNotice.sections.intellectual.title'), body: t('legalNotice.sections.intellectual.body') },
    { title: t('legalNotice.sections.liability.title'), body: t('legalNotice.sections.liability.body') },
    { title: t('legalNotice.sections.contact.title'), body: t('legalNotice.sections.contact.body', vars.value) },
]);
</script>

<template>
    <LegalPageLayout :page-title="t('legalNotice.pageTitle')" :title="t('legalNotice.title')">
        <section v-for="section in sections" :key="section.title">
            <h2 class="text-lg font-semibold text-white">{{ section.title }}</h2>
            <p class="mt-2 whitespace-pre-line">{{ section.body }}</p>
        </section>
    </LegalPageLayout>
</template>
