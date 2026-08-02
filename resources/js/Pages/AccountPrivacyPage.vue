<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { resetAnalytics, track } from '../utils/analytics';

const { t } = useI18n();
const showDeleteConfirm = ref(false);

const deleteForm = useForm({
  password: '',
});

function submitDelete() {
  track('account_deleted');
  resetAnalytics();
  deleteForm.delete('/account', {
    preserveScroll: true,
    onError: () => {
      showDeleteConfirm.value = true;
    },
  });
}
</script>

<template>
  <div class="mx-auto w-full max-w-3xl px-4 py-8 sm:px-6">
    <Head :title="t('app.accountPrivacy.title')" />

    <header class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight text-white">{{ t('app.accountPrivacy.title') }}</h1>
      <p class="mt-2 text-sm text-slate-400">
        {{ t('app.accountPrivacy.subtitle') }}
      </p>
    </header>

    <section class="mb-6 rounded-2xl border border-slate-700/80 bg-slate-800/40 p-6">
      <h2 class="text-lg font-semibold text-white">{{ t('app.accountPrivacy.exportTitle') }}</h2>
      <p class="mt-2 text-sm text-slate-400">
        {{ t('app.accountPrivacy.exportBody') }}
      </p>
      <a
        href="/account/data-export"
        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
      >
        {{ t('app.accountPrivacy.downloadJson') }}
      </a>
    </section>

    <section class="rounded-2xl border border-red-500/40 bg-red-950/20 p-6">
      <h2 class="text-lg font-semibold text-red-300">{{ t('app.accountPrivacy.deleteTitle') }}</h2>
      <p class="mt-2 text-sm text-slate-400">
        {{ t('app.accountPrivacy.deleteBody') }}
      </p>

      <button
        v-if="!showDeleteConfirm"
        type="button"
        class="mt-4 inline-flex items-center gap-2 rounded-xl border border-red-500/60 px-4 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-500/10"
        @click="showDeleteConfirm = true"
      >
        {{ t('app.accountPrivacy.deleteAccount') }}
      </button>

      <form v-else class="mt-4 space-y-4" @submit.prevent="submitDelete">
        <div>
          <label for="delete-password" class="block text-sm font-medium text-slate-300">
            {{ t('app.accountPrivacy.confirmPassword') }}
          </label>
          <input
            id="delete-password"
            v-model="deleteForm.password"
            type="password"
            autocomplete="current-password"
            class="mt-1 block w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-sm text-white focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
          />
          <p v-if="deleteForm.errors.password" class="mt-1 text-sm text-red-400">
            {{ deleteForm.errors.password }}
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="submit"
            :disabled="deleteForm.processing"
            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-60"
          >
            {{ deleteForm.processing ? t('app.accountPrivacy.deleting') : t('app.accountPrivacy.confirmDelete') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-slate-800"
            @click="showDeleteConfirm = false"
          >
            {{ t('common.cancel') }}
          </button>
        </div>
      </form>
    </section>

    <i18n-t
      keypath="app.accountPrivacy.seeMore"
      tag="p"
      class="mt-8 text-sm text-slate-500"
    >
      <template #link>
        <Link href="/confidentialite" class="text-blue-400 hover:underline">{{
          t('app.accountPrivacy.privacyLink')
        }}</Link>
      </template>
    </i18n-t>
  </div>
</template>
