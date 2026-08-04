<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLogo from '../Components/AppLogo.vue';
import LegalFooterLinks from '../Components/Legal/LegalFooterLinks.vue';
import { useLegalPublisher } from '../composables/useLegalPublisher';

const { t } = useI18n();
const { vars } = useLegalPublisher();
const year = computed(() => new Date().getFullYear());
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-200">
    <Head :title="t('privacy.pageTitle')" />

    <header class="border-b border-slate-800/80">
      <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
        <Link href="/" class="flex items-center gap-2">
          <AppLogo mark-class="h-8 w-8" wordmark-class="text-sm font-bold text-white" />
        </Link>
        <Link href="/" class="text-sm font-medium text-slate-400 transition hover:text-white">
          {{ t('privacy.backHome') }}
        </Link>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
      <h1 class="text-3xl font-bold tracking-tight text-white">{{ t('privacy.title') }}</h1>
      <p class="mt-2 text-sm text-slate-500">{{ t('privacy.lastUpdated', { year }) }}</p>

      <div class="mt-8 space-y-8 text-sm leading-relaxed text-slate-300">
        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section1Title') }}</h2>
          <p class="mt-2">
            {{ t('privacy.section1Body', vars) }}
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section2Title') }}</h2>
          <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>{{ t('privacy.section2Items.account') }}</li>
            <li>{{ t('privacy.section2Items.profile') }}</li>
            <li>{{ t('privacy.section2Items.training') }}</li>
            <li>{{ t('privacy.section2Items.content') }}</li>
            <li>{{ t('privacy.section2Items.technical') }}</li>
            <li>{{ t('privacy.section2Items.usage') }}</li>
          </ul>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section3Title') }}</h2>
          <p class="mt-2">
            {{ t('privacy.section3Body') }}
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section4Title') }}</h2>
          <p class="mt-2">
            {{ t('privacy.section4Body1') }}
          </p>
          <p class="mt-2">
            {{ t('privacy.section4Body2') }}
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section5Title') }}</h2>
          <p class="mt-2">
            {{ t('privacy.section5Body') }}
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section6Title') }}</h2>
          <p class="mt-2">
            {{ t('privacy.section6BodyBefore') }}
            <Link href="/account/privacy" class="text-blue-400 hover:underline">{{ t('privacy.section6Link') }}</Link>{{ t('privacy.section6BodyAfter') }}
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">{{ t('privacy.section7Title') }}</h2>
          <p class="mt-2">
            {{ t('privacy.section7Body') }}
          </p>
        </section>
      </div>
    </main>

    <footer class="border-t border-slate-800/80">
      <div class="mx-auto flex max-w-4xl flex-col gap-4 px-4 py-6 text-sm text-slate-500 sm:px-6">
        <LegalFooterLinks />
        <p>{{ t('privacy.footer') }}</p>
      </div>
    </footer>
  </div>
</template>
